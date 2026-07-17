<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleMovie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    // ADMIN APIs
    public function index()
    {
        $articles = Article::withCount('movies')->orderBy('created_at', 'desc')->get();
        return response()->json($articles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'thumbnail_url' => 'nullable',
            'is_published' => 'boolean',
            'movies' => 'array',
            'movies.*.id' => 'required|exists:movies,id',
            'movies.*.rank' => 'required|integer',
            'movies.*.review_text' => 'nullable|string',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        if (isset($validated['is_published']) && $validated['is_published']) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('thumbnail_url')) {
            $file = $request->file('thumbnail_url');
            if (strpos($file->getMimeType(), 'image/') !== 0) {
                return response()->json(['errors' => ['thumbnail_url' => ['Chỉ cho phép tải lên file ảnh.']]], 422);
            }
            $path = $file->store('articles', 'public');
            $validated['thumbnail_url'] = '/storage/' . $path;
        } else {
            unset($validated['thumbnail_url']);
        }

        $article = Article::create($validated);

        if (!empty($validated['movies'])) {
            foreach ($validated['movies'] as $movieData) {
                ArticleMovie::create([
                    'article_id' => $article->id,
                    'movie_id' => $movieData['id'],
                    'rank' => $movieData['rank'],
                    'review_text' => $movieData['review_text'] ?? null,
                ]);
            }
        }

        return response()->json($article->load('movies'), 201);
    }

    public function show($id)
    {
        $article = Article::with('movies')->findOrFail($id);
        return response()->json($article);
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'thumbnail_url' => 'nullable',
            'is_published' => 'boolean',
            'movies' => 'array',
        ]);

        if ($validated['title'] !== $article->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if (isset($validated['is_published']) && $validated['is_published'] && !$article->is_published) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('thumbnail_url')) {
            $file = $request->file('thumbnail_url');
            if (strpos($file->getMimeType(), 'image/') !== 0) {
                return response()->json(['errors' => ['thumbnail_url' => ['Chỉ cho phép tải lên file ảnh.']]], 422);
            }
            $path = $file->store('articles', 'public');
            $validated['thumbnail_url'] = '/storage/' . $path;
        } else {
            unset($validated['thumbnail_url']);
        }

        $article->update($validated);

        if (isset($validated['movies'])) {
            // Delete old relationships
            ArticleMovie::where('article_id', $article->id)->delete();
            // Create new ones
            foreach ($validated['movies'] as $movieData) {
                ArticleMovie::create([
                    'article_id' => $article->id,
                    'movie_id' => $movieData['id'],
                    'rank' => $movieData['rank'] ?? 0,
                    'review_text' => $movieData['review_text'] ?? null,
                ]);
            }
        }

        return response()->json($article->load('movies'));
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }

    // CLIENT APIs
    public function publicIndex()
    {
        $articles = Article::with(['movies' => function($q) {
                $q->orderBy('article_movies.rank', 'asc');
            }])
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->paginate(12);
        return response()->json($articles);
    }

    public function publicShow($slug)
    {
        $article = Article::with('movies')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Increment views
        $article->increment('views');

        return response()->json($article);
    }
}
