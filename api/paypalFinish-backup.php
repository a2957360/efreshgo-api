<?php
  include("../include/sql.php");
  include("../include/conf/config.php");

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $orderNumber=$_GET['orderNumber'];
    $orderToken=$_GET['token'];

    $stmt = $pdo->prepare("SELECT * From `orderTable` WHERE `orderId` = '$orderNumber' AND `orderState` = '0'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        $totalPrice = $row['totalPrice'];
        $sqlorderToken=$row['orderToken'];
        // var_dump($row);
      }
    }else{
        echo json_encode(["message"=>"database error"]);
        exit();
    }
    if(!isset($totalPrice)){
      echo json_encode(["message"=>"wrong order"]);
      exit();
    }
    if($orderToken != $sqlorderToken){
      echo json_encode(["message"=>"wrong token"]);
      exit();
    }
    //修改订单状态
    $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderState`='1' WHERE `orderId` = '$orderNumber' AND `orderState` = '0'");
    $stmt->execute();


  }
?>

