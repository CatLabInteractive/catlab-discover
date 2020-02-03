<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SslCertificate
 * @package App\Models
 */
class SslCertificate extends Model
{
    protected $dates = [
        'created_at',
        'updated_at',
        'expires'
    ];

    /**
     * @param $orderUrl
     * @return SslCertificate
     */
    public static function getOrCreate($orderUrl)
    {
        $existing = SslCertificate::where('order_url', '=', $orderUrl)->first();
        if ($existing) {
            return $existing;
        }

        $out = new self();
        $out->order_url = $orderUrl;

        return $out;
    }

    /**
     * @param $
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
