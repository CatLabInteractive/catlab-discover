<?php

namespace App\Models;

use App\Services\CloudFlareService;
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
     * @param $domain
     * @return bool
     */
    public function isUniqueDomain($domain)
    {
        $exists = Device::where('domain', '=', $domain)->exists();
        if (!$exists) {
            return true;
        }

        return false;
    }

    /**
     * Is a domain valid & can we use it in cloudflare?
     * @param $domain
     * @return bool
     * @throws \Cloudflare\API\Endpoints\EndpointException
     */
    public function isValidDomain($domain)
    {
        if (!$this->isUniqueDomain($domain)) {
            return false;
        }

        $cloudflare = new CloudFlareService();
        return $cloudflare->isValidDomainName($domain);
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

            if (self::isUniqueDomain($checkDomain)) {
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
        $yesterday = Carbon::yesterday();
        $query = $this->certificates()->whereDate('expires', '>', $yesterday);

        return !$query->exists();
    }
}
