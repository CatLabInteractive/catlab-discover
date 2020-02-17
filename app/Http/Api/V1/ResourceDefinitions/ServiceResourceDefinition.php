<?php

namespace App\Http\Api\V1\ResourceDefinitions;

use App\Models\Device;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class ServiceResourceDefinition
 * @package App\Http\Api\V1\ResourceDefinitions
 */
class ServiceResourceDefinition extends ResourceDefinition
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
            ->required()
            ->writeable(true, true)
            ->visible(true, true);

        $this->field('apiToken')
            ->string()
            ->writeable(true, true);
    }
}
