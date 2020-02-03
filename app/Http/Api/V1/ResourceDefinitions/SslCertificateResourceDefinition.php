<?php

namespace App\Http\Api\V1\ResourceDefinitions;

use App\Models\SslCertificate;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class SslCertificate
 * @package App\Http\Api\V1\ResourceDefinitions
 */
class SslCertificateResourceDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(SslCertificate::class);

        $this->field('certificate')
            ->visible(true, true);

        $this->field('public_key')
            ->visible(true, true);

        $this->field('private_key')
            ->visible(true, true);

        $this->field('certificate')
            ->visible(true, true);

        $this->field('status')
            ->visible(true, true);

        $this->field('expires')
            ->datetime()
            ->visible(true, true);
    }
}
