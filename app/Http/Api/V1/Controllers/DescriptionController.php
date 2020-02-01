<?php

namespace App\Http\Api\V1\Controllers;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Laravel\InputParsers\JsonBodyInputParser;
use CatLab\Charon\Swagger\SwaggerBuilder;
use CatLab\Charon\Swagger\Authentication\OAuth2Authentication;

/**
 * Class DescriptionController
 * @package App\Http\Api\V1\Controllers
 */
class DescriptionController extends Base\ResourceController
{
    const RESOURCE_DEFINITION = null;

    /**
     * DescriptionController constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return RouteCollection
     */
    public function getRouteCollection() : RouteCollection
    {
        return include __DIR__ . '/../routes.php';
    }

    /**
     * @param $format
     * @return \Illuminate\Http\Response
     * @throws \CatLab\Charon\Exceptions\RouteAlreadyDefined
     */
    public function description()
    {
        switch ($this->getRequest()->route('format')) {
            case 'txt':
            case 'text':
                return $this->textResponse();
                break;

            case 'json':
            default:
                return $this->swaggerResponse();
                break;
        }
    }

    /**
     * @return \Illuminate\Http\Response
     */
    protected function textResponse()
    {
        $routes = $this->getRouteCollection();
        return \Response::make($routes->__toString(), 200, [ 'Content-type' => 'text/text' ]);
    }

    /**
     * @return mixed
     * @throws \CatLab\Charon\Exceptions\RouteAlreadyDefined
     */
    protected function swaggerResponse()
    {
        $builder = new SwaggerBuilder(\Request::getHttpHost(), '/');

        $builder
            ->setTitle(config('charon.title'))
            ->setDescription(config('charon.description'))
            ->setContact(
                config('charon.contact.name'),
                config('charon.contact.url'),
                config('charon.contact.email')
            )
            ->setVersion('1.0');

        foreach ($this->getRouteCollection()->getRoutes() as $route) {
            $builder->addRoute($route);
        }

        return $builder->build($this->getContext());
    }
}
