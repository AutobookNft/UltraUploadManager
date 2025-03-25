<?php

namespace Ultra\UploadManager\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class FileProcessingUpload implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue connection to use when broadcasting the event.
     *
     * @var string
     */
    public $connection = 'sync';

    /**
     * The name of the queue on which to place the broadcasting job.
     *
     * @var string
     */
    public $queue = 'default';


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public string $message, public string $state, public ?int $user_id, public $progress = null)
    {

        Log::channel('upload')->info('classe: FileProcessingUpdate. Method: __construct. Action: $message: ' . $message . ' $state: ' . $state . ' $user_id: ' . $user_id . ' $progress: ' . $progress);

    }


    public function broadcastAs()
    {
        return 'TestUploadEvent12345';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): Channel
    {
        // Log::channel('upload')->info('classe: FileProcessingUpdate. Method: broadcastOn. Action: dentro al metodo broadcastOn');
        // return new PrivateChannel('upload');
        return new Channel('upload');
    }


}

