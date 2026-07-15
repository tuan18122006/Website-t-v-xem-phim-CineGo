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

        $viewer = auth('sanctum')->user() ?? $request->user();

        // Bình luận bị admin ẩn: chỉ chính người viết mới còn thấy của mình.
        $reviews = Review::where('movie_id', $movieId)
            ->with('user:id,name')
            ->when($viewer, function ($q) use ($viewer) {
                $q->where(function ($sub) use ($viewer) {
                    $sub->where('is_hidden', false)->orWhere('user_id', $viewer->id);
                });
            }, function ($q) {
                $q->where('is_hidden', false);
            })
            ->orderByDesc('is_featured') // review được ghim luôn lên đầu
            ->latest()
            ->get();

        $user = $viewer;
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
                        $query->where('movie_id', $movieId);
                    })
                    ->exists();

                if ($hasCompletedBooking) {
                    $canReview = true;
                    $reviewStatus = 'eligible';
                    $reviewMessage = 'Bạn đủ điều kiện để đánh giá phim này.';
                } else {
                    $reviewStatus = 'no_ticket';
                    $reviewMessage = 'Bạn cần mua vé thành công (đã thanh toán) cho phim này để đánh giá.';
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

    /* ==================== ADMIN MODERATION ==================== */

    /**
     * Bảng kiểm duyệt: tất cả đánh giá từ mọi phim, có lọc & tìm kiếm.
     * ?rating=1..5 | ?movie_id=.. | ?q=từ khóa
     */
    public function adminIndex(Request $request)
    {
        $reviews = Review::with(['user:id,name,email', 'movie:id,title'])
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->rating))
            ->when($request->filled('movie_id'), fn ($q) => $q->where('movie_id', (int) $request->movie_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kw = trim($request->q);
                $q->where(function ($sub) use ($kw) {
                    $sub->where('comment', 'like', "%{$kw}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$kw}%")->orWhere('email', 'like', "%{$kw}%"));
                });
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(20);

        return response()->json($reviews, 200);
    }

    /** Ẩn / hiện một đánh giá */
    public function toggleHide($id)
    {
        $review = Review::findOrFail($id);
        $review->is_hidden = ! $review->is_hidden;
        $review->save();

        return response()->json([
            'success' => true,
            'message' => $review->is_hidden ? 'Đã ẩn bình luận.' : 'Đã hiện lại bình luận.',
            'is_hidden' => $review->is_hidden,
        ], 200);
    }

    /** Ghim / bỏ ghim (nổi bật) */
    public function toggleFeature($id)
    {
        $review = Review::findOrFail($id);
        $review->is_featured = ! $review->is_featured;
        $review->save();

        return response()->json([
            'success' => true,
            'message' => $review->is_featured ? 'Đã ghim bình luận nổi bật.' : 'Đã bỏ ghim.',
            'is_featured' => $review->is_featured,
        ], 200);
    }

    /** Admin phản hồi với tư cách CineGo Official */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'admin_reply' => 'nullable|string|max:1000',
        ], [
            'admin_reply.max' => 'Phản hồi tối đa 1000 ký tự.',
        ]);

        $review = Review::findOrFail($id);
        $review->admin_reply = $request->admin_reply;
        $review->replied_at = $request->filled('admin_reply') ? now() : null;
        $review->save();

        return response()->json([
            'success' => true,
            'message' => $request->filled('admin_reply') ? 'Đã gửi phản hồi.' : 'Đã xóa phản hồi.',
            'data' => $review,
        ], 200);
    }

    /** Admin xóa cứng một đánh giá */
    public function adminDestroy($id)
    {
        Review::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn bình luận.',
        ], 200);
    }

    /**
     * Danh sách bình luận nổi bật/mới cho trang Review phim công khai (nhiều phim).
     */
    public function publicFeatured()
    {
        $reviews = Review::where('is_hidden', false)
            ->with(['user:id,name', 'movie:id,title,poster_url,duration'])
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }
}
