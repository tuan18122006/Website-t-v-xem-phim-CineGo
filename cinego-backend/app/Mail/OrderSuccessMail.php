<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class OrderSuccessMail extends Mailable
{
    use SerializesModels;

    public $booking;
    public $qrPath;
    public $posterPath;

    public $comboImages = [];

    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load([
            'user',
            'showtime.movie',
            'showtime.room',
            'bookingDetails.seat',
            'bookingCombos.combo',
            'voucher'
        ]);
        // --- 1. XỬ LÝ POSTER ---
        $posterUrl = $booking->showtime->movie->poster_url;

        // Lấy phần đường dẫn tương đối (VD: 'posters/ten-anh.jpg') bằng cách cắt bỏ cả dấu '/'
        $posterRelativePath = str_replace(url('/storage') . '/', '', $posterUrl);

        // Trỏ thẳng vào thư mục vật lý storage/app/public/
        $this->posterPath = storage_path('app/public/' . $posterRelativePath);


        // --- 2. XỬ LÝ COMBO IMAGES ---
        foreach ($this->booking->bookingCombos as $item) {
            $imageUrl = $item->combo->image_url;

            // 1. Lấy phần path từ URL (ví dụ: /storage/combos/ten-anh.jpg)
            $path = parse_url($imageUrl, PHP_URL_PATH);

            // 2. Loại bỏ tiền tố '/storage/' để chỉ lấy 'combos/ten-anh.jpg'
            // Dùng ltrim để xóa dấu '/' ở đầu nếu có
            $relativePath = ltrim(str_replace('/storage', '', $path), '/');

            // 3. Trỏ về đường dẫn vật lý: storage/app/public/combos/ten-anh.jpg
            $this->comboImages[$item->id] = storage_path('app/public/' . $relativePath);
        }

        // Tạo thư mục nếu chưa có
        $directory = storage_path('app/public/qrcodes');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Sinh QR
        $result = new Builder(
            writer: new PngWriter(),
            data: $booking->booking_code,
            size: 250,
            margin: 1
        );

        // Đường dẫn file
        $this->qrPath = $directory . '/' . $booking->booking_code . '.png';

        // Ghi file
        file_put_contents(
            $this->qrPath,
            $result->build()->getString()
        );
        
    }



    public function build()
    {
        return $this
            ->subject('🎬 Đặt vé CineGo thành công')
            ->view('emails.order-success');
    }
}
