<?php
$file = 'app/Http/Controllers/Api/ShowtimeController.php';
$content = file_get_contents($file);

$storeMethodOldRegex = '/public function store\(Request \$request\)\s*\{.*?\/\/ Nếu tất cả các ngày đều pass, bắt đầu insert/s';

$storeMethodNew = <<<'EOD'
public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date',
            'times' => 'required|array',
            'times.*' => 'required|string',
            'end_date' => 'nullable|date',
            'format' => 'required|string',
            'translation' => 'required|string',
        ]);

        $movie = \App\Models\Movie::findOrFail($request->movie_id);
        // Thêm 15 phút thời gian dọn phòng
        $duration = $movie->duration + 15; 

        $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
        $endDate = $request->has('end_date') && $request->end_date ? \Carbon\Carbon::parse($request->end_date)->startOfDay() : $startDate->copy();

        $showtimeDates = [];
        $currentDate = $startDate->copy();

        // Bước 1: Kiểm tra chống trùng lịch cho TOÀN BỘ dải ngày và khung giờ
        while ($currentDate <= $endDate) {
            foreach ($request->times as $timeStr) {
                if (empty($timeStr)) continue;
                $timeParts = explode(':', $timeStr);
                $currentStart = $currentDate->copy()->setHour($timeParts[0])->setMinute($timeParts[1]);
                $currentEnd = $currentStart->copy()->addMinutes($duration);
                
                $conflict = \App\Models\Showtime::where('room_id', $request->room_id)
                    ->where('status', 'active')
                    ->where('start_time', '<', $currentEnd)
                    ->where('end_time', '>', $currentStart)
                    ->with('movie:id,title')
                    ->first();

                if ($conflict) {
                    $clashName = $conflict->movie ? $conflict->movie->title : 'một suất chiếu khác';

                    return response()->json([
                        'success' => false,
                        'message' => "Phòng kín lịch ngày " . $currentStart->format('d/m/Y') . " lúc " . $currentStart->format('H:i') . "! Đang vướng suất \"{$clashName}\" từ "
                            . Carbon::parse($conflict->start_time)->format('H:i') . ' đến '
                            . Carbon::parse($conflict->end_time)->format('H:i')
                            . '. Vui lòng kiểm tra lại!',
                        'conflict' => [
                            'id'         => $conflict->id,
                            'movie'      => $clashName,
                            'start_time' => Carbon::parse($conflict->start_time)->toIso8601String(),
                            'end_time'   => Carbon::parse($conflict->end_time)->toIso8601String(),
                        ],
                    ], 422);
                }
                
                $showtimeDates[] = [
                    'start' => $currentStart->copy(),
                    'end' => $currentEnd->copy()
                ];
            }
            $currentDate->addDay();
        }

        // Nếu tất cả các ngày đều pass, bắt đầu insert
EOD;

$content = preg_replace($storeMethodOldRegex, $storeMethodNew, $content);

file_put_contents($file, $content);
echo "ShowtimeController updated!";
