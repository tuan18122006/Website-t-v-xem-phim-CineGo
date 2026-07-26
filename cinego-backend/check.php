<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

print_r(DB::table('seats')->where('room_id', 2)->where('row', 'J')->get()->pluck('type', 'number')->toArray());
