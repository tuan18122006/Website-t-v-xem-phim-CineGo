<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Movies: " . \App\Models\Movie::count() . "\n";
echo "Showtimes: " . \App\Models\Showtime::count() . "\n";
echo "Bookings: " . \App\Models\Booking::count() . "\n";
