<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct($booking)
    {
        // 🔥 LOAD BỔ SUNG QUAN HỆ Combo/Quà tặng miễn phí mà người dùng đã dùng
        if ($booking instanceof Booking) {
            $this->booking = $booking->load([
                'user',
                'showtime.movie',
                'showtime.room',
                'bookingDetails.seat',
                'bookingCombos.combo',
                'voucher',
                'userCombos', // 👈 Eager load quà tặng/combo đổi điểm ở đây
            ]);
        } else {
            $this->booking = $booking;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận Đặt vé thành công - CineGo',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_success',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}