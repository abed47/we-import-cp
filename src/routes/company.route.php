<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

$app->get('/company', function (Request $req, Response $res) use ($container){
    $conn   = $container->get('connection');

    try{
        $q      = "SELECT * FROM company";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        $companies = $stmt->getAll(PDO::FETCH_ASSOC);

        $respObj = [
            "status"    => true,
            "type"      => "success",
            "data"      => $companies,
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        
        $conn = null;
        $stmt = null;
        return $res->withStatus(200);
    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? $e ?? "Unknown Error"
        ];




        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;
        return $res->withStatus(500);
    }
});
$app->post('/company', function (Request $req, Response $res) use ($container) {
    try{
        $body = $req->getParsedBody();
        $conn = $container->get('connection');
        $files = $req->getUploadedFiles(); 
        $filePath = "";

        $name       = $body['name'];
        $address    = $body['address'];
        $phone      = $body['phone'];
        $country    = $body['country'];
        $email      = $body['email'];
        $website    = $body['website'];
        $client_id  = $body['client_id'];
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
        company(name, address, phone, country, email, website, photo, created_at)
        VALUES('$name', '$address', '$phone', '$country', '$email', '$website', '$filePath', '$created')");
        $transaction->execute();
        $company_id = $conn->lastInsertId();

        $q2 = "UPDATE clients SET company_id = '$company_id' WHERE id = '$client_id';";
        $stmt2 = $conn->prepare($q2);
        $stmt2->execute();

        $respObj = [
            "status"    => true,
            "results"   => $transaction ,
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