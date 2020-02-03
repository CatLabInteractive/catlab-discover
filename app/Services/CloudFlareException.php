<?php


namespace App\Services;

use Throwable;

/**
 * Class CloudFlareException
 * @package App\Services
 */
class CloudFlareException extends \Exception
{
    const STATUS_CODE_DUPLICATE_RECORD = 81057;
}
