<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HandlePercentPdfToPusher implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $percent;

    public function __construct($percent)
    {
        $this->percent = $percent;
    }
  
    public function broadcastOn()
    {
        return ['tracking-percent'];
    }
  
    public function broadcastAs()
    {
        return 'update-percent';
    }
}
