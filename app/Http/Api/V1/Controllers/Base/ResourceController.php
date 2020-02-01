<?php


namespace App\Http\Api\V1\Controllers\Base;

use App\Http\Controllers\Controller;
use CatLab\Charon\Laravel\Controllers\CrudController;
use CatLab\Charon\Laravel\Controllers\ResourceController as CharonResourceController;

/**
 * Class ResourceController
 * @package App\Http\Api\V1\Controllers\Base
 */
class ResourceController extends Controller
{
    use CharonResourceController, CrudController {
        CrudController::getRequest insteadof CharonResourceController;
    }

    /**
     * ResourceController constructor.
     * @param string $resourceDefinition
     */
    public function __construct($resourceDefinition = null)
    {
        if (isset($resourceDefinition)) {
            $this->setResourceDefinition($resourceDefinition);
        } elseif (defined('static::RESOURCE_DEFINITION')) {
            $this->setResourceDefinition(static::RESOURCE_DEFINITION);
        } else {
            $this->resourceTransformer = $this->createResourceTransformer();
            //throw new \InvalidArgumentException(static::class . ' requires a resourceDefinition.');
        }
    }
}
