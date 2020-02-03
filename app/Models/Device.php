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

    /**
     * Find a unique subdomain name.
     * @return string
     */
    public function generateDomainName()
    {
        $preferredSubDomain = Str::slug($this->name);

        $preferredDomain = $preferredSubDomain . config('cloudflare.ROOT_DOMAIN');
        $tries = 0;
        while ($tries < 999) {
            if ($tries === 0) {
                $checkDomain = $preferredDomain;
            } else {
                $checkDomain = $preferredDomain . sprintf("%03d", $tries);
            }
            $tries ++;

            $exists = Device::where('domain', '=', $checkDomain)->exists();
            if (!$exists) {
                return $checkDomain;
            }
        }

        return '';
    }
}
