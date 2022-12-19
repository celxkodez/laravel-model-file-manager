<?php

namespace Celxkodez\LaravelModelFileManager\Events;

use Cloudinary\Api\ApiResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlysystemCloudinaryResponseLog
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $response;

    public function __construct(ApiResponse $response)
    {
        $this->response = $response;
    }
}
