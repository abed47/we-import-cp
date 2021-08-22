<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;
return function (App $app) {
    $container = $app->getContainer();
 
    require 'routes/clients.route.php';
    require 'routes/users.route.php';
    require 'routes/auth.route.php';
    require 'routes/contact.route.php';
    require 'routes/projects.route.php';
    require 'routes/invoices.route.php';
    require 'routes/orders.route.php';
    require 'routes/transactions.route.php';
    require 'routes/dashboard.route.php';
    require 'routes/gallery.route.php';
    require 'routes/company.route.php';
    require 'routes/accounting.route.php';
    
};
