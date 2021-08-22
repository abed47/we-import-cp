<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Tuupola\Base62;

$app->post('/login', function (Request $req, Response $res) use ($container) {
    $body = json_decode($req->getBody(), true);
    $conn = $container->get('connection');

    $logger = $container->get('logger');

    #TODO: validate



    $email = $body['username'];
    $password = $body['password'];

    try {
        $stmt = $conn->query(
            "SELECT * FROM users WHERE username LIKE '$email' AND password LIKE '$password'"
        );
        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        if($user){

            $respObj = [
                "status"    => true,
                "results"   => $user,
                "message"   => "login success",
                "type" => "success"
            ];
    
            $res->getBody()->write(json_encode($respObj));
    
            $conn = null;
            $stmt = null;
    
            return $res->withStatus(200)->withHeader('Content-type', 'application-json');
        }

        $respObj = [
            "status" => false,
            "message" => "error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(401);


    } catch (PDOException $e) {
        // var_dump($e->getMessage());
        $conn = null;
        $stmt = null;


            $message = $e->getMessage();

        $respObj = [
            "status" => false,
            "message" => $message
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->post('/refresh',function(Request $req, Response $res) use ($container){
    $body = json_decode($req->getBody(),true);
    $conn = $container->get('connection');
    $logger = $container->get('logger');

    $rToken = $body['refreshToken'];

    try{
        $stmt = $conn->query("SELECT * FROM tokens WHERE token LIKE '$rToken';");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $conn = null;
        $stmt = null;
    
        if(count($result) > 0){
            $resObj = [
                "jwt" => generateToken($body['user'])
            ];
    
            $res->getBody()->write(json_encode($resObj));
            return $res->withStatus(200);
        }
    }catch(PDOException $e){
        $logger->error("sql error while refreshing token: " . $e->getMessage());
        return $res->withStatus(401);
    }

    return $res->withStatus(401);

});

function createRefreshToken($conn, $userId, $logger) {
    $token = uniqid();

    try{
        $stmt1 = $conn->query("SELECT * FROM tokens WHERE user_id LIKE '$userId';");
        // $stmt1->execute();
        $res = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        $stmt1 = null;

        if(count($res) > 0){
            return $res[0]['token'];
        }

        return insertRefreshToken($conn,$userId,$logger, $token);
        
    }catch(PDOException $e){
        $logger->error("Slim-Skeleton '/' create token '/' " . $e->getMessage());
        return false;
    }



    
}

function insertRefreshToken($conn, $userId, $logger, $token){
    try{
        $stmt = $conn->query("INSERT INTO tokens (user_id,token) VALUES('$userId','$token')");
        // $stmt->execute();
        $stmt = null;
        return $token;
    }catch(PDOException $e){
        $logger->error("Slim-Skeleton '/' create token '/' " . $e->getMessage());
        $stmt = null;
        return false;
    }
}

function logout($conn, $userId){

}

function generateToken($data){
    $b = new Base62();

    $now = new DateTime();
    $future = new DateTime("now +2 hours");
    $jti = $b->encode(random_bytes(16));

    $secret = "superSecretKey1";

    $payload = [
        "jti" => $jti,
        "iat" => $now->getTimeStamp(),
        "nbf" => $now->getTimeStamp(),
        "exp" => $future->getTimestamp(),
        "data" => $data
    ];

    $token = JWT::encode($payload, $secret, "HS256");
    return $token;
}