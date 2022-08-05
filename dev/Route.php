<?php

namespace route;

class Router
{
    private static $routes = [];
    function add($path, $class, $method = 'GET')
    {
        array_push(self::$routes, [
            'path' => $path,
            'class' => $class,
            'method' => $method,
        ]);
    }
    function list()
    {
        return self::$routes;
    }
}
