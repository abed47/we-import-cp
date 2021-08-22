<?php

use Slim\App;

return function (App $app) {
    
    // $app->add(new Tuupola\Middleware\JwtAuthentication([
    //     "path" => "/",
    //     "ignore" => ["/login", "/clients","/contact"],
    //     "header" => "Authorization",
    //     "secret" => "superSecretKey1",
    //     "algorithm" => ["HS256", "HS384"],
    //     "error" => function ($response, $arguments) {
    //         $data["status"] = "error";
    //         $data["message"] = $arguments["message"];
    //         return $response
    //             ->withHeader("Content-Type", "application/json")
    //             ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    //     }
    // ]));

    // $app->add(function($req, $res, $next) {
    //     $response = $next($req, $res);

    //     return $response->withHeader('Access-Control-Allow-Origin', '*')
    //                     ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    //                     ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');;
    // });
};
