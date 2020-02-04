<?php

namespace App\Models;

use Carbon\Carbon;
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
                $checkDomain = $preferredSubDomain . sprintf("%03d", $tries) . config('cloudflare.ROOT_DOMAIN');
            }
            $tries ++;

            $exists = Device::where('domain', '=', $checkDomain)->exists();
            if (!$exists) {
                return $checkDomain;
            }
        }

        return '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates()
    {
        return $this->hasMany(SslCertificate::class);
    }

    /**
     * @return mixed
     */
    public function getLastCertificate()
    {
        return $this->certificates->first();
    }

    /**
     * @return bool
     */
    public function needsRefreshCertificate()
    {
        return !$this->certificates()->where('expires', '>', Carbon::yesterday())->exists();
    }
}
