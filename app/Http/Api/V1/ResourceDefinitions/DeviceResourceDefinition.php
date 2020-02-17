<?php

namespace App\Http\Api\V1\ResourceDefinitions;

use App\Models\Device;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class DeviceResourceDefinition
 * @package App\Http\Api\V1\ResourceDefinitions
 */
class DeviceResourceDefinition extends ResourceDefinition
{
    /**
     * DeviceResourceDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Device::class);

        $this->identifier('id');

        $this->field('name')
            ->string()
            ->writeable(true, true)
            ->visible(true, true)
            ->max(64)
            ->min(3);

        $this->field('updateKey')
            ->display('key')
            ->string()
            ->visible(true, true);

        $this->field('ip')
            ->string()
            ->writeable(true, true)
            ->visible(true, true);

        $this->field('port')
            ->number()
            ->writeable(true, true)
            ->visible(true, true);

        $this->field('domain')
            ->string()
            ->visible(true, true);

        $this->field('desiredDomain')
            ->writeable(true, false);

        $this->relationship('lastCertificate', SslCertificateResourceDefinition::class)
            ->visible(true, true)
            ->display('certificate')
            ->one()
            ->expanded(Action::VIEW);

        $this->relationship('services', ServiceResourceDefinition::class)
            ->visible(true, true)
            ->many()
            ->expanded(Action::VIEW);
    }
}
