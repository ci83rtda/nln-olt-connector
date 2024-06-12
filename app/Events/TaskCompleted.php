<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCompleted
{
    use Dispatchable, SerializesModels;

    public $requestId;
    public $status;
    public $message;

    /**
     * Create a new event instance.
     *
     * @param string $requestId
     * @param string $status
     * @param string $message
     * @return void
     */
    public function __construct($requestId, $status, $message)
    {
        $this->requestId = $requestId;
        $this->status = $status;
        $this->message = $message;
    }
}
