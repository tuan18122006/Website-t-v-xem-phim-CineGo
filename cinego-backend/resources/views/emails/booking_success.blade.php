<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận Đặt vé - CineGo</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #e50914; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #e50914; margin: 0; }
        .ticket-box { background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; border-bottom: 1px dashed #ccc; padding: 8px 0; }
        .row:last-child { border-bottom: none; }
        .total { font-size: 18px; font-weight: bold; color: #e50914; }
        .qr-code { text-align: center; margin-top: 20px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CineGo Cinemas</h1>
            <h2>Xác Nhận Đặt Vé Thành Công</h2>
        </div>
        
        <p>Xin chào <strong>{{ $booking->user ? $booking->user->name : 'Quý khách' }}</strong>,</p>
        <p>Cảm ơn bạn đã lựa chọn CineGo. Dưới đây là thông tin vé của bạn:</p>

        <div class="ticket-box">
            <div class="row">
                <span>Mã Đặt Vé:</span>
                <strong style="color: #e50914; font-size: 18px;">{{ $booking->booking_code }}</strong>
            </div>
            <div class="row">
                <span>Phim:</span>
                <strong>{{ $booking->showtime->movie->title ?? 'N/A' }}</strong>
            </div>
            <div class="row">
                <span>Suất chiếu:</span>
                <strong>
                    {{ \Carbon\Carbon::parse($booking->showtime->start_time)->format('H:i d/m/Y') }}
                </strong>
            </div>
            <div class="row">
                <span>Phòng chiếu:</span>
                <strong>{{ $booking->showtime->room->name ?? 'N/A' }}</strong>
            </div>
            <div class="row">
                <span>Ghế:</span>
                <strong>
                    @foreach($booking->bookingDetails as $detail)
                        @if($detail->seat)
                            {{ $detail->seat->row }}{{ $detail->seat->number }}@if(!$loop->last), @endif
                        @endif
                    @endforeach
                </strong>
            </div>
            @if($booking->bookingCombos && $booking->bookingCombos->count() > 0)
            <div class="row">
                <span>Bắp nước:</span>
                <strong>
                    @foreach($booking->bookingCombos as $bc)
                        {{ $bc->combo->name }} (x{{ $bc->quantity }})@if(!$loop->last), @endif
                    @endforeach
                </strong>
            </div>
            @endif
            <div class="row">
                <span>Tổng Tiền:</span>
                <span class="total">{{ number_format($booking->total_amount, 0, ',', '.') }} VNĐ</span>
            </div>
        </div>

        <div class="qr-code">
            <p>Vui lòng xuất trình mã này tại quầy để nhận vé hoặc vào rạp:</p>
            <!-- Sử dụng dịch vụ tạo QR Code online để hiển thị QR trong email -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $booking->booking_code }}" alt="QR Code">
        </div>

        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống CineGo. Vui lòng không trả lời.</p>
            <p>&copy; 2026 CineGo Cinemas. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
