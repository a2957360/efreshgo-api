<?php
  include("../include/sql.php");
  include("sendNotifition.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $today30 = date('Y-m-d H:i:s', strtotime("-30 minute"));

    $orderList = array();
    //已付款还超过30分钟没人接单
    $stmt = $pdo->prepare("SELECT `orderTable`.*,`storeManagerTable`.`userId` AS `driverNumber`
                            `storeManagerTable`.`userExpoToken` AS `storeToken`, 
                            `userTable`.`userExpoToken` AS `userToken`, 
                            From `orderTable` 
                          LEFT JOIN `storeTable` ON `storeTable`.`storeNumber` = `orderTable`.`storeNumber` 
                          LEFT JOIN `userTable` `storeManagerTable` ON `storeManagerTable`.`userId` = `storeTable`.`managerUserNumber` 
                          LEFT JOIN `userTable` ON `userTable`.`userId` = `orderTable`.`userNumber` 
                          WHERE `orderState`='2' AND `createTime` <= '$today30'");
    $stmt->execute();
    $sqldriverNumber = 0;
    if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $orderNumber = $row['orderId'];
          $driverNumber = $row['driverNumber'];
          // 推送内容
          $pushcontent = $pushlist[$orderState];
          $pushcontent["orderNumber"] = $orderNumber;
          $pushtarget = $pushcontent['pushTarget'];
          //发送推送
          foreach ($pushtarget as $key => $value) {
            sendpush($pushtarget,$orderList[$value]);
          }
          
          //更新订单 5配送中 商家id当做骑手id 设定为商家自送
          $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderState`='5',`driverNumber`='$driverNumber',`deliverType`='2'  WHERE `orderId`='$orderNumber'");
          $stmt->execute();
          echo json_encode(["message"=>"success"]);
          exit();
        }
    }else{
          echo json_encode(["message"=>"database error"]);
          exit();
    }


  }
