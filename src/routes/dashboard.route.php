<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/stats/dashboard', function (Request $req, Response $res) use ($container) {
    try{
        $conn = $container->get('connection');

        $stmt = $conn->prepare(" SELECT
        (SELECT COUNT(*) from orders) AS order_count,
        (SELECT COUNT(*) from orders WHERE status = 1) AS pending_orders,
        (SELECT COUNT(*) from orders WHERE status = 2) AS otw_orders,
        (SELECT COUNT(*) from orders WHERE status = 3) AS delivered_orders,
        (SELECT SUM(amount) from transactions WHERE type = 1) AS income,
        (SELECT SUM(amount) from transactions WHERE type = 2) AS expense
        ;
        ");
        $stmt->execute();
        $totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $conn->prepare("SELECT * from transactions where type = 1;");
        $stmt2->execute();
        $income = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $stmt3 = $conn->prepare("SELECT * from transactions where type = 2;");
        $stmt3->execute();
        $expense = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $stmt4 = $conn->prepare("select COUNT(*) as count, created_at from orders where status = 3 group by created_at;");
        $stmt4->execute();
        $delivered_orders = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        $stmt5 = $conn->prepare("select COUNT(*) as count, created_at from orders where status = 1 group by created_at;");
        $stmt5->execute();
        $pending_orders = $stmt5->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status"    => true,
            "results"   => ["totals" => $totals, "income" => $income, "expense" => $expense, "delivered" => $delivered_orders, "pending" => $pending_orders],
            "message"   => "successfully created"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200);
    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "data"   => [],
            "message"   => $e->getMessage()
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;
        return $res->withStatus(500);
    }
    
});

$app->post('/stats/dashboard', function (Request $req, Response $res) use ($container) {
    try{
        $conn = $container->get('connection');
        $body = json_decode($req->getBody(), true);

        $start = $body['start'];
        $end = $body['end'];

        $stmt = $conn->prepare(" SELECT
        (SELECT COUNT(*) from orders WHERE (created_at BETWEEN '$start' AND '$end')) AS order_count,
        (SELECT COUNT(*) from orders WHERE status = 1 AND (created_at BETWEEN '$start' AND '$end')) AS pending_orders,
        (SELECT COUNT(*) from orders WHERE status = 2 AND (created_at BETWEEN '$start' AND '$end')) AS otw_orders,
        (SELECT COUNT(*) from orders WHERE status = 3 AND (created_at BETWEEN '$start' AND '$end')) AS delivered_orders,
        (SELECT SUM(amount) from transactions WHERE type = 1 AND (created_at BETWEEN '$start' AND '$end')) AS income,
        (SELECT SUM(amount) from transactions WHERE type = 2 AND (created_at BETWEEN '$start' AND '$end')) AS expense
        ;
        ");
        $stmt->execute();
        $totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $conn->prepare("SELECT * from transactions WHERE type = 1 AND (created_at BETWEEN '$start' AND '$end');");
        $stmt2->execute();
        $income = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $stmt3 = $conn->prepare("SELECT * from transactions WHERE type = 2 AND (created_at BETWEEN '$start' AND '$end');");
        $stmt3->execute();
        $expense = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $stmt4 = $conn->prepare("SELECT COUNT(*) as count, created_at from orders WHERE status = 3 AND (created_at BETWEEN '$start' AND '$end') group by created_at;");
        $stmt4->execute();
        $delivered_orders = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        $stmt5 = $conn->prepare("SELECT COUNT(*) as count, created_at from orders WHERE  status = 1 AND (created_at BETWEEN '$start' AND '$end') group by created_at;");
        $stmt5->execute();
        $pending_orders = $stmt5->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status"    => true,
            "results"   => ["totals" => $totals, "income" => $income, "expense" => $expense, "delivered" => $delivered_orders, "pending" => $pending_orders],
            "message"   => "successfully created"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200);
    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "data"   => [],
            "message"   => $e->getMessage()
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;
        return $res->withStatus(500);
    }
    
});