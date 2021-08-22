<?php
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


$app->get('/users', function(Request $req, Response $res) use ($container){
    $conn = $container->get('connection');
    
    try{

        $stmt = $conn->query('SELECT * FROM users WHERE deleted_at IS NULL');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        

        $respObj = [
            "status" => true,
            "results" => $users,
            "message" => "successfully retrieved"
        ];

        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type','application-json');
        
    }catch(PDOException $e){
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

$app->get('/users/[{id}]', function(Request $req, Response $res, $args) use ($container){
    $conn = $container->get('connection');
    
    $id = $args['id'];

    try{
        $stmt = $conn->query("SELECT * FROM clients WHERE id LIKE $id");
        $client = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($client) < 1){
            $respObj = [
                "status"    => false,
                "results"   => [],
                "message"   => "Resource not found"
            ];
        }else{
            $respObj = [
                "status"    => true,
                "results"   => $client,
                "message"   => "successfully retrieved"
            ];
        }

        

        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type','application-json');
        
    }catch(PDOException $e){
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

$app->post('/users', function(Request $req, Response $res) use ($container){
    $body = json_decode($req->getBody(),true);
    $conn = $container->get('connection');

    #TODO: validate

    // $first_name = $body['firstName'];
    // $last_name = $body['lastName'];
    $name       = $body['name'];
    $username   = $body['username'];
    $password   = $body['password'];
    $type       = $body['type'] ?? '';
    $created    = date('Y-m-d H:i:s',time());
    
    try{
        $stmt = $conn->prepare(
            "INSERT INTO 
            users(
                name,
                username,
                password, 
                role,
                created_at)

            VALUES(
                ?,
                ?,
                ?,
                ?,
                ?
            )
            "
        );
        $user = $stmt->execute(array($name, $username, $password, $type,$created));



            $respObj = [
                "status"    => true,
                "results"   => $user,
                "message"   => "successfully created"
            ];
        

        

        $res->getBody()->write(json_encode($respObj));

        $conn = null;
        $stmt = null;

        return $res->withStatus(200)->withHeader('Content-type','application-json');
        
    }catch(PDOException $e){
        // var_dump($e->getMessage());
        $conn = null;
        $stmt = null;

        if(strpos($e->getMessage(),"Duplicate entry") !== false){
            $message = 'email already exists';
        }else{
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

$app->delete('/users/[{id}]', function(Request $req, Response $res, array $args) use ($container){
    $container->get('logger')->info($args["id"]);

    $id = $args["id"];
    $conn = $container->get('connection');

    if ($id == "" || !$id) {
        $res->getBody()->write(responseObject(false, null, "no user defined", true));
        return $res->withStatus(400);
    }

    try {
        $client = $conn->query("delete from users where id = '$id'");



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

$app->put('/users/[{id}]', function(Request $req, Response $res, array $args) use ($container){
    $body = json_decode($req->getBody(),true);
    $conn = $container->get('connection');
    $logger = $container->get('logger');
    $user_id = $args['id'];

    //TODO:validate;
    $name           = $body['name'];
    $role           = $body['type'] ?? '';
    $password       = $body['password'] ?? '';
    $username       = $body['username'] ?? '';
    $updatedAt      = date('Y-m-d H:i:s',time());

    try{
        $user       = $conn->query(
            "UPDATE users SET 
            name = '$name',
            role = '$role',
            password = '$password',
            username = '$username',
            updated_at = '$updatedAt'
            WHERE id = $user_id;
            ");
        
        if($user){
            $res->getBody()->write(responseObject(true,$user,'updated successful',true));
            return $res->withStatus(200);
        }

        $res->getBody()->write(responseObject(false,$user,'updated failed',true));
        return $res->withStatus(400);
    }catch(Exception $e){
        $res->getBody()->write(responseObject(false,$user,$e->getMessage(),true));
        return $res->withStatus(500);
    }
});