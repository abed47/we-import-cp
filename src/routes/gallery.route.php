<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/gallery/upload', function (Request $req, Response $res) use ($container) {
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
                $file = $folderPath . $_FILES['file']['name'];
                move_uploaded_file($file_tmp, $file);
            }
        }catch(Exception $e){
            echo $e->getMessage();
        }

        $type       = $_POST['type'];
        $id         = $_POST['id'];
        $created    = date('Y-m-d H:i:s', time());

        $q      = "INSERT INTO gallery(url, item_id, type, created_at) VALUES('".$_FILES['file']['name']."', '$id', '$type', '$created')";
        $stmt   = $conn->prepare($q);
        $stmt->execute();

        $respObj = [
            "status"    => true,
            "type"      => "success",
            "data"      => $_FILES['file']['name'],
            "message"   => "uploaded successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){
        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? $e ?? "Unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    } 
    

});

$app->get('/gallery/uploads/[{id}]', function(Request $req, Response $res, array $args) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $id = $args['id'];

        $q  = "SELECT * FROM gallery where item_id = '$id'";
        $stmt = $conn->prepare($q);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status"    => true,
            "type"      => "success",
            "data"      => $data,
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){
        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? $e ?? "Unknown Error"
        ];

        $res->getBody()->write(json_encode($respObj));

        return $res->withStatus(500);
    }
});

$app->delete('/gallery/uploads/[{id}]', function(Request $req, Response $res, array $args) use ($container) {
    try{
        $body = json_decode($req->getBody(), true);
        $conn = $container->get('connection');

        $id = $args['id'];
        $filePath = 'uploads/'.$body['path'];

        if($filePath && file_exists($filePath)){
            unlink($filePath);
        }

        $q  = "DELETE FROM gallery where id = '$id'";
        $stmt = $conn->prepare($q);
        $stmt->execute();
        // $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "message"   => "deleted successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){
        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => $filePath,
            "message"   => $e->getMessage() ?? $e ?? "Unknown Error"
        ];

        $res->getBody()->write(json_encode($respObj));

        return $res->withStatus(500);
    }
});