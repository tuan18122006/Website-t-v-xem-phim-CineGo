<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SeatIncidentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $seatLabel;
    public $movieTitle;
    public $showtimeAt;
    public $roomName;
    public $refundAmount;

    public function __construct($booking, $seatLabel, $movieTitle, $showtimeAt, $roomName, $refundAmount)
    {
        $this->booking = $booking;
        $this->seatLabel = $seatLabel;
        $this->movieTitle = $movieTitle;
        $this->showtimeAt = $showtimeAt;
        $this->roomName = $roomName;
        $this->refundAmount = $refundAmount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông báo sự cố ghế - CineGo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seat_incident',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
