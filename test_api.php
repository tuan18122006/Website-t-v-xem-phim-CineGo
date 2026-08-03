<?php
require "cinego-backend/vendor/autoload.php";
$app = require_once "cinego-backend/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::where("email", "admin@cinego.com")->first();
$token = $user->createToken("test")->plainTextToken;
echo "Token: " . $token;

