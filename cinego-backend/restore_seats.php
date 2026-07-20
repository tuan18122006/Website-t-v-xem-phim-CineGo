<?php
$seats = \App\Models\Seat::where('row', 'J')->orderBy('number')->get();
foreach($seats as $s) {
    $s->type = 'couple';
    $s->save();
}
echo 'Restored to 12 couple seats';
