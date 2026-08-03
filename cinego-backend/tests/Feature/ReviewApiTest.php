<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_review_without_a_booking(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'Dune: Part Two',
            'slug' => 'dune-part-two',
            'description' => 'Sci-fi epic',
            'duration' => 166,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'PG-13',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/movies/{$movie->id}/reviews", [
            'rating' => 5,
            'comment' => 'Phim rất hay',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.rating', 5);
        $this->assertDatabaseHas('reviews', [
            'movie_id' => $movie->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_only_submit_one_review_per_movie(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'Inside Out 2',
            'slug' => 'inside-out-2',
            'description' => 'Animation',
            'duration' => 96,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'G',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $room = Room::create([
            'name' => 'Phòng 2',
            'total_seats' => 120,
            'status' => 'active',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHours(2),
            'format' => '2D',
            'translation' => 'Phụ đề',
            'status' => 'showing',
        ]);

        Booking::create([
            'booking_code' => 'BK-002',
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ]);

        $this->actingAs($user, 'sanctum')->postJson("/api/movies/{$movie->id}/reviews", [
            'rating' => 4,
            'comment' => 'Phim hay',
        ])->assertCreated();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/movies/{$movie->id}/reviews", [
            'rating' => 5,
            'comment' => 'Phim hay nữa',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Bạn đã đánh giá phim này rồi.']);
    }

    public function test_review_index_returns_clear_status_for_guest_user(): void
    {
        $movie = Movie::create([
            'title' => 'The Matrix',
            'slug' => 'the-matrix',
            'description' => 'Sci-fi',
            'duration' => 136,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'R',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $response = $this->getJson("/api/movies/{$movie->id}/reviews");

        $response->assertOk();
        $response->assertJsonPath('data.review_status', 'guest');
        $response->assertJsonPath('data.can_review', false);
    }

    public function test_review_index_returns_status_for_authenticated_user_with_eligible_booking(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'The Matrix Resurrections',
            'slug' => 'the-matrix-resurrections',
            'description' => 'Sci-fi sequel',
            'duration' => 148,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'R',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $room = Room::create([
            'name' => 'Phòng 4',
            'total_seats' => 120,
            'status' => 'active',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHours(2),
            'format' => '2D',
            'translation' => 'Phụ đề',
            'status' => 'showing',
        ]);

        Booking::create([
            'booking_code' => 'BK-004',
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/movies/{$movie->id}/reviews");

        $response->assertOk();
        $response->assertJsonPath('data.review_status', 'eligible');
        $response->assertJsonPath('data.can_review', true);
        $response->assertJsonPath('data.review_message', 'Bạn đủ điều kiện để đánh giá phim này.');
    }

    public function test_review_index_accepts_bearer_token_on_public_route(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'Fast & Furious 11',
            'slug' => 'fast-furious-11',
            'description' => 'Action sequel',
            'duration' => 120,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'PG-13',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $room = Room::create([
            'name' => 'Phòng 5',
            'total_seats' => 120,
            'status' => 'active',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHours(2),
            'format' => '2D',
            'translation' => 'Phụ đề',
            'status' => 'showing',
        ]);

        Booking::create([
            'booking_code' => 'BK-005',
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/movies/{$movie->id}/reviews");

        $response->assertOk();
        $response->assertJsonPath('data.review_status', 'eligible');
        $response->assertJsonPath('data.can_review', true);
    }

    public function test_owner_can_update_their_review(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'Inside Out',
            'slug' => 'inside-out',
            'description' => 'Animation',
            'duration' => 95,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'G',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $room = Room::create([
            'name' => 'Phòng 3',
            'total_seats' => 120,
            'status' => 'active',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHours(2),
            'format' => '2D',
            'translation' => 'Phụ đề',
            'status' => 'showing',
        ]);

        Booking::create([
            'booking_code' => 'BK-003',
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => 3,
            'comment' => 'Ban đầu',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/movies/{$movie->id}/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => 'Đã cập nhật',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rating', 5);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => 'Đã cập nhật',
        ]);
    }

    public function test_admin_can_update_other_users_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'The Batman',
            'slug' => 'the-batman',
            'description' => 'Superhero',
            'duration' => 176,
            'release_date' => now()->subMonth(),
            'status' => 'now_showing',
            'rating' => 'PG-13',
            'poster_url' => 'https://example.com/poster.jpg',
            'trailer_url' => 'https://example.com/trailer.mp4',
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => 2,
            'comment' => 'Nội dung cũ',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/movies/{$movie->id}/reviews/{$review->id}", [
            'rating' => 4,
            'comment' => 'Admin chỉnh sửa',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.comment', 'Admin chỉnh sửa');
    }
}
