<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$seats = DB::table('seats')->where('type', 'couple_hidden')->get();
foreach ($seats as $s) {
    $prev = DB::table('seats')
                ->where('room_id', $s->room_id)
                ->where('row', $s->row)
                ->where('number', $s->number - 1)
                ->first();
    if (!$prev || $prev->type !== 'couple') {
        DB::table('seats')->where('id', $s->id)->update(['type' => 'standard']);
        echo 'Fixed seat ' . $s->id . "\n";
    }
}
echo "Done\n";
