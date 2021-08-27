<?php

use Slim\http\Response;
use Slim\http\Request;

$app->get('/accounting/init', function (Request $req, Response $res) use ($container) {

    try{

        $conn   = $container->get('connection');

        $q1 = "DELETE FROM account_groups;
        DELETE FROM account_type;
        DELETE FROM accounts;";
        $stmt1 = $conn->prepare($q1);
        $stmt1->execute();
        $stmt1 = null;

        $q      = " INSERT INTO account_groups(id, name, ledger, code, nature)
                    VALUES (1, 'Assets', 1, 1, 'c'),
                    (2, 'Liabilities', 2, 2, 'd'),
                    (3, 'Equity', 3, 3, 'c'),
                    (4, 'Expense', 4, 4, 'd');

                    INSERT INTO account_types(id, name, group_id)
                    VALUES (1, 'Current Assets', 1),
                    (2, 'Fixed Assets', 1),
                    (3, 'Current Liabilities', 2),
                    (4, 'Long-Term Liabilities', 2),
                    (5, 'Equity', 3),
                    (6, 'Operating Expenses', 4),
                    (7, 'Non-Operating Expenses', 4);

                    INSERT INTO accounts(id, name, group_id, nature, parent, code)
                    VALUES (1, 'Bank', 1, NULL, 1, 001),
                    (2, 'Receivables', 1, NULL, 1, 002),
                    (3, 'Inventory', 1, NULL, 1, 003),
                    (4, 'Advances', 1, NULL, 1, 004),
                    (5, 'Cash', 1, NULL, 1, 005),
                    (6, 'Land', 1, NULL, 2, 051),
                    (7, 'Equipment', 1, Null, 2, 052),
                    (8, 'Furniture', 1, NULL, 2, 053),
                    (9, 'A/C Payable', 2, NULL, 3, 101),
                    (10, 'Other Payables', 2, NULL, 3, 102),
                    (11, 'Bank overdraft', 2, NULL, 3, 103),
                    (12, 'Unearned Revenue', 2, NULL, 3, 104),
                    (13, 'Bank Loan', 2, NULL, 4, 151),
                    (14, 'Long-Term Debenture', 2, NULL, 4, 152),
                    (15, 'Share Capital', 3, NULL, 5, 201),
                    (16, 'Retained Earnings', 3, NULL, 5, 202),
                    (17, 'Additional Payed In Capital', 3, NULL, 5, 203),
                    (18, 'Costs Of Goods Sold', 4, NULL, 6, 301),
                    (19, 'Salaries & Wages', 4, NULL, 6, 302),
                    (20, 'Admin Expenses', 4, NULL, 6, 303),
                    (21, 'Miscellaneous', 4, NULL, 6, 304),
                    (22, 'Interest Expenses', 4, NULL, 7, 351),
                    (23, 'Loss from disposal of assets', 4, NULL, 7, 352)
                    ";
        $stmt       = $conn->prepare($q);
        $stmt->execute();

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "message"   => "initiated successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);

    }
});

$app->get('/accounting/groups', function (Request $req, Response $res) use ($container){

    try{

        $conn   = $container->get('connection');

        $q      = "SELECT * FROM account_groups";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => $results,
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }

});

