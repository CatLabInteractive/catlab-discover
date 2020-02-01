<?php

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Laravel\Middleware\JsonApiOutput;

/*
 * API v1
 */
$routes = new RouteCollection([
    'prefix' => '/api/v1/',
    'namespace' => 'App\Http\Api\V1\Controllers'
]);

// Swagger documentation
$routes->get('description.{format?}', 'DescriptionController@description')->tag('description');

$routes->group(
    [
        'middleware' => [
            //'auth:api'
        ]
    ],
    function(RouteCollection $routes) {

        // All endpoints can have these return values
        $routes->returns()->statusCode(403)->describe('Authentication error');
        $routes->returns()->statusCode(404)->describe('Entity not found');

        // Format parameter goes for all endpoints.
        $routes->parameters()
            ->path('format')
            ->enum(['json'])
            ->describe('Output format')
            ->default('json');

        // Controllers: oauth middleware is required
        $routes->group(
            [
                'middleware' => [ ],
            ],
            function(RouteCollection $routes)
            {
                App\Http\Api\V1\Controllers\DeviceController::setRoutes($routes);

            }
        );
    }
);

return $routes;
