<?php

$movie = \App\Models\Movie::where("status", "showing")->orWhere("status", "Đang chiếu")->first();
if (!$movie) $movie = \App\Models\Movie::first();
$room = \App\Models\Room::first();
$start = \Carbon\Carbon::today()->setHour(22)->setMinute(10);
$end = $start->copy()->addMinutes($movie->duration ?? 120);

$showtime = \App\Models\Showtime::create([
    "movie_id" => $movie->id,
    "room_id" => $room->id,
    "start_time" => $start,
    "end_time" => $end,
    "format" => "2D",
    "translation" => "Phụ đề",
    "status" => "active"
]);

\App\Models\PriceConfig::create(["showtime_id" => $showtime->id, "seat_type" => "standard", "price" => 75000]);
\App\Models\PriceConfig::create(["showtime_id" => $showtime->id, "seat_type" => "vip", "price" => 95000]);
\App\Models\PriceConfig::create(["showtime_id" => $showtime->id, "seat_type" => "couple", "price" => 160000]);
echo "Created showtime ID: " . $showtime->id . " for movie: " . $movie->title . " at " . $start->format("Y-m-d H:i:s") . "\n";
