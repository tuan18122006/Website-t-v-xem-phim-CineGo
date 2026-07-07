<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đặt vé CineGo thành công</title>
</head>

<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
        <tr>
            <td align="center">

                <table width="650" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);">

                    <!-- Banner -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#e11d2e,#c40823);padding:34px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="middle">
                                        <span style="font-size:30px;">🎞️</span>
                                        <span style="font-size:28px;font-weight:bold;color:#ffffff;vertical-align:middle;margin-left:8px;">CineGo</span>
                                        <div style="margin-top:8px;font-size:15px;color:#ffe1e1;">Đặt vé thành công</div>
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="font-size:38px;">🍿🥤</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding:30px 30px 10px 30px;">
                            <h2 style="margin:0 0 6px 0;color:#1a1a1a;">Xin chào {{ $booking->user->name }},</h2>
                            <p style="margin:0;color:#666;font-size:14px;">
                                Cảm ơn bạn đã lựa chọn <b>CineGo</b>. Chúc bạn có trải nghiệm xem phim tuyệt vời!
                            </p>
                        </td>
                    </tr>

                    <!-- Order info: 3 cột -->
                    <tr>
                        <td style="padding:20px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border:1px solid #eee;border-radius:10px;">
                                <tr>
                                    <td width="33%" style="padding:18px;border-right:1px solid #f0f0f0;">
                                        <div style="font-size:20px;">🎫</div>
                                        <div style="font-size:12px;color:#999;margin-top:4px;">Mã đặt vé</div>
                                        <div style="font-size:15px;font-weight:bold;color:#e11d2e;">{{ $booking->booking_code }}</div>
                                    </td>
                                    <td width="33%" style="padding:18px;border-right:1px solid #f0f0f0;">
                                        <div style="font-size:20px;">📅</div>
                                        <div style="font-size:12px;color:#999;margin-top:4px;">Ngày đặt</div>
                                        <div style="font-size:15px;font-weight:bold;">{{ $booking->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td width="33%" style="padding:18px;">
                                        <div style="font-size:20px;">💳</div>
                                        <div style="font-size:12px;color:#999;margin-top:4px;">Thanh toán</div>
                                        <div style="font-size:15px;font-weight:bold;">{{ strtoupper($booking->payment_method) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Movie -->
                    <tr>
                        <td style="padding:10px 30px;">
                            <h3 style="color:#e11d2e;margin:0 0 12px 0;">🍿 Thông tin phim</h3>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="62%" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;width:34px;">🎞️</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;color:#999;font-size:13px;width:90px;">Phim</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;font-weight:bold;">{{ $booking->showtime->movie->title }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;">🕐</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;color:#999;font-size:13px;">Suất chiếu</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #f2f2f2;font-weight:bold;">{{ $booking->showtime->start_time->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;">🏢</td>
                                                <td style="padding:8px 0;color:#999;font-size:13px;">Phòng</td>
                                                <td style="padding:8px 0;font-weight:bold;">{{ $booking->showtime->room->name }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="38%" align="right" valign="top">
                                        <img src="{{ $message->embed($posterPath) }}" width="140"
                                            style="border-radius:10px;display:block;margin-left:auto;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Seats -->
                    <tr>
                        <td style="padding:20px 30px 10px 30px;">
                            <h3 style="color:#e11d2e;margin:0 0 12px 0;">💺 Ghế</h3>
                            @foreach($booking->bookingDetails as $ticket)
                            <span style="display:inline-block;background:#e11d2e;color:#ffffff;padding:8px 16px;
                                border-radius:20px;margin:0 6px 6px 0;font-weight:bold;font-size:14px;">
                                {{ $ticket->seat->row }}{{ $ticket->seat->number }}
                            </span>
                            @endforeach
                        </td>
                    </tr>

                    <!-- Combo -->
                    @if($booking->bookingCombos->count())
                    <tr>
                        <td style="padding:10px 30px;">
                            <h3 style="color:#e11d2e;margin:0 0 12px 0;">🥤 Combo</h3>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border:1px solid #eee;border-radius:10px;overflow:hidden;">
                                <tr style="background:#faf7f7;">
                                    <td style="padding:10px 14px;font-size:12px;color:#999;">Tên</td>
                                    <td align="center" style="padding:10px 14px;font-size:12px;color:#999;">Số lượng</td>
                                    <td align="right" style="padding:10px 14px;font-size:12px;color:#999;">Tiền</td>
                                </tr>
                                @foreach($booking->bookingCombos as $combo)
                                <tr>
                                    <td style="padding:12px 14px;border-top:1px solid #f2f2f2;">
                                        <img src="{{ $message->embed($comboImages[$combo->id]) }}" width="26"
                                            style="border-radius:4px;vertical-align:middle;margin-right:8px;">
                                        {{ $combo->combo->name }}
                                    </td>
                                    <td align="center" style="padding:12px 14px;border-top:1px solid #f2f2f2;">{{ $combo->quantity }}</td>
                                    <td align="right" style="padding:12px 14px;border-top:1px solid #f2f2f2;">{{ number_format($combo->subtotal) }}đ</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Price + QR: 2 cột -->
                    <tr>
                        <td style="padding:20px 30px 30px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Cột trái: tổng tiền -->
                                    <td width="58%" valign="top" style="padding:20px;background:#fdf1f2;border-radius:10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0;color:#666;">Tạm tính</td>
                                                <td align="right" style="padding:6px 0;">{{ number_format($booking->subtotal) }}đ</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#666;">Giảm giá</td>
                                                <td align="right" style="padding:6px 0;color:#e11d2e;">-{{ number_format($booking->discount_amount) }}đ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-top:1px dashed #f0c4c8;padding-top:10px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:18px;font-weight:bold;color:#e11d2e;">Tổng thanh toán</td>
                                                <td align="right" style="font-size:18px;font-weight:bold;color:#e11d2e;">{{ number_format($booking->total_amount) }}đ</td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td width="4%"></td>

                                    <!-- Cột phải: QR -->
                                    <td width="38%" align="center" valign="top" style="padding:20px;background:#fdf1f2;border-radius:10px;">
                                        <img src="{{ $message->embed($qrPath) }}" width="130" alt="QR Code" style="display:block;margin:0 auto 10px auto;">
                                        <div style="font-size:13px;font-weight:bold;color:#1a1a1a;">Quét mã để check-in</div>
                                        <div style="font-size:11px;color:#888;margin-top:4px;">
                                            Vui lòng xuất trình mã QR tại rạp để nhân viên kiểm tra vé.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#fafafa;padding:24px 30px;border-top:1px solid #f0f0f0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="33%" style="font-size:12px;color:#777;">
                                        🏷️ Giữ mã QR này<br>để check-in tại rạp
                                    </td>
                                    <td width="33%" style="font-size:12px;color:#777;">
                                        🕐 Đến rạp trước giờ chiếu<br>30 phút để làm thủ tục
                                    </td>
                                    <td width="34%" align="right" style="font-size:12px;color:#777;">
                                        Cảm ơn bạn đã chọn CineGo!<br>
                                        <a href="https://cinego.vn" style="color:#e11d2e;text-decoration:none;font-weight:bold;">www.cinego.vn</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>