<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::whereIn('role', ['admin', 'staff'])->get(['name', 'email', 'role'])->toArray();
echo json_encode($users, JSON_PRETTY_PRINT);
