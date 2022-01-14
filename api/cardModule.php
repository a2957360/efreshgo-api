<?php
  include("../include/sql.php");
  require_once "../stripe/config.php";
  // ini_set('display_errors',1);
  // error_reporting(E_ALL);

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cardNumber = $data['cardNumber'];
    // $cardName = $data['cardName'];
    // $cardNumber = $data['cardNumber'];
    // $expireMonth = $data['expireMonth'];
    // $expireYear = $data['expireYear'];
    // $cardCvv = $data['cardCvv'];

    $userNumber = $data['userNumber'];
    $isDelete=$data['isDelete'];

    // $token = json_encode($data['token'], JSON_UNESCAPED_UNICODE);
    $cardToken=$data['cardToken'];
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

    if (isset($data['isGet'])) {
      try {
        $cards = \Stripe\Customer::allSources(
          $userStripeToken,
          ['object' => 'card', 'limit' => 6]
        );
        } catch(Exception $e) {
          echo $e;
          exit();
      }
      $data = array();
      // $data[] = ["label"=>"Choose One","value"=>"Choose One"];
      foreach ($cards['data'] as $key => $value) {
        $tmp = ["label"=>$value['last4'],"value"=>$value['last4'],"id"=>$value['id'],"brand"=>$value['brand']];
        $data[] = $tmp;
      }
      $data[] = ["label"=>"Add credit card","value"=>""];
      echo json_encode(["data"=>$data,"message"=>"success"]);
      exit();
    }

    
    $stmt = $pdo->prepare("SELECT `userStripeToken` FROM `userInfo` WHERE `userId` = '$userNumber';");
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
    //删除银行卡
    if(isset($cardNumber) && !empty($cardNumber) && isset($isDelete) && $isDelete == "1"){
      try {
        $cards = \Stripe\Customer::deleteSource(
            $userStripeToken,
            $cardNumber,
            []
          );
      } catch(Exception $e) {
        echo $e;
        exit();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }
    // $allcard = \Stripe\Customer::allSources(
    //   $userStripeToken,
    //   ['object' => 'card', 'limit' => 3]
    // );
    // echo json_encode(["message"=>$allcard]);
    // exit();


    //添加银行卡
    try {
      $cards = \Stripe\Customer::createSource(
          $userStripeToken,
          ['source' => $cardToken]
        );
    } catch(Exception $e) {
      echo $e;
      echo json_encode(["message"=>"fail"]);
      exit();
    }
    $data=array("id"=>$cards['id'],"value"=>$cards['last4'],);
    echo json_encode(["message"=>"success","data"=>$data]);
    exit();



  }



