<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

$app->post('/transactions', function (Request $req, Response $res) use ($container) {
    try{
        $body = $req->getParsedBody();
        $conn = $container->get('connection');
        $files = $req->getUploadedFiles(); 
        $filePath = "";

        $type   = $body['type'];
        $status = $body['status'];
        $amount = $body['amount'];
        $reason = $body['reason'];
        $remark = $body['remark'];
        $created = date('Y-m-d H:i:s', time());

        if(array_key_exists('file',$files)) $file = $files['file'];
        if(array_key_exists('file',$files)){
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $basename = 'tr-'.time(); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);

            $file->moveTo('uploads' . DIRECTORY_SEPARATOR . $filename);

            $filePath = $filename;
        }

        $transaction = $conn->prepare("INSERT INTO
        transactions(amount, type, status, reason, created_at, photo, remark)
        VALUES($amount, $type, $status, '$reason', '$created', '$filePath', '$remark')");
        $transaction->execute();

        $respObj = [
            "status"    => true,
            "results"   => $transaction ,
            "files"     => $filePath,
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

$app->get('/transactions', function (Request $req, Response $res) use ($container) {
    $conn = $container->get('connection');

    try {

        if(isset($_GET['type'])){
            $type = $_GET['type'];
            $stmt = $conn->query(
                "SELECT * FROM transactions WHERE type = '$type'
                 AND  deleted_at IS NULL ORDER BY created_at DESC;");
        }else{
            $stmt = $conn->query(
                'SELECT *, 
                (SELECT SUM(price) FROM items where order_id = o.id) as total_price, 
                (SELECT COUNT(*) FROM items where order_id = o.id) as 
                total_items FROM orders o WHERE deleted_at IS NULL');
        }


        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status" => true,
            "data" => $transactions,
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

$app->post('/transactions/[{id}]', function (Request $req, Response $res, array $args) use ($container) {
    try{
        $body = $req->getParsedBody();
        $conn = $container->get('connection');
        $files = $req->getUploadedFiles();

        $itemId = $args['id'];
        $type   = $body['type'];
        $status = $body['status'];
        $amount = $body['amount'];
        $reason = $body['reason'];
        $remark = $body['remark'];
        $filePath = $body['photo'];

        if(array_key_exists('file',$files)) $file = $files['file'];
        if(array_key_exists('file',$files)){
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $basename = 'tr-'.time(); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);

            $file->moveTo('uploads' . DIRECTORY_SEPARATOR . $filename);

            $filePath = $filename;
        }

        $transaction = $conn->prepare("UPDATE transactions
        SET type = $type, amount = $amount, reason = '$reason', remark = '$remark', photo = '$filePath', status = $status WHERE id = $itemId");
        $transaction->execute();

        $respObj = [
            "status"    => true,
            "results"   => $transaction ,
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

$app->delete('/transactions/[{id}]', function (Request $req, Response $res, $args) use ($container){
    $container->get('logger')->info($args["id"]);

    $id = $args["id"];
    $conn = $container->get('connection');

    if ($id == "" || !$id) {
        $res->getBody()->write(responseObject(false, null, "no transaction defined", true));
        return $res->withStatus(400);
    }

    try {
        $transaction = $conn->query("delete from transactions where id = '$id'");



        if ($transaction) {
            $res->getBody()->write(responseObject(true, $transaction, "deleted successfully", true));

            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false, "error", "error deleting the order", true));
        return $res->withStatus(400);
    } catch (PDOException $e) {
        $res->getBody()->write(responseObject(false, $e->getMessage(), $e->getMessage(), true));
        return $res->withStatus(500);
    }
});
