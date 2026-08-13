<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rooms = DB::table('rooms')->pluck('id');
foreach ($rooms as $roomId) {
    $rows = DB::table('seats')->where('room_id', $roomId)->select('row')->distinct()->pluck('row');
    foreach ($rows as $r) {
        $seats = DB::table('seats')->where('room_id', $roomId)->where('row', $r)->orderBy('number')->get();
        for ($i = 0; $i < count($seats); $i++) {
            $seat = $seats[$i];
            
            // Fix orphaned couple_hidden
            if ($seat->type === 'couple_hidden') {
                $prev = $i > 0 ? $seats[$i-1] : null;
                if (!$prev || $prev->type !== 'couple') {
                    DB::table('seats')->where('id', $seat->id)->update(['type' => 'standard']);
                    $seats[$i]->type = 'standard';
                    echo "Restored orphaned hidden seat {$seat->row}{$seat->number}\n";
                }
            }
            
            // Fix contiguous couples
            if ($seat->type === 'couple') {
                if ($i + 1 < count($seats)) {
                    $next = $seats[$i+1];
                    if ($next->type !== 'couple_hidden') {
                        DB::table('seats')->where('id', $next->id)->update(['type' => 'couple_hidden']);
                        $seats[$i+1]->type = 'couple_hidden';
                        echo "Paired seat {$next->row}{$next->number} as hidden for {$seat->row}{$seat->number}\n";
                    }
                }
            }
        }
    }
}
echo "Database cleanup complete.\n";
