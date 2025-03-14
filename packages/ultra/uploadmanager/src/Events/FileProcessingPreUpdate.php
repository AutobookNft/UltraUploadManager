<?php

namespace Ultra\UploadManager\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FileProcessingPreUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public string $message, public string $state, public User $user)
    {

        $this->message = $message;
        $this->state = $state;
        $this->user = $user;

        Log::channel('upload')->info('classe: FileProcessingUpdate. Method: __construct. Action: $message: ' . $message . ' $state: ' . $state . ' $user: ' . $user->id);

    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): Channel
    {
        Log::channel('upload')->info('classe: FileProcessingPreUpdate. Method: broadcastOn. Action: dentro al metodo broadcastOn');
        return new PrivateChannel('preUpload');
    }


}

