<?php
/**
 * This file is part of the Laravel Model File Manager package.
 *
 * (c) Celestine Stephen Uko <decele2011@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
