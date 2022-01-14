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
  $orderNumber = $data['orderNumber'];

  //获取订单信息
  $stmt = $pdo->prepare("SELECT * From `orderTable`
                         LEFT JOIN `userTable` ON `userTable`.`userId` = `orderTable`.`userNumber`
                         WHERE `orderId` = '$orderNumber' AND `orderState` = '0'");
  $stmt->execute();
  if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      $totalPrice = $row['totalPrice'];
      $userBalance = $row['userBalance'];
      $userNumber = $row['userNumber'];
    }
  }else{
      echo json_encode(["message"=>"database error"]);
      exit();
  }
  
  if(!isset($totalPrice)){
    echo json_encode(["message"=>"wrong order"]);
    exit();
  }

  if((float)$userBalance < (float)$totalPrice){
    echo json_encode(["message"=>"no enough balance"]);
    exit();
  }

  $stmt = $pdo->prepare("INSERT INTO `balanceTable`(`userNumber`,`balancePrice`,`balanceType`) 
                        VALUES ('$userNumber','$totalPrice','1')");
  $stmt->execute();
  if($stmt != null){
    $stmt = $pdo->prepare("UPDATE `userTable` SET `userBalance` = `userBalance` - '$totalPrice' 
                          WHERE `userId` = '$userNumber'");
    $stmt->execute();
  }

 ?>