$app->put('/accounting/types/[{id}]', function (Request $req, Response $res, array $args) use ($container){

    try{
        $id     = $args['id'];
        $conn   = $container->get('connection');
        $body   = $req->getParsedBody();

        $name   = $body['name'];
        $group  = $body['group'];

        if(!$name || !$group) throw new Exception("all fields are required");

        $q      = "UPDATE account_types SET name = '$name', group_id = '$group' WHERE id = '$id';";
        $stmt   = $conn->prepare($q);
        $stmt->execute();

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "message"   => "updated successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => true,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];
        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->post('/accounting/types', function (Request $req, Response $res) use ($container){

    try{
        $conn       = $container->get('connection');
        $body       = $req->getParsedBody();

        $name       = $body['name'];
        $group      = $body['group'];

        if(!$name || !$group) throw new Exception("all fields are required");

        $q      = "INSERT INTO account_types(name, group_id) VALUES('$name', '$group');";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        
        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "messages"  => "created successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->delete('/accounting/types/[{id}]', function (Request $req, Response $res, array $args) use ($container) {

    try{

        $conn       = $container->get('connection');
        $id         = $args['id'];

        $q      = "DELETE FROM account_types WHERE id = '$id'";
        $stmt   = $conn->prepare($q);
        $stmt->execute();

        $respObj = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "message"   => "deleted successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];
        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->get('/accounting/types', function (Request $req, Response $res) use ($container){

    try{

        $conn   = $container->get('connection');
        $q      = "SELECT * FROM account_types";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => $results,
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }

});

$app->get('/accounting/accounts', function (Request $req, Response $res) use ($container) {

    try{
        $conn = $container->get('connection');

        $q = "SELECT  
            a.id as id,
            a.name as name,
            a.nature as nature,
            a.code as code,
            pa.id as parent_id,
            pa.name as parent_name,
            pa.nature as parent_nature,
            ag.id as group_id,
            ag.name as group_name,
            ag.code as group_code,
            ag.ledger as group_ledger
            FROM accounts a
            LEFT JOIN account_groups ag ON ag.id = a.group_id
            LEFT JOIN accounts pa ON pa.id = a.parent;
        ";

        $stmt       = $conn->prepare($q);
        $stmt->execute();
        $results    = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj    = [
            "status"        => true,
            "type"          => "success",
            "data"          => $results,
            "message"       => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);

    }

});

$app->get('/accounting/transactions', function (Request $req, Response $res) use ($container) {

    try{
        $conn = $container->get('connection');

        $q      = "SELECT 
        t.id as id,
        t.amount as amount,
        t.reason as reason,
        t.remark as remark,
        t.type as t_type,
        t.created_at as createdAt,
        t.status as status,
        t.photo as photo,
        t.debit as debit_id,
        t.credit as credit_id,
        t.order_id as order_id,
        a.name as debit_name,
        b.name as credit_name
        FROM transactions t
        LEFT JOIN accounts a ON a.id = t.debit
        LEFT JOIN accounts b ON b.id = t.credit";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => $results,
            "message"   => "retrieved successfully"
        ];
        
        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }

});

$app->post('/accounting/transactions', function (Request $req, Response $res) use ($container) {

    try{

        $body       = $req->getParsedBody();
        $conn       = $container->get('connection');
        $files      = $req->getUploadedFiles();
        $filePath   = "";

        $created        = $body['date'];
        $amount         = $body['amount'];
        $status         = $body['status'];
        $remark         = $body['remark'];
        $credit         = $body['credit'];
        $debit          = $body['debit'];
        $orderId        = $body['orderId'] ?? NULL;

        if(array_key_exists('file', $files)) $file = $files['file'];
        if(array_key_exists('file', $files)){
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $basename = 'tr-'.time(); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);

            $file->moveTo('uploads' . DIRECTORY_SEPARATOR . $filename);

            $filePath = $filename;
        }

        $q          = "INSERT INTO transactions(amount, status, remark, debit, credit, order_id, photo, created_at)
                        VALUES('$amount', '$status', '$remark', '$debit', '$credit', '$orderId', '$filePath', '$created')";
        $stmt       = $conn->prepare($q);
        $stmt->execute();
        
        $respObj = [
            'status'    => true,
            'type'      => 'success',
            'data'      => null,
            'message'   => 'created successfully'
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);

    }
});

$app->get('/accounting/transactions/[{id}]', function (Request $req, Response $res, array $args) use($container) {

    try{
        
        $conn   = $container->get('connection');
        $id     = $args['id'];
        $q      = "SELECT 
        t.id as id,
        t.amount as amount,
        t.reason as reason,
        t.remark as remark,
        t.type as t_type,
        t.created_at as createdAt,
        t.status as status,
        t.photo as photo,
        t.debit as debit_id,
        t.credit as credit_id,
        t.order_id as order_id,
        a.name as debit_name,
        b.name as credit_name
        FROM transactions t
        LEFT JOIN accounts a ON a.id = t.debit
        LEFT JOIN accounts b ON b.id = t.credit
        WHERE t.id = '$id';";
        $stmt   = $conn->prepare($q);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => $response,
            "message"   => "retrieved successfully"
        ];
        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->delete('/accounting/transactions/[{id}]', function (Request $req, Response $res, array $args) use ($container) {

    try{

        $conn   = $container->get('connection');
        $id     = $args['id'];
        $q      = "DELETE FROM transactions WHERE id = '$id';";
        $stmt   = $conn->prepare($q);
        $stmt->execute();

        $respObj    = [
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
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }

});

$app->post('/accounting/upload', function (Request $req, Response $res) use ($container) {
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

function createDateFilter($s,$e, $c, $d){
    $z = "";

    if(!$s && !$e) return $z;

    if($s && $e && $c && $d){
        $z = " AND CAST(t.created_at AS DATE) BETWEEN CAST('$s' AS DATE) AND CAST('$e' AS DATE)";
    }else{
        $z = " WHERE CAST(t.created_at AS DATE) BETWEEN CAST('$s' AS DATE) AND CAST('$e' AS DATE)";
    }

    return $z;
};

function createAmountFilter($s,$e, $c, $d, $f, $g){
    $z = "";

    if(!$s && !$e) return $z;

    if(($s || $e) && ($c && $d) || $f || $g){
        $z = " AND amount BETWEEN '$s' AND '$e'";
    }else{
        $z = " WHERE amount BETWEEN '$s' AND '$e'";
    }

    return $z;
};

$app->post('/accounting/transactions/search', function (Request $req, Response $res) use ($container){
    try{
        
        $conn       = $container->get('connection');
        $body       = $req->getParsedBody();

        $debit          = $body['debit'];
        $credit         = $body['credit'];
        $accounts       = $body['accounts'];
        $startDate      = $body['startDate'];
        $endDate        = $body['endDate'];
        $startRange     = $body['startAmount'];
        $endRage        = $body['endAmount'];

        $dateFilter = createDateFilter($startDate, $endDate, $credit, $debit);
        $amountFilter = createAmountFilter($startRange, $endRage, $startDate, $endDate, $credit, $debit);

        //crete query string to fill later
        $accountsQuery          = "";
        $creditAccountsQuery    = "";
        $debitAccountsQuery     = "";
        $transactionsQuery      = "";

        //query results empty variables
        $accountsTotals     = null;
        $debitAccounts      = null;
        $creditAccounts     = null;
        $transactions       = null;

        for($i = 0; $i < count($accounts); $i++){
            
            if($i == 0){
                $accountsQuery = $accountsQuery . "SELECT a.name, 
                SUM((SELECT COALESCE(SUM(amount),0) from transactions WHERE debit = '" . $accounts[$i] ."') - (SELECT COALESCE(SUM(amount),0) from transactions WHERE credit = '" . $accounts[$i] ."') ) as total
                FROM accounts a WHERE a.id = ".$accounts[$i];
            }else{
                $accountsQuery = $accountsQuery . " UNION ALL SELECT a.name, 
                SUM((SELECT COALESCE(SUM(amount),0) from transactions WHERE debit = '" . $accounts[$i] ."') - (SELECT COALESCE(SUM(amount),0) from transactions WHERE credit = '" . $accounts[$i] ."') ) as total
                FROM accounts a WHERE a.id = ".$accounts[$i];
            }

        }

        //get transaction query
        if(count($debit) > 0 || count($credit)){
            $transactionsQuery = "SELECT 
            t.id as id,
            t.amount as amount,
            t.reason as reason,
            t.remark as remark,
            t.type as t_type,
            t.created_at as createdAt,
            t.status as status,
            t.photo as photo,
            t.debit as debit_id,
            t.credit as credit_id,
            a.name as debit_name,
            b.name as credit_name
            FROM transactions t
            LEFT JOIN accounts a ON a.id = t.debit
            LEFT JOIN accounts b ON b.id = t.credit";

            if(count($debit) > 0){
                for($i = 0; $i < count($debit); $i++){
                    if($i == 0){
                        $transactionsQuery = $transactionsQuery . " WHERE t.debit IN(".$debit[$i]."";
                    }else{
                        $transactionsQuery = $transactionsQuery . " ,".$debit[$i]."";
                    }
                }
                $transactionsQuery = $transactionsQuery . ") " . $dateFilter . " " . $amountFilter;
            }

            if(count($credit) > 0 && count($debit) > 0) $transactionsQuery = $transactionsQuery . " OR ";
            if(!count($debit) > 0) $transactionsQuery = $transactionsQuery . " WHERE ";
            // echo $transactionsQuery;

            if(count($credit) > 0){
                for($i = 0; $i < count($credit); $i++){
                    if($i == 0){
                        $transactionsQuery = $transactionsQuery . " t.credit IN(".$credit[$i]."";
                    }else{
                        $transactionsQuery = $transactionsQuery . " ,".$credit[$i]."";
                    }
                }
                $transactionsQuery = $transactionsQuery . ")" . " " . $dateFilter . " " . $amountFilter;
            }
        }

        //add date filter
        // $transactionsQuery = $transactionsQuery . " ". $dateFilter;

        //account calculations
        if($accountsQuery){
            $stmt1  = $conn->prepare($accountsQuery);
            $stmt1->execute();
            $accountsTotals = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            $stmt1 = null;
        }

        if($transactionsQuery){
            $stmt2  = $conn->prepare($transactionsQuery);
            $stmt2->execute();
            $transactions   = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            $stmt2  = null;
        }


        $respObj    = [
            "status"    => true,
            "type"      => "success",
            "data"      => [
                "accounts"      => $accountsTotals,
                "transactions"  => $transactions
            ],
            "query" => $transactionsQuery,
            "message"   => "retrieved successfully"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj    = [
            "status"    => true,
            "type"      => "error",
            "data"      => null,
            "query" => $debitAccountsQuery,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);
    }
});

$app->post('/accounting/transactions/[{id}]', function (Request $req, Response $res, array $args) use ($container) {

    try{

        $body       = $req->getParsedBody();
        $conn       = $container->get('connection');
        $files      = $req->getUploadedFiles();
        $id         = $args['id'];
        
        $filePath       = $body['photo'];
        $created        = $body['date'];
        $amount         = $body['amount'];
        $status         = $body['status'];
        $remark         = $body['remark'];
        $credit         = $body['credit'];
        $debit          = $body['debit'];
        $orderId        = $body['orderId'];

        if(array_key_exists('file', $files)) $file = $files['file'];
        if(array_key_exists('file', $files)){
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $basename = 'tr-'.time(); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);

            $file->moveTo('uploads' . DIRECTORY_SEPARATOR . $filename);

            $filePath = $filename;
        }

        $q          = "UPDATE transactions SET amount = '$amount',
        status      = '$status',
        remark      = '$remark',
        debit       = '$debit',
        credit      = '$credit',
        order_id    = '$orderId',
        photo       = '$filePath',
        created_at   = '$created'
        WHERE id    = '$id'";
        $stmt       = $conn->prepare($q);
        $stmt->execute();
        
        $respObj = [
            'status'    => true,
            'type'      => 'success',
            'data'      => null,
            'message'   => 'created successfully'
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(200);

    }catch(Exception $e){

        $respObj = [
            "status"    => false,
            "type"      => "error",
            "data"      => null,
            "message"   => $e->getMessage() ?? "unknown error"
        ];

        $res->getBody()->write(json_encode($respObj));
        return $res->withStatus(500);

    }
});