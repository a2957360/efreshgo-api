<?php
  include("../include/sql.php");
  require_once "../stripe/config.php";
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $userNumber=$data['userNumber'];

      $searchSql .= isset($userNumber)?" AND `userNumber`='$userNumber'":"";

      $balanceList = array();
      $stmt = $pdo->prepare("SELECT * From `balanceTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['balanceNumber']=$row['balanceId'];
          $balanceList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$balanceList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['balanceNumber'])){
      $balanceNumber=$data['balanceNumber'];
      foreach ($balanceNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `balanceTable`WHERE `balanceId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }
    if($data['userNumber'] == "" || $data['balancePrice'] == ""){
      exit();
    }

    //添加
    $userNumber=$data['userNumber'];
    $balancePrice=$data['balancePrice'];
    $paymentType=$data['paymentType'];
    $balanceType ='0';//添加

    //信用卡支付
    if($data['paymentType'] == "CreditCard"){
      $userNumber = $data['userNumber'];
      $cardId = $data['cardId'];
      $money = (float)$data['balancePrice'] * 100;

      $userNumber=$data['userNumber'];
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
      echo $userNumber;
      try {
        $charge = \Stripe\PaymentIntent::create([
            'amount' => $money,
            'currency' => 'cad',
            'customer' => $userStripeToken,
            'payment_method' => $cardId,
            'error_on_requires_action' => true,
            'confirm' => true,
        ]);
        $charge = json_encode($charge, JSON_UNESCAPED_UNICODE);
        $charge = json_decode($charge, true);
        // var_dump($charge['id']);
        $orderStripeToken = $charge['paymentId'];

      } catch(\Stripe\Exception\CardException $e) {
        $message['message'] = "fail";
        echo json_encode($message);
        exit();
      } 
      catch (Exception $e) {
        $message = array();
        $message['message'] = "fail";
        echo json_encode($message);
      }
    }
    
    //添加
    $stmt = $pdo->prepare("INSERT INTO `balanceTable`(`userNumber`,`balancePrice`,`paymentType`,`balanceType`) 
                          VALUES ('$userNumber','$balancePrice','$paymentType','$balanceType')");
    $stmt->execute();
    if($stmt != null){
      $stmt = $pdo->prepare("UPDATE `userTable` SET `userBalance` = `userBalance` + '$balancePrice' 
                            WHERE `userId` = '$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
