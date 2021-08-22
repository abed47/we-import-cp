<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/partner', function (Request $req, Response $res) use ($container) {
    try{
        error_reporting(E_ALL ^ E_NOTICE);  

        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $folderPath = "uploads/";
    
        try{
            $file_tmp = $_FILES['file']['tmp_name'];
            $tmp = explode('.',$_FILES['file']['name']);
            $file_ext = strtolower(end($tmp));
            $file = $folderPath . uniqid() . '.'.$file_ext;
            move_uploaded_file($file_tmp, $file);
        }catch(Exception $e){

        }

        $name           = $_POST['name'];
        $country        = $_POST['country'];
        $streetAddress  = $_POST['streetAddress'];
        $zipCode        = $_POST['zipCode'];
        $email          = $_POST['email'];
        $phone          = $_POST['phone'];
        $officePhone    = $_POST['officePhone'];
        $extraInfo      = $_POST['extraInfo'];
        $type = 2;
        $created = date('Y-m-d H:i:s', time());

        $client = $conn->query(
            "INSERT INTO 
            clients(name, email, phone, country, address, office_line, zip_code, type, note, doc_url, created_at)
            VALUES('$name','$email','$phone','$country','$streetAddress', '$officePhone', '$zipCode', $type, '$extraInfo', '$file','$created')
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

        return $res->withStatus(200);
    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "data"   => $body,
            "message"   => $e->getMessage()
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;
        return $res->withStatus(500);
    }
    
});

$app->post('/partner/contract/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    try{

        $clientId = $args['id'];

        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $folderPath = "uploads/";
    
        $file_tmp = $_FILES['file']['tmp_name'];
        $tmp = explode('.',$_FILES['file']['name']);
        $file_ext = strtolower(end($tmp));
        $file = $folderPath . uniqid() . '.'.$file_ext;
        move_uploaded_file($file_tmp, $file);

        $updated = date('Y-m-d H:i:s', time());

        $client = $conn->query(
            "UPDATE clients
            SET doc_url = '$file',
            updated_at = '$updated'
            WHERE id = '$clientId'
            "
        );

        $respObj = [
            "status"    => true,
            "results"   => "success",
            "message"   => "successfully updated"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200);
    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "data"   => $body,
            "message"   => $e->getMessage()
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;
        return $res->withStatus(500);
    }
    
});

