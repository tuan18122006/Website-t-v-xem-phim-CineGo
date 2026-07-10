<!DOCTYPE html>
<html lang="vi">

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