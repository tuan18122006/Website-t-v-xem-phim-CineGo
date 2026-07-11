<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReviewController extends Controller
{
    public function index(Request $request, $movieId)
    {
        $movie = Movie::findOrFail($movieId);

        $reviews = Review::where('movie_id', $movieId)
            ->with('user:id,name')
            ->latest()
            ->get();

        $user = auth('sanctum')->user() ?? $request->user();
        $reviewStatus = 'guest';
        $canReview = false;
        $reviewMessage = 'Bạn cần đăng nhập và đủ điều kiện để đánh giá phim này.';

        if ($user) {
            $hasReviewed = $reviews->contains('user_id', $user->id);
            if ($hasReviewed) {
                $reviewStatus = 'reviewed';
                $reviewMessage = 'Bạn đã đánh giá phim này rồi.';
            } else {
                $hasCompletedBooking = Booking::where('user_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->where('booking_status', 'confirmed')
                    ->whereHas('showtime', function ($query) use ($movieId) {
                        $query->where('movie_id', $movieId)
                            ->where('end_time', '<=', Carbon::now());
                    })
                    ->exists();

                if ($hasCompletedBooking) {
                    $canReview = true;
                    $reviewStatus = 'eligible';
                    $reviewMessage = 'Bạn đủ điều kiện để đánh giá phim này.';
                } else {
                    $hasFutureBooking = Booking::where('user_id', $user->id)
                        ->where('payment_status', 'paid')
                        ->where('booking_status', 'confirmed')
                        ->whereHas('showtime', function ($query) use ($movieId) {
                            $query->where('movie_id', $movieId)
                                ->where('end_time', '>', Carbon::now());
                        })
                        ->exists();

                    if ($hasFutureBooking) {
                        $reviewStatus = 'waiting';
                        $reviewMessage = 'Bạn chỉ có thể đánh giá sau khi suất chiếu kết thúc.';
                    } else {
                        $hasAnyBooking = Booking::where('user_id', $user->id)
                            ->whereHas('showtime', function ($query) use ($movieId) {
                                $query->where('movie_id', $movieId);
                            })
                            ->exists();

                        if ($hasAnyBooking) {
                            $reviewStatus = 'waiting';
                            $reviewMessage = 'Bạn chỉ có thể đánh giá sau khi suất chiếu kết thúc.';
                        } else {
                            $reviewStatus = 'no_ticket';
                            $reviewMessage = 'Bạn cần mua vé phim này để đánh giá.';
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'movie_id' => $movie->id,
                'movie_title' => $movie->title,
                'average_rating' => round($reviews->avg('rating') ?? 0, 1),
                'total_reviews' => $reviews->count(),
                'reviews' => $reviews,
                'review_status' => $reviewStatus,
                'can_review' => $canReview,
                'review_message' => $reviewMessage,
            ],
        ], 200);
    }

    public function store(Request $request, $movieId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Vui lòng chọn điểm đánh giá.',
            'rating.integer' => 'Điểm đánh giá phải là số nguyên.',
            'rating.min' => 'Điểm đánh giá tối thiểu là 1.',
            'rating.max' => 'Điểm đánh giá tối đa là 5.',
            'comment.max' => 'Nội dung đánh giá tối đa 1000 ký tự.',
        ]);

        $user = $request->user();
        $movie = Movie::findOrFail($movieId);

        if ($user->reviews()->where('movie_id', $movieId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá phim này rồi.',
            ], 422);
        }

        $hasCompletedBooking = Booking::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->where('booking_status', 'confirmed')
            ->whereHas('showtime', function ($query) use ($movieId) {
                $query->where('movie_id', $movieId)
                    ->where('end_time', '<=', Carbon::now());
            })
            ->exists();

        if (! $hasCompletedBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chỉ có thể đánh giá sau khi suất chiếu kết thúc.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá thành công.',
            'data' => $review->load('user:id,name'),
        ], 201);
    }

    public function update(Request $request, $movieId, $reviewId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::where('id', $reviewId)
            ->where('movie_id', $movieId)
            ->firstOrFail();

        $user = $request->user();

        if ($user->id !== $review->user_id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền sửa đánh giá này.',
            ], 403);
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đánh giá thành công.',
            'data' => $review->load('user:id,name'),
        ], 200);
    }

    public function destroy(Request $request, $movieId, $reviewId)
    {
        $review = Review::where('id', $reviewId)
            ->where('movie_id', $movieId)
            ->firstOrFail();

        $user = $request->user();

        if ($user->id !== $review->user_id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa đánh giá này.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công.',
        ], 200);
    }
}
