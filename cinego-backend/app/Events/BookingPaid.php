<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingPaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $total_amount;
    public $ticket_count;
    public $combo_count;

    /**
     * Create a new event instance.
     */
    public function __construct($booking)
    {
        $this->total_amount = $booking->total_amount;
        $this->ticket_count = $booking->bookingDetails()->count();
        $this->combo_count = $booking->bookingCombos()->sum('quantity');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin.dashboard'),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'BookingPaid';
    }
}
