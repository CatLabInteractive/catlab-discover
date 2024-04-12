<?php

namespace App\Http\Api\V1\Controllers;

use App\Http\Api\V1\ResourceDefinitions\DeviceResourceDefinition;
use App\Models\Device;
use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use Illuminate\Http\Request;
use Artisan;

/**
 * Class DeviceController
 * @package App\Http\Api\V1\Controllers
 */
class DeviceController extends Base\ResourceController
{
    const RESOURCE_DEFINITION = DeviceResourceDefinition::class;

    const DEVICE_KEY_HEADER = 'x-deviceKey';

    const MAX_TRIES = 10;

    const SLEEP = 5;

    /**
     * @param RouteCollection $routes
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public static function setRoutes(RouteCollection $routes)
    {
        $routes->group(function(RouteCollection $routes) {

            $routes->post('devices/register', 'DeviceController@register')
                ->summary('Register a new device')
                ->parameters()->resource(DeviceResourceDefinition::class)->one()
                ->returns()->one(DeviceResourceDefinition::class);

            $routes->put('devices/{deviceId}', 'DeviceController@update')
                ->summary('Update device ip address')
                ->parameters()->resource(DeviceResourceDefinition::class)->one()
                ->parameters()->path('deviceId')->required()
                ->parameters()->header(self::DEVICE_KEY_HEADER)->describe('Device key')->required()
                ->returns()->one(DeviceResourceDefinition::class);

        })->tag('devices');
    }

    /**
     * @return \CatLab\Charon\Laravel\Models\ResourceResponse
     * @throws \CatLab\Requirements\Exceptions\RequirementValidationException
     * @throws \CatLab\Requirements\Exceptions\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function register(Request $request)
    {
        $writeContext = $this->getContext(Action::CREATE);
        $inputResource = $this->bodyToResource($writeContext);

        try {
            $inputResource->validate($writeContext);
        } catch (ResourceValidationException $e) {
            return $this->getValidationErrorResponse($e);
        }

        $entity = $this->toEntity($inputResource, $writeContext);

        // do we have a desired domain?
        if ($entity->desiredDomain) {
            // check if unique
            if (!$entity->isValidDomain($entity->desiredDomain)) {
                abort(403, 'Desired domain is not valid or not available anymore.');
            }

            $entity->domain = $entity->desiredDomain;
            unset($entity->desiredDomain);
        }

        $entity->save();

        // call the update process (might take a while)
        $this->updateDeviceKey($entity);

        // refresh entity
        $entity->refresh();

        return $this->createViewEntityResponse($entity);
    }

    /**
     * @param Request $request
     * @param $existingEntityId
     * @return \CatLab\Charon\Laravel\Models\ResourceResponse
     * @throws \CatLab\Requirements\Exceptions\RequirementValidationException
     * @throws \CatLab\Requirements\Exceptions\ValidationException
     */
    public function update(Request $request, $existingEntityId)
    {
        $existingEntity = Device::findOrFail($existingEntityId);

        $writeContext = $this->getContext(Action::EDIT);
        $inputResource = $this->bodyToResource($writeContext);

        try {
            $inputResource->validate($writeContext);
        } catch (ResourceValidationException $e) {
            return $this->getValidationErrorResponse($e);
        }

        $entity = $this->toEntity($inputResource, $writeContext, $existingEntity);

        // check access key
        $updateKey = $request->header(self::DEVICE_KEY_HEADER);
        if ($entity->updateKey !== $updateKey) {
            abort(403, 'Invalid ' . self::DEVICE_KEY_HEADER . ' provided.');
        }

        $entity->save();

        // call the update process (might take a while)
        $this->updateDeviceKey($entity);

        $entity->refresh();

        return $this->createViewEntityResponse($entity);
    }

    /**
     * @param Device $entity
     * @return void
     */
    protected function updateDeviceKey(Device $entity)
    {
        for ($i = 0; $i < self::MAX_TRIES && $entity->needsRefreshCertificate(); $i ++) {

            $exitCode = Artisan::call('devices:update', [
                'deviceId' => $entity->id
            ]);

            if ($entity->needsRefreshCertificate()) {
                sleep(self::SLEEP);
            }

        }
    }
}
