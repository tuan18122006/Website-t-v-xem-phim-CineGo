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

<head>
    <meta charset="UTF-8">
    <title>Vé xem phim CineGo</title>
</head>

<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#000;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:20px;">

                <table width="500" cellpadding="0" cellspacing="0" style="border:1px solid #000;">

                    <tr>
                        <td style="padding:16px;border-bottom:1px solid #000;text-align:center;">
                            <b style="font-size:18px;">VÉ XEM PHIM CINEGO</b>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px;">
                            <p style="margin:0 0 10px 0;">
                                Xin chào <b>{{ $booking->user->name ?? 'Quý khách' }}</b>, cảm ơn bạn đã đặt vé tại CineGo.
                            </p>
                            <table width="100%" cellpadding="4" cellspacing="0">
                                <tr>
                                    <td width="140"><b>Mã đặt vé</b></td>
                                    <td>: {{ $booking->booking_code }}</td>
                                </tr>
                                <tr>
                                    <td><b>Phim</b></td>
                                    <td>: {{ $booking->showtime->movie->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><b>Suất chiếu</b></td>
                                    <td>: {{ $booking->showtime->start_time->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><b>Phòng</b></td>
                                    <td>: {{ $booking->showtime->room->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><b>Ngày đặt vé</b></td>
                                    <td>: {{ $booking->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ghế -->
                    <tr>
                        <td style="padding:0 16px 16px 16px;">
                            <b>Ghế đã đặt</b>
                            <table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #000;border-collapse:collapse;margin-top:6px;">
                                <tr style="background:#eee;">
                                    <td style="border:1px solid #000;"><b>Ghế</b></td>
                                    <td style="border:1px solid #000;"><b>Loại ghế</b></td>
                                    <td style="border:1px solid #000;" align="right"><b>Giá</b></td>
                                </tr>
                                @foreach($booking->bookingDetails as $ticket)
                                <tr>
                                    <td style="border:1px solid #000;">{{ $ticket->seat->row }}{{ $ticket->seat->number }}</td>
                                    <td style="border:1px solid #000;">{{ strtoupper($ticket->seat->type) }}</td>
                                    <td style="border:1px solid #000;" align="right">{{ number_format($ticket->price) }}đ</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- Combo -->
                    @if($booking->bookingCombos && $booking->bookingCombos->count() > 0)
                    <tr>
                        <td style="padding:0 16px 16px 16px;">
                            <b>Combo đã chọn</b>
                            <table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #000;border-collapse:collapse;margin-top:6px;">
                                <tr style="background:#eee;">
                                    <td style="border:1px solid #000;"><b>Tên combo</b></td>
                                    <td style="border:1px solid #000;" align="center"><b>Số lượng</b></td>
                                    <td style="border:1px solid #000;" align="right"><b>Giá</b></td>
                                </tr>
                                @foreach($booking->bookingCombos as $combo)
                                <tr>
                                    <td style="border:1px solid #000;">{{ $combo->combo->name }}</td>
                                    <td style="border:1px solid #000;" align="center">{{ $combo->quantity }}</td>
                                    <td style="border:1px solid #000;" align="right">{{ number_format($combo->subtotal) }}đ</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Mã giảm giá -->
                    <tr>
                        <td style="padding:0 16px 16px 16px;">
                            <table width="100%" cellpadding="4" cellspacing="0">
                                <tr>
                                    <td width="160"><b>Mã giảm giá</b></td>
                                    @if($booking->discount_amount > 0)
                                    <td>: Đã áp dụng ({{ $booking->voucher->code ?? '' }}) — giảm {{ number_format($booking->discount_amount) }}đ</td>
                                    @else
                                    <td>: Không sử dụng</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td><b>Tạm tính</b></td>
                                    <td>: {{ number_format($booking->subtotal) }}đ</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Tổng tiền -->
                    <tr>
                        <td style="padding:0 16px 16px 16px;text-align:right;">
                            <b style="font-size:16px;">Tổng thanh toán: {{ number_format($booking->total_amount) }}đ</b>
                        </td>
                    </tr>

                    <!-- QR -->
                    <tr>
                        <td style="padding:16px;border-top:1px solid #000;text-align:center;">
                            @php
                            $ticketUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/ticket/' . $booking->booking_code;

                            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($ticketUrl);
                            @endphp

                            <img src="{{ $qrUrl }}" width="160" alt="QR Code">

                            <p style="margin:8px 0 0 0;">
                                Quét mã QR này để xem chi tiết vé đã đặt.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
