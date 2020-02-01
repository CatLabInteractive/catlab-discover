<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Device
 * @package App\Models
 */
class Device extends Model
{
    /**
     *
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function (Device $device) {
            $device->updateKey = Str::random(32);
        });
    }
}
