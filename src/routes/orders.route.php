<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/orders', function (Request $req, Response $res) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $folderPath = "uploads/";
    
        try{

            $file="null";
            if(isset($_FILES['file'])){
                $file_tmp = $_FILES['file']['tmp_name'];
                $tmp = explode('.',$_FILES['file']['name']);
                $file_ext = strtolower(end($tmp));
                $file = $folderPath . uniqid() . '.'.$file_ext;
                move_uploaded_file($file_tmp, $file);
            }
        }catch(Exception $e){
            echo $e->getMessage();
        }

        

        $name = $_POST['name'];
        $country = $_POST['country'];
        $description = $_POST['description'];
        $address = $_POST['address'];
        $zipCode = $_POST['zipCode'];
        $clientId = $_POST['clientId'];
        $link = $_POST['link'];
        $quantity = $_POST['quantity'];
        $created = date('Y-m-d H:i:s', time());

        $order = $conn->prepare("INSERT INTO 
        orders(client_id, status, name, address, country, zip_code, created_at, type)
        VALUES($clientId,1,'$name','$address','$country', '$zipCode', '$created', 1)");
        $order->execute();

        $orderID = $conn->lastInsertId();

        $item = $conn->prepare("INSERT INTO
        items(order_id, client_id, status, description, photo_url, link, quantity)
        VALUES($orderID, $clientId, 1, '$description', '$file', '$link', '$quantity')");
        $item->execute();

        $respObj = [
            "status"    => true,
            "results"   => ["items" => $item, "order" => $order] ,
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

$app->get('/orders', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        if(isset($_GET['status'])){
            $statuss = $_GET['status'];
            $stmt = $conn->query(
                "SELECT *, (SELECT SUM(price) FROM items 
                where order_id = o.id) as total_price, 
                (SELECT description from items i  where i.order_id = o.id LIMIT 1) as item_description,
                (SELECT COUNT(*) FROM items where order_id = o.id) as total_items 
                FROM orders o WHERE status = $statuss 
                AND  deleted_at IS NULL ORDER BY created_at DESC");
        }else{
            $stmt = $conn->query(
                'SELECT *, (SELECT SUM(price) FROM 
                items where order_id = o.id) as total_price, (SELECT COUNT(*) 
                FROM items where order_id = o.id) as total_items 
                FROM orders o WHERE deleted_at IS NULL ORDER BY created_at DESC');
        }


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

$app->put('/orders', function (Request $req, Response $res) use ($container) {
    $conn               = $container->get('connection');
    $body               = json_decode($req->getBody(), true);

    $itemId             = $body['itemId'];
    $orderId            = $body['orderId'];
    $clientId           = $body['clientId'];
    $country            = $body['country'];
    $description        = $body['description'];
    $link               = $body['link'];
    $address            = $body['address'];
    $zipCode            = $body['zipCode'];
    $status             = $body['status'];
    $name               = $body['name'];
    $selling_price      = $body['sellingPrice'];
    $quantity           = $body['quantity'];
    $updated            = date('Y-m-d H:i:s', time());

    try {
        $item = $conn->prepare(
            "UPDATE items
            SET status = '$status',
            description = '$description',
            link = '$link',
            client_id = '$clientId',
            selling_price = '$selling_price',
            quantity = '$quantity'
            WHERE id = $itemId;
            UPDATE orders
            SET name = '$name',
            address = '$address',
            zip_code = '$zipCode',
            updated_at = '$updated',
            country = '$country',
            client_id = '$clientId',
            status = '$status'
            WHERE id = $orderId;
            "
        );

        $item->execute();

        if ($item) {
            $res->getBody()->write(responseObject(true, $item, "updated successfully", true));
            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false, $item, "updated failed", true));
        return $res->withStatus(400);
    } catch (PDOException $e) {
        $res->getBody()->write(responseObject(true, $e, $e->getMessage(), true));
        return $res->withStatus(500);
    }
});

$app->delete('/orders/[{id}]', function (Request $req, Response $res, $args) use ($container){
    $container->get('logger')->info($args["id"]);

    $id = $args["id"];
    $conn = $container->get('connection');

    if ($id == "" || !$id) {
        $res->getBody()->write(responseObject(false, null, "no order defined", true));
        return $res->withStatus(400);
    }

    try {
        $client = $conn->query("delete from orders where id = '$id'");



        if ($client) {
            $res->getBody()->write(responseObject(true, $client, "deleted successfully", true));

            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false, "error", "error deleting the order", true));
        return $res->withStatus(400);
    } catch (PDOException $e) {
        $res->getBody()->write(responseObject(false, $e->getMessage(), $e->getMessage(), true));
        return $res->withStatus(500);
    }
});

$app->get('/orders/[{id}]', function (Request $req, Response $res, array $args) use ($container){
    $container->get('logger')->info($args["id"]);

    try{

        $id = $args["id"];
        $conn = $container->get('connection');
    
        $q1         = ("SELECT * FROM orders WHERE id = $id");
        $stmt1      = $conn->prepare($q1);
        $stmt1->execute();
        $order      = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
        $q2         = ("SELECT * FROM items WHERE order_id = $id");
        $stmt2      = $conn->prepare($q2);
        $stmt2->execute();
        $items      = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $q3         = "SELECT * FROM clients WHERE id = " . $order[0]['client_id'];
        $stmt3      = $conn->prepare($q3);
        $stmt3->execute();
        $client     = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => [
                "order" => $order,
                "items" => $items,
                "client"=> $client[0]
            ],
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? $e ?? "Unknown Error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }

});

function getAllOrders(){};

/*============================================================================
                            BATCH ROUTES
============================================================================== */
$app->post('/batch/order', function (Request $req, Response $res) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');    

        $clientId = $body['clientId'];
        $created = date('Y-m-d H:i:s', time());

        $order = $conn->prepare("INSERT INTO 
        orders(client_id,name, status, created_at, type)
        VALUES($clientId,(SELECT name from clients WHERE id = $clientId),1, '$created', 2)");
        $order->execute();

        $orderID = $conn->lastInsertId();

        $respObj = [
            "status"    => true,
            "results"   => $orderID ,
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

$app->post('/batch/item', function (Request $req, Response $res) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $folderPath = "uploads/";
    
        try{

            $file="null";
            if(isset($_FILES['file'])){
                $file_tmp = $_FILES['file']['tmp_name'];
                $tmp = explode('.',$_FILES['file']['name']);
                $file_ext = strtolower(end($tmp));
                $file = $folderPath . uniqid() . '.'.$file_ext;
                move_uploaded_file($file_tmp, $file);
            }
        }catch(Exception $e){
            echo $e->getMessage();
        }

        
        $link           = $_POST['link'];
        $description    = $_POST['description'];
        $quantity       = $_POST['quantity'];
        $orderID        = $_POST['orderId'];
        $clientId       = $_POST['clientId'];
        $target_price   = $_POST['target_price'] ?? "NULL";

        $item = $conn->prepare("INSERT INTO
        items(order_id, status, description, photo_url, link, quantity, client_id, target_price)
        VALUES($orderID, 1, '$description', '$file', '$link', '$quantity', '$clientId', $target_price)");
        $item->execute();

        $respObj = [
            "status"    => true,
            "results"   => ["items" => $item] ,
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

$app->put('/batch/item/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $items = $body["itemList"];
        $status = $body['status'];
        $orderId = $args["id"];
        $clientId = $body['clientId'];
        $clientName = $body['clientName'];

        $query = "";

        for($i = 0; $i < count($items); $i++){
            $description = $items[$i]['description'];
            $target_price = $items[$i]['target_price'] ?? "NULL";
            $quantity = $items[$i]['quantity'];
            $link = $items[$i]['link'];
            $itemId = $items[$i]['id'];
            $selling_price = $items[$i]['selling_price'] ?? 0;
            $query = $query . " " . "UPDATE items SET description = '$description', 
            quantity = '$quantity', 
            status = '$status', 
            client_id = '$clientId', 
            link = '$link', 
            target_price = $target_price,
            selling_price = '$selling_price'
            WHERE id = '$itemId';";
        }

        $order = $conn->prepare("UPDATE orders SET status = '$status', name = '$clientName', client_id = '$clientId'  WHERE id = '$orderId'");
        $order->execute();
        $item = $conn->prepare($query);
        $item->execute();

        $respObj = [
            "status"    => true,
            "results"   => $order ,
            "message"   => "successfully updated"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;

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

$app->get('/batch/orders', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        $stmt = $conn->query('SELECT * FROM clients WHERE type = 2 AND deleted_at IS NULL');
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

$app->get('/items/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    $conn = $container->get('connection');
    $itemId = $args['id'];
    try {


        $stmt = $conn->query("SELECT * FROM items WHERE order_id = '$itemId'");
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

$app->put('/price/item/[{id}]', function (Request $req, Response $res) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $price                  = $body['price'] ?? 0;
        $shippingAir            = $body['air_shipping_price'] ?? 0;
        $shippingSea            = $body['sea_shipping_price'] ?? 0;
        $item_id                = $body['item_id'];
        $order_id               = $body['order_id'];
        $market                 = $body['marketName'];
        $marketPhone            = $body['marketPhone'];
        $marketAddress          = $body['marketAddress'];
        $height                 = $body['height'];
        $weight                 = $body['weight'];
        $width                  = $body['width'];
        $length                 = $body['length'];
        $link                   = $body['link'];
        $description            = $body['description'];
        $status                 = $body['status'];
        

        $item = $conn->prepare("UPDATE items
        SET price = '$price', 
        market_place = '$market', 
        market_place_phone = '$marketPhone',
        market_place_address = '$marketAddress', 
        air_shipping_price = '$shippingAir', 
        sea_shipping_price = '$shippingSea',
        height = '$height',
        weight = '$weight',
        width  = '$width',
        length = '$length',
        link = '$link',
        description = '$description'
        WHERE id = '$item_id'");
        $item->execute();

        $order = $conn->prepare("UPDATE orders SET status = '$status' WHERE id = $order_id;");
        $order->execute();

        $respObj = [
            "status"    => true,
            "results"   => "" ,
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

$app->put('/price/batch/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $items = $body;

        $query = "";

        $totalPrice = 0;

        for($i = 0; $i < count($items); $i++){
            $market = $items[$i]["market_place"];
            $marketPhone = $items[$i]["market_phone"];
            $marketAddress = $items[$i]["market_address"];
            $price = $items[$i]["price"] ?? 0;
            $itemId = $items[$i]["item_id"];
            $shippingSea = $items[$i]["sea_shipping_price"] ?? 0;
            $shippingAir = $items[$i]["air_shipping_price"] ?? 0;
            $weight = $items[$i]["weight"] ?? 0;
            $width = $items[$i]["width"] ?? 0;
            $length = $items[$i]["length"] ?? 0;
            $height = $items[$i]["height"] ?? 0;
            $link = $items[$i]["link"];
            $description = $items[$i]["description"];
            $query = $query . " " . "UPDATE items SET market_place = '$market', 
            market_place_phone = '$marketPhone', 
            market_place_address = '$marketAddress', 
            price = '$price', 
            air_shipping_price = $shippingAir, 
            sea_shipping_price = $shippingSea,
            weight = '$weight',
            width = '$width',
            length = '$length',
            height = '$height',
            link = '$link',
            description = '$description'
            WHERE id = '$itemId';";
        }

        $order_id = $items[0]['order_id'];

        $order = $conn->prepare("UPDATE orders SET status = 2 where id = $order_id;");
        $order->execute();

        $item = $conn->prepare($query);
        $item->execute();

        $respObj = [
            "status"    => true,
            "results"   => $item ,
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