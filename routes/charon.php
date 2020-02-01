<?php


/*
 * Convert Charon routes to Laravel routes.
 */
$routeTransformer = new \CatLab\Charon\Laravel\Transformers\RouteTransformer();

/** @var \CatLab\Charon\Collections\RouteCollection $routeCollection */
$routeCollection = include __DIR__ . '/../app/Http/Api/V1/routes.php';
$routeTransformer->transform($routeCollection);
