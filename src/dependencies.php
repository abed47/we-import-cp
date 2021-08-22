<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

    $container['private_env'] = function($c){
        return $c->get('settings')['private_env'];
    };

    $container['connection'] = function($c){
        $env = $c->get('settings')['private_env'];


        $host = $env['db_host'];
        $name = $env['db_name'];
        $user = $env['db_user'];
        $pass = $env['db_pass'];

        // $connection;

        try{
            $connection = new PDO("mysql:host=$host;dbname=$name;",$user,$pass);
            $connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $e){
            echo "Connection Failed: " . $e->getMessage();
        }

        return $connection;
    };
};
