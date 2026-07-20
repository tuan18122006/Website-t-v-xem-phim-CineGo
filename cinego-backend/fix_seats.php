<?php
$seats = \App\Models\Seat::where('row', 'J')->orderBy('number')->get();
foreach($seats as $k => $s) {
    if ($k % 2 != 0) {
        $s->type = 'couple_hidden';
        $s->save();
    } else {
        $s->type = 'couple';
        $s->save();
    }
}
echo 'Done';