$app->get('/partners', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        $stmt = $conn->query('SELECT * FROM clients WHERE type = 2 AND deleted_at IS NULL ORDER BY created_at DESC');
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status" => true,
            "data" => $clients,
            "message" => "successfully retrieved",
            "pagination" => true
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

$app->put('/partners/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $body = json_decode($req->getBody(), true);
    $conn = $container->get('connection');
    $clientId = $args['id'];
    #TODO: validate

    $name = $body['name'];
    $country = $body['country'];
    $streetAddress = $body['streetAddress'];
    $zipCode = $body['zipCode'];
    $email = $body['email'];
    $phone = $body['phone'];
    $officePhone = $body['officePhone'] ?? '';
    $note = $body['note'] ?? '';
    $updated = date('Y-m-d H:i:s', time());

    try {
        $client = $conn->query(
            "UPDATE clients
            SET name = '$name', 
            email = '$email', 
            phone = '$phone', 
            country = '$country', 
            address = '$streetAddress', 
            office_line = '$officePhone', 
            zip_code = '$zipCode', 
            note = '$note',
            updated_at = '$updated'
            WHERE id = $clientId
            "
        );




        $respObj = [
            "status"    => true,
            "results"   => $body,
            "message"   => "update successful"
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

        $message = $e->getMessage();
        

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

$app->get('/partners/[{id}]', function (Request $req, Response $res, $args) use ($container) {
    $conn = $container->get('connection');

    $id = $args['id'];
    $company = null;

    try {
        $stmt = $conn->query("SELECT * FROM clients WHERE id LIKE '$id'");
        $client = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $conn->query(
            " SELECT o.*,
            (SELECT SUM(price) from items WHERE order_id = o.id) as items_total
            FROM orders o
            WHERE o.client_id = '$id'"
        );
        $clientOrders = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($client) < 1) {

            $respObj = [
                "status"    => false,
                "data"   => [],
                "message"   => "Resource not found"
            ];
        } else {
            //get company
            if(array_key_exists("company_id", $client[0]) && $client[0]['company_id']){
                $q3 = "SELECT * FROM company WHERE id = '".$client[0]['company_id']."';";
                $stmt3 = $conn->prepare($q3);
                $stmt3->execute();
                $company = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            }

            $respObj = [
                "status"    => true,
                "data"   => ["client" => $client, "orders" => $clientOrders, "company" => $company],
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


$app->get('/clients/all', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        $stmt = $conn->query('SELECT * FROM clients where deleted_at IS NULL');
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        count($clients);

        $respObj = [
            "status" => true,
            "data" => $clients,
            "message" => "successfully retrieved",
            "pagination" => true
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

$app->get('/clients', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        $stmt = $conn->query('SELECT * FROM clients WHERE type = 1 AND deleted_at IS NULL');
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        count($clients);

        $respObj = [
            "status" => true,
            "data" => $clients,
            "message" => "successfully retrieved",
            "pagination" => true
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

$app->get('/clients/[{id}]', function (Request $req, Response $res, $args) use ($container) {
    $conn = $container->get('connection');

    $id = $args['id'];

    try {
        $stmt = $conn->query("SELECT * FROM clients WHERE id LIKE '$id'");
        $client = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $conn->query(
            " SELECT o.status, 
            o.id as order_id,
            i.id as item_id,
            o.price as order_price, 
            i.price as item_price, 
            i.description as item_description,
            i.photo_url as item_img,
            i.market_place as item_market_place,
            i.link  as item_link,
            i.quantity as item_quantity,
            o.name as name
            FROM orders o 
            LEFT JOIN items i ON i.order_id = o.id
            WHERE o.client_id = '$id'"
        );
        $clientOrders = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($client) < 1) {
            $respObj = [
                "status"    => false,
                "data"   => [],
                "message"   => "Resource not found"
            ];
        } else {
            $respObj = [
                "status"    => true,
                "data"   => ["client" => $client, "orders" => $clientOrders],
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

$app->post('/clients', function (Request $req, Response $res) use ($container) {
    $body = json_decode($req->getBody(), true);
    $conn = $container->get('connection');

    #TODO: validate

    $name = $body['name'];
    $country = $body['country'];
    $streetAddress = $body['streetAddress'];
    $zipCode = $body['zipCode'];
    $email = $body['email'];
    $phone = $body['phone'];
    $homePhone = $body['homePhone'] ?? '';
    $type = $body['type'];
    $created = date('Y-m-d H:i:s', time());

    try {
        $client = $conn->query(
            "INSERT INTO 
            clients(name, email, phone, country, address, home_line, zip_code, type, created_at)
            VALUES('$name','$email','$phone','$country','$streetAddress', '$homePhone', '$zipCode', $type,'$created')
            "
        );

        $respObj = [
            "status"    => true,
            "results"   => $body,
            "message"   => "successfully retrieved"
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

        if (strpos($e->getMessage(), "Duplicate entry") !== false) {
            $message = 'email already exists';
        } else {
            $message = $e->getMessage();
        }

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

$app->delete('/clients/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $container->get('logger')->info($args["id"]);

    $id = $args["id"];
    $conn = $container->get('connection');

    if ($id == "" || !$id) {
        $res->getBody()->write(responseObject(false, null, "no user defined", true));
        return $res->withStatus(400);
    }

    try {
        $client = $conn->query("delete from clients where id = '$id'");



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

$app->put('/clients/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $body = json_decode($req->getBody(), true);
    $conn = $container->get('connection');
    $clientId = $args['id'];
    #TODO: validate

    $name = $body['name'];
    $country = $body['country'];
    $streetAddress = $body['streetAddress'];
    $zipCode = $body['zipCode'];
    $email = $body['email'];
    $phone = $body['phone'];
    $homePhone = $body['homePhone'] ?? '';
    $updated = date('Y-m-d H:i:s', time());

    try {
        $client = $conn->query(
            "UPDATE clients
            SET name = '$name', 
            email = '$email', 
            phone = '$phone', 
            country = '$country', 
            address = '$streetAddress', 
            home_line = '$homePhone', 
            zip_code = '$zipCode', 
            updated_at = '$updated'
            WHERE id = $clientId
            "
        );




        $respObj = [
            "status"    => true,
            "results"   => $body,
            "message"   => "update successful"
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

        $message = $e->getMessage();
        

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

$app->put('/convert/client/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $conn = $container->get('connection');
    $clientId = $args['id'];
    #TODO: validate
    try {
        $client = $conn->query(
            "UPDATE clients set type = 2 where id = $clientId"
        );




        $respObj = [
            "status"    => true,
            "results"   => $client,
            "message"   => "update successful"
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

        $message = $e->getMessage();
        

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

$app->put('/convert/partner/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $conn = $container->get('connection');
    $clientId = $args['id'];
    #TODO: validate
    try {
        $client = $conn->query(
            "UPDATE clients set type = 1 where id = $clientId"
        );




        $respObj = [
            "status"    => true,
            "results"   => $client,
            "message"   => "update successful"
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

        $message = $e->getMessage();
        

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(400);
    }
});

function responseObject($status, $data, $message, $toJson = false)
{

    $respObj = [
        "status" => $status,
        "data" => $data,
        "message" => $message,
    ];

    if ($toJson) {
        return json_encode($respObj);
    }

    return $respObj;
}
