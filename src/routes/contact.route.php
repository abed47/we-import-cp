<?php
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

//for mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$app->post('/contact', function(Request $req, Response $res) use ($container){
    $conn       = $container->get('connection');
    $logger     = $container->get('logger');

    $body       = json_decode($req->getBody(),true);
    $email      = $body['email'];
    $message    = $body['message'];
    $name       = $body['name'];
    
    $mail       = new PHPMailer(true);

    saveMessage($name, $email,$message,$conn,$logger);
    $conn = null;

    try{
        $mail->SMTPDebug    = 0;
        $mail->isSMTP();
        $mail->Host         = 'smtp.hostinger.com';
        $mail->SMTPAuth     = true;
        $mail->Username     = 'info@revision-lb.com';
        $mail->Password     = '#rEv@764020';
        $mail->SMTPSecure   = 'tls';
        $mail->Port         = 587;

        //recipients
        $mail->setFrom('info@revision-lb.com', 'Revision-LB');
        $mail->addAddress('info@revision-lb.com', 'Revision-LB');
        
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'new contact request';
        $mail->Body = 'a new contact request from ' . $email;
        $mail->AltBody = 'a new contact request from ' . $email;

        if($mail->send()){
            $res->getBody()->write(json_encode([
                'message'   => 'message sent successfully!',
                'status'    => true
                ]));
            return $res->withStatus(200);
        }else{
            $res->getBody()->write(json_encode([
                'message'   => 'message sending failed!',
                'status'    => false
            ]));
            return $res->withStatus(500);
        }
    }catch(Exception $e){
        $logger->error("Revision-Lb @ Contact: " . $e->getMessage());
        $res->getBody()->write(json_encode([
            'message'   => 'message sending failed!',
            'status'    => false
        ]));
        return $res->withStatus(500);
    }



    
});

function saveMessage($name, $email, $message, $connection,$logger){
    $created = date('Y-m-d H:i:s', time());
    try{
        $connection->query(
            "INSERT INTO 
            contact(name, email, message, createdAt)

            VALUES('$name','$email','$message', '$created')
            "
        );
    }catch(PDOException $e){
        $logger->error("Revision-Lb @ Contact: " . $e->getMessage());
        $logger->error("Revision-Lb @ Contact: " . $email);
        $logger->error("Revision-Lb @ Contact: " . $message);
        $logger->error("Revision-Lb @ Contact: " . $name);
    }
}

// $app->get('/clients/[{id}]', function(Request $req, Response $res, $args) use ($container){
//     $conn = $container->get('connection');
    
//     $id = $args['id'];

//     try{
//         $stmt = $conn->query("SELECT * FROM clients WHERE id LIKE $id");
//         $client = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         if(count($client) < 1){
//             $respObj = [
//                 "status"    => false,
//                 "data"   => [],
//                 "message"   => "Resource not found"
//             ];
//         }else{
//             $respObj = [
//                 "status"    => true,
//                 "data"   => $client,
//                 "message"   => "successfully retrieved"
//             ];
//         }

        

//         $res->getBody()->write(json_encode($respObj));

//         $conn = null;
//         $stmt = null;

//         return $res->withStatus(200)->withHeader('Content-type','application-json');
        
//     }catch(PDOException $e){
//         // var_dump($e->getMessage());
//         $conn = null;
//         $stmt = null;

//         $respObj = [
//             "status" => false,
//             "message" => $e->getMessage()
//         ];

//         $res->getBody()->write(json_encode($respObj));
//         return $res->withStatus(400);
        
//     }
    
// });

// $app->post('/clients', function(Request $req, Response $res) use ($container){
//     $body = json_decode($req->getBody(),true);
//     $conn = $container->get('connection');

//     #TODO: validate

//     $first_name = $body['firstName'];
//     $last_name = $body['lastName'];
//     $email = $body['email'];
//     $phone = $body['phone'] ?? '';
//     $website = $body['website'] ?? '';
//     $company = $body['company'] ?? '';
//     $work_phone = $body['workPhone'] ?? '';
//     $active = 0;
//     $type = 0;
//     $created = date('Y-m-d H:i:s',time());
    
//     try{
//         $stmt = $conn->query(
//             "INSERT INTO 
//             clients(first_name, last_name, email, phone, work_phone, website, company_name, active, type, createAt)

//             VALUES('$first_name', '$last_name', '', '$phone', '$website', '$company', '$work_phone', $active, $type, '$created')
//             "
//         );
//         $client = $stmt->execute();


//         var_dump($client);

//             $respObj = [
//                 "status"    => true,
//                 "results"   => $client,
//                 "message"   => "successfully retrieved"
//             ];
        

        

//         $res->getBody()->write(json_encode($respObj));

//         $conn = null;
//         $stmt = null;

//         return $res->withStatus(200)->withHeader('Content-type','application-json');
        
//     }catch(PDOException $e){
//         // var_dump($e->getMessage());
//         $conn = null;
//         $stmt = null;

//         if(strpos($e->getMessage(),"Duplicate entry") !== false){
//             $message = 'email already exists';
//         }else{
//             $message = $e->getMessage();
//         }

//         $respObj = [
//             "status" => false,
//             "message" => $message
//         ];

//         $res->getBody()->write(json_encode($respObj));
//         return $res->withStatus(400);
        
//     }

// });

// $app->delete('/clients/id', function(Request $req, Response $res){});

// $app->put('/clients/id', function(Request $req, Response $res){});