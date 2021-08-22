<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/invoices', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        $stmt = $conn->query('SELECT 
        i.id as id,
        i.client_id as client_id,
        i.amount as amount,
        i.total as total,
        c.first_name as first_name,
        c.last_name as last_name,
        p.title as title,
        i.status as status
        FROM invoices i LEFT JOIN projects p on p.id = i.project_id LEFT JOIN clients c on c.id = i.client_id');
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status" => true,
            "data" => $projects,
            "message" => "successfully retrieved"
        ];

        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type', 'application-json');
    } catch (PDOException $e) {
        // var_dump($e->getMessage());
        $conn = null;
        $stmt = null;

        $respObj = [
            "status" => false,
            "message" => $e->getMessage()
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

/*
$app->get('/clients/[{id}]', function (Request $req, Response $res, $args) use ($container) {
    $conn = $container->get('connection');

    $id = $args['id'];

    try {
        $stmt = $conn->query("SELECT * FROM clients WHERE id LIKE $id");
        $client = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($client) < 1) {
            $respObj = [
                "status"    => false,
                "data"   => [],
                "message"   => "Resource not found"
            ];
        } else {
            $respObj = [
                "status"    => true,
                "data"   => $client,
                "message"   => "successfully retrieved"
            ];
        }



        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type', 'application-json');
    } catch (PDOException $e) {
        // var_dump($e->getMessage());
        $conn = null;
        $stmt = null;

        $respObj = [
            "status" => false,
            "message" => $e->getMessage()
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});
*/

$app->post('/invoices', function (Request $req, Response $res) use ($container) {
    $body = json_decode($req->getBody(), true);
    $conn = $container->get('connection');

    #TODO: validate

    $due_date   = $body['dueDate'];
    $amount     = $body['amount'];
    $total      = $body['total'];
    $client     = $body['client'];
    $project   = $body['project'];
    $status     = $body['status'];
    $created    = date('Y-m-d H:i:s', time());

    //TODO: add currency
    try {
        $client = $conn->query(
            "INSERT INTO 
            invoices(amount, status, client_id, project_id, total, due_date, createdAt)

            VALUES('$amount', '$status', '$client', '$project', '$total', '$due_date', '$created')
            "
        );




        $respObj = [
            "status"    => true,
            "results"   => $body,
            "message"   => "successfully created"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type', 'application-json');
    } catch (PDOException $e) {
        // var_dump($e->getMessage());
        $conn = null;
        $stmt = null;

        $container->get('logger')->error($e->getMessage());

        $respObj = [
            "status" => false,
            "message" => $e->getMessage()
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->delete('/invoices/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $container->get('logger')->info($args["id"]);

    $id = $args["id"];
    $conn = $container->get('connection');

    if ($id == "" || !$id) {
        $res->getBody()->write(responseObject(false, null, "no user defined", true));
        return $res->withStatus(400);
    }

    try {
        $client = $conn->query("delete from projects where id = '$id'");



        if ($client) {
            $res->getBody()->write(responseObject(true, $client, "deleted successfully", true));

            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false, "error", "error deleting the client", true));
        return $res->withStatus(400);
    } catch (PDOException $e) {
        $res->getBody()->write(responseObject(false, $e->getMessage(), $e->getMessage(), true));
        return $res->withStatus(500);
    }
});

$app->put('/invoices/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $invoiceId   = $args['id'];
    $conn       = $container->get('connection');
    $body       = json_decode($req->getBody(), true);

    $due_date   = $body['dueDate'];
    $amount     = $body['amount'];
    $total      = $body['total'];
    $client     = $body['client'];
    $project   = $body['project'];
    $status     = $body['status'];
    $updated    = date('Y-m-d H:i:s', time());

    try {
        $client = $conn->query(
            "UPDATE invoices 
                SET `due_date`  = '$due_date',
                    `amount`    = '$amount',
                    `total`     = '$total',
                    `client_id` = '$client',
                    `project_id`= '$project',
                    `status`    = '$status',
                    `updatedAt` = '$updated'

                WHERE id = $invoiceId;
                "
        );

        if ($client) {
            $res->getBody()->write(responseObject(true, $client, "updated successfully", true));
            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false, $client, "updated failed", true));
        return $res->withStatus(400);
    } catch (PDOException $e) {
        $res->getBody()->write(responseObject(true, $e, $e->getMessage(), true));
        return $res->withStatus(500);
    }
});
