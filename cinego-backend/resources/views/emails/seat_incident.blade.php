<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo sự cố ghế - CineGo</title>
</head>

<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#000;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:20px;">

                <table width="500" cellpadding="0" cellspacing="0" style="border:1px solid #dc2626;">

                    <tr>
                        <td style="padding:16px;border-bottom:2px solid #dc2626;text-align:center;background:#fef2f2;">
                            <b style="font-size:18px;color:#dc2626;">THÔNG BÁO SỰ CỐ GHẾ</b>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px;">
                            <p style="margin:0 0 10px 0;">
                                Xin chào <b>{{ $booking->user->name ?? 'Quý khách' }}</b>,
                            </p>
                            <p style="margin:0 0 10px 0;">
                                Rất tiếc, ghế <b style="color:#dc2626;">{{ $seatLabel }}</b> bạn đã đặt cho phim <b>{{ $movieTitle }}</b> tại phòng <b>{{ $roomName }}</b>, suất <b>{{ $showtimeAt }}</b> gặp sự cố kỹ thuật và không thể sử dụng.
                            </p>
                            <p style="margin:0 0 10px 0;">
                                Vui lòng chọn 1 trong 2 phương án bên dưới để xử lý:
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 16px 16px 16px;">
                            <table width="100%" cellpadding="12" cellspacing="0" style="border:1px solid #e5e7eb;border-collapse:collapse;">
                                <tr style="background:#f0fdf4;">
                                    <td style="border:1px solid #e5e7eb;width:30px;text-align:center;font-size:16px;font-weight:700;color:#16a34a;">1</td>
                                    <td style="border:1px solid #e5e7eb;">
                                        <b style="color:#16a34a;">Đổi ghế miễn phí</b><br>
                                        <span style="font-size:13px;color:#475569;">Đổi sang ghế khác cùng phòng, miễn phí chênh lệch giá.</span>
                                    </td>
                                </tr>
                                <tr style="background:#fef2f2;">
                                    <td style="border:1px solid #e5e7eb;text-align:center;font-size:16px;font-weight:700;color:#dc2626;">2</td>
                                    <td style="border:1px solid #e5e7eb;">
                                        <b style="color:#dc2626;">Hoàn tiền vào ví</b><br>
                                        <span style="font-size:13px;color:#475569;">Hoàn {{ number_format($refundAmount) }}đ vào ví tiền CineGo của bạn.</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 16px 16px 16px;">
                            <p style="margin:0;font-size:13px;color:#64748b;">
                                Mã đặt vé: <b>{{ $booking->booking_code }}</b>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px;border-top:1px solid #e5e7eb;text-align:center;background:#f8fafc;">
                            @php
                            $profileUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/profile?tab=history&subtab=affected';
                            @endphp
                            <a href="{{ $profileUrl }}" style="display:inline-block;padding:12px 24px;background:#dc2626;color:#fff;text-decoration:none;border-radius:6px;font-weight:700;">
                                Xử lý ngay
                            </a>
                            <p style="margin:8px 0 0 0;font-size:12px;color:#94a3b8;">
                                Đăng nhập và vào tab "Lịch sử giao dịch" → "Vé bị ảnh hưởng" để xử lý.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
