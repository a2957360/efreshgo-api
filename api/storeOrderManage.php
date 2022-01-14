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

    $storeNumber=$data['storeNumber'];
    $orderNumber=$data['orderNumber'];
    $driverNumber=$data['driverNumber'];

    //"0"=>"未付款","1"=>"已付款","2"=>"待接单","3"=>"备货中","4"=>"待取货","5"=>"配送中","6"=>"待收货","7"=>"待评价","8"=>"已完成","9"=>"申请退款","10"=>"已退款","11"=>"拒绝退款"
    $orderState=$data['orderState'];

    //获取推送列表
    $pushlist = array();
    $stmt = $pdo->prepare("SELECT * FROM `pushTable`");
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $pushlist[$row['pushState']] = $row;
      }
    }

    $orderList = array();
    $stmt = $pdo->prepare("SELECT `orderTable`.*,
                            `driverTable`.`userExpoToken` AS `driverToken`, 
                            `userTable`.`userExpoToken` AS `userToken`
                            From `orderTable` 
                          LEFT JOIN `userTable` `driverTable` ON `driverTable`.`userId` = `orderTable`.`driverNumber` 
                          LEFT JOIN `storeTable` ON `storeTable`.`storeNumber` = `orderTable`.`storeNumber` 
                          LEFT JOIN `userTable` `storeManagerTable` ON `storeManagerTable`.`userId` = `storeTable`.`managerUserNumber` 
                          LEFT JOIN `userTable` ON `userTable`.`userId` = `orderTable`.`userNumber` 
                          WHERE `orderId`='$orderNumber' Group BY `orderTable`.`orderId`");
    $stmt->execute();
    $sqldriverNumber = 0;
    if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        	if($storeNumber != $row['storeNumber']){
	          echo json_encode(["message"=>"wrong store"]);
	          exit();
        	}
          if(isset($driverNumber) && $driverNumber != "" && $driverNumber != $row['driverNumber'] && $row['driverNumber'] != 0 && $row['deliverType'] != 0){
            echo json_encode(["message"=>"wrong driver"]);
            exit();
          }
          $curOrderState = $row['orderState'];
          $userNumber = $row['userNumber'];
          $sqldriverNumber = $row['driverNumber'];
          //用于发送推送
          $orderList = $row;
        }
    }else{
      echo json_encode(["message"=>"database error"]);
      exit();
    }

    if(isset($data['isReview']) && $data['isReview'] != ""){
      if($curOrderState < "7"){
        echo json_encode(["message"=>"cannot review"]);
        exit();
      }
      $userRate=$data['userRate'];
      $userReview=$data['userReview']; 
      $stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`, `storeForUserRate`, `storeForUserReview`)
                             VALUES ('$orderNumber','$userRate','$userReview')
                             ON DUPLICATE KEY UPDATE `storeForUserRate`='$userRate',`storeForUserReview`='$userReview'");
      $stmt->execute();

      $stmt = $pdo->prepare("UPDATE `userTable` SET `userRate` = (
                            select sum(`userRate`)/count(*) from `orderTable` 
                            LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                            WHERE `orderTable`.`userNumber` = '$userNumber') 
                            WHERE `userId` = '$userNumber'");
      $stmt->execute();
      
      echo json_encode(["message"=>"success"]);
      exit();
    }


    $updateSql = "";
    //判断订单状态
    switch ($orderState) {

  		case '2':
  			//发布到骑手平台
  			break;  
    	// case '3':
    	// 	//商家自己配送
     //        if($sqldriverNumber != 0){
     //          echo json_encode(["message"=>"exist driver"]);
     //          exit();
     //        }
    	// 	  $driverNumber=$data['driverNumber'];
     //      $updateSql .= ", `driverNumber` = '$driverNumber'";
     //      $updateSql .= ", `deliverType` = '2'";
    	// 	break;      	
    	case '4':
          //商家自己配送
            if($sqldriverNumber != 0){
              echo json_encode(["message"=>"exist driver"]);
              exit();
            }
          $driverNumber=$data['driverNumber'];
          $updateSql .= ", `driverNumber` = '$driverNumber'";
          $updateSql .= ", `deliverType` = '2'";
    		//更新商品备货时间
    		$storeReadyTime=date("Y-m-d H:i:s");
			  $updateSql .= ", `storeReadyTime` = '$storeReadyTime'";
    		break;    	
    	case '5':
    		//更新骑手配送开始时间
    		$driverPickupTime=date("Y-m-d H:i:s");
			$updateSql .= ", `driverPickupTime` = '$driverPickupTime'";
    		break;    	
    	case '6':
    		//更新骑手配送结束时间
    		$driverDeliverTime=date("Y-m-d H:i:s");
			$updateSql .= ", `driverDeliverTime` = '$driverDeliverTime'";
    		break;
    	default:
    		# code...
    		break;
    }
    // 推送内容
    $pushcontent = $pushlist[$orderState];
    $pushcontent["orderNumber"] = $orderNumber;
    $pushtarget = $pushcontent['pushTarget'];
    $pushtarget = json_decode($pushtarget, true);
    //发送推送
    foreach ($pushtarget as $value) {
      sendpush($orderList[$value],$pushcontent);
    }

    //更新订单
    $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderState`='$orderState'".$updateSql." WHERE `orderId`='$orderNumber'");
    $stmt->execute();
    echo json_encode(["message"=>"success"]);
    exit();

  }
