<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$user = \App\Models\User::where('email', 'tuan25042k6@gmail.com')->first();
if ($user) {
    // Reset tier to Silver first so we can trigger Gold again or trigger Diamond
    $user->membership_tier = 'Silver';
    $user->save();
    \App\Services\LoyaltyService::setTier($user, 'Diamond');
    echo 'Success';
}
