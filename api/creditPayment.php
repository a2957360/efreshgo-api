<?php 
  ob_start();
  require_once "../stripe/config.php";
  include("../include/sql.php");

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  $userNumber = $data['userNumber'];
  $cardId = $data['cardId'];
  $orderNumber = $data['orderNumber'];
  $money = (float)$money * 100;
  $stmt = $pdo->prepare("SELECT `userStripeToken` FROM `userTable` WHERE `userId` = '$userNumber';");
  $stmt->execute();
  $order = 0;
  if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      $userStripeToken = $row['userStripeToken'];
    }
  }else{
      echo json_encode(["message"=>"database error"]);
      exit();
  }

  $stmt = $pdo->prepare("SELECT `itemList`,`storeNumber`,`totalPrice` FROM `orderTable` WHERE `orderId` = '$orderNumber';");
  $stmt->execute();
  if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      $itemList = json_decode($row['itemList'], true);
      $tmpitemList = json_encode($itemList,JSON_UNESCAPED_UNICODE);
      $storeNumber = $row['storeNumber'];
      $totalPrice = (float)$row['totalPrice'] * 100;
    }
  }else{
      echo json_encode(["message"=>"database error"]);
      exit();
  }
  try {
      // $charge = \Stripe\Charge::create([
      //     'amount' => $money,
      //     'currency' => 'cad',
      //     'customer' => $userStripeToken,
      //     'payment_intent' => $cardId,
      // ]);
      $charge = \Stripe\PaymentIntent::create([
          'amount' => $totalPrice,
          'currency' => 'cad',
          'customer' => $userStripeToken,
          'payment_method' => $cardId,
          'error_on_requires_action' => true,
          'confirm' => true,
      ]);
      $charge = json_encode($charge, JSON_UNESCAPED_UNICODE);
      $charge = json_decode($charge, true);
      // var_dump($charge['id']);
      $orderStripeToken = $charge['id'];
      //??????order
      $stmt = $pdo->prepare("UPDATE  `orderTable` SET `orderToken` = '$orderStripeToken', `paymentType` = 'CreditCard',`orderState` = '1' WHERE `orderId` = '$orderNumber';");
      $stmt->execute();  
      //????????????report
      $stmt = $pdo->prepare("INSERT INTO `stockReportTable`(`storeNumber`, `orderNumber`, `itemList`, `stockReportType`) 
                                                    VALUES ('$storeNumber','$orderNumber','$itemList','1')");
      $stmt->execute();  
      foreach ($itemList as $key => $value) {
        $itemNumber = $value['itemNumber'];
        $itemQuantity = $value['itemQuantity'];
        $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemSaleNum` = `itemSaleNum`+'$itemQuantity' WHERE `itemNumber` = '$orderNumber';");
        $stmt->execute();  
      }
      echo json_encode(["message"=>"success"]);
      exit();
      // echo json_encode(["message"=>"success","data"=>["paymentId"=>$orderStripeToken]]);
  } catch(\Stripe\Exception\CardException $e) {
    // Since it's a decline, \Stripe\Exception\CardException will be caught
    // $message = array();
    // $message['message'] .= 'Status is:' . $e->getHttpStatus() . '\n';
    // $message['message'] .= 'Type is:' . $e->getError()->type . '\n';
    // $message['message'] .= 'Code is:' . $e->getError()->code . '\n';
    // // param is '' in this case
    // $message['message'] .= 'Param is:' . $e->getError()->param . '\n';
    // $message['message'] .= 'Message is:' . $e->getError()->message . '\n';
    $message['message'] = "fail";
    echo json_encode($message);
    exit;
  } 
  catch (Exception $e) {
    // Something else happened, completely unrelated to Stripe
    $message = array();
    $message['message'] = "fail";
    echo json_encode($message);
    exit;
  }

 ?>