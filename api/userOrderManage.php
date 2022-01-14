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

    $orderNumber=isset($data['orderNumber'])?$data['orderNumber']:$_POST['orderNumber'];
    $userNumber=isset($data['userNumber'])?$data['userNumber']:$_POST['userNumber'];
    $paymentType=$data['paymentType'];

    // $storeReadyTime=$data['storeReadyTime'];
    // $driverPickupTime=$data['driverPickupTime'];
    // $driverDeliverTime=$data['driverDeliverTime'];
    //"0"=>"未付款","1"=>"已付款","2"=>"待接单","3"=>"备货中","4"=>"待取货","5"=>"配送中","6"=>"待收货","7"=>"待评价","8"=>"已完成","9"=>"申请退款","10"=>"已退款","11"=>"拒绝退款"
    $orderState=isset($data['orderState'])?$data['orderState']:$_POST['orderState'];

    //获取推送列表
    $pushlist = array();
    $stmt = $pdo->prepare("SELECT * FROM `pushTable`");
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // $row['pushContent'] = json_decode($row['pushContent'], true);
        $pushlist[$row['pushState']] = $row;
      }
    }

    $orderList = array();
    $stmt = $pdo->prepare("SELECT `orderTable`.*,`driverTable`.`userExpoToken` AS `driverToken`, `storeManagerTable`.`userExpoToken` AS `storeToken` From `orderTable` 
                          LEFT JOIN `userTable` `driverTable` ON `driverTable`.`userId` = `orderTable`.`driverNumber` 
                          LEFT JOIN `storeTable` ON `storeTable`.`storeNumber` = `orderTable`.`storeNumber` 
                          LEFT JOIN `userTable` `storeManagerTable` ON `storeManagerTable`.`userId` = `storeTable`.`managerUserNumber` 
                          WHERE `orderId`='$orderNumber'");
    $stmt->execute();
    $sqldriverNumber = 0;
    if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        	if($userNumber != $row['userNumber']){
	          echo json_encode(["message"=>"Wrong User"]);
	          exit();
        	}
            $itemList = json_decode($row['itemList'], true);
            $storeNumber = $row['storeNumber'];
            $driverNumber = $row['driverNumber'];
            $userExpoToken = $row['userExpoToken'];
            //用来更新coupon状态
            $couponNumber = $row['couponNumber'];
            $totalPrice = round($row['totalPrice']);
            //用来判断下单时间
            $expectDeliverTime = date("Y-m-d",strtotime($row['expectDeliverTime']));
            $today = date("Y-m-d");
            //用于发送推送
            $orderList = $row;
        }
    }else{
          echo json_encode(["message"=>"database error"]);
          exit();
    }

    $updateSql = "";
    //判断订单状态
    switch ($orderState) {
    	case '1':
            $orderToken = $data['orderToken'];
            //更新库存
            $tmpitemList = json_encode($itemList,JSON_UNESCAPED_UNICODE);
            //更新库存report
            $stmt = $pdo->prepare("INSERT INTO `stockReportTable`(`storeNumber`, `orderNumber`, `itemList`, `stockReportType`) 
                                                          VALUES ('$storeNumber','$orderNumber','$tmpitemList','1')");
            $stmt->execute();  
            
            foreach ($itemList as $key => $value) {
              $itemNumber = $value['itemNumber'];
              $itemQuantity = (int)$value['itemQuantity'];
              $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemSaleNum` = `itemSaleNum`+'$itemQuantity' WHERE `itemNumber` = '$itemNumber'");
              $stmt->execute();
            }

			      $updateSql .= ", `paymentType` = '$paymentType'";
            $updateSql .= $orderToken != null?", `orderToken` = '$orderToken'":"";
			break;  
      case '7':
        //更新
        $addPoints = (int)$totalPrice;
        $stmt = $pdo->prepare("UPDATE `userTable` SET `userPoint` = `userPoint` + '$addPoints' WHERE `userId` = '$userNumber' ");
        $stmt->execute();
        break;
    	case '8':
    	  $storeRate=$data['storeRate'];
		    $storeReview=$data['storeReview'];
		    $itemRate=$data['itemRate'];
		    $itemReview=$data['itemReview'];
		    $driverRate=$data['driverRate'];
		    $driverReview=$data['driverReview'];
		    $stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`, `storeRate`, `storeReview`, `itemRate`, `itemReview`, `driverRate`, `driverReview`, `refundReview`)
		    										 VALUES ('$orderNumber','$storeRate','$storeReview','$itemRate','$itemReview','$driverRate','$driverReview','$refundReview')
                             ON DUPLICATE KEY UPDATE `storeRate`='$storeRate',`storeReview`='$storeReview',`itemRate`='$itemRate',`itemReview`='$itemReview',`driverRate`='$driverRate',
                             `driverReview`='$driverReview',`refundReview`='$refundReview'");
    		$stmt->execute();

        $stmt = $pdo->prepare("UPDATE `storeTable` SET `storeRate` = (
                              select sum(`storeRate`)/count(*) from `orderTable` 
                              LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                              WHERE `orderTable`.`storeNumber` = '$storeNumber' AND `reviewTable`.`reviewId` != '') 
                              WHERE `storeNumber` = '$storeNumber'");
        $stmt->execute();

        $stmt = $pdo->prepare("UPDATE `userTable` SET `userRate` = (
                              select sum(`driverRate`)/count(*) from `orderTable` 
                              LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                              WHERE `orderTable`.`driverNumber` = '$driverNumber' AND `reviewTable`.`reviewId` != '') 
                              WHERE `userId` = (SELECT `userNumber` FROM `driverTable` WHERE `driverId` = '$driverNumber')");
        $stmt->execute();
    		//评价
    		break;
    	case '9':
    		//申请退款
        //退款照片
       //  $date = date('YmdHis');
       //  if($_FILES['refundImage']['name'] != null){
       //    $File_type = strrchr($_FILES['refundImage']['name'], '.'); 
       //    $refundImage = '../include/pic/refundImage/'.$date.rand(0,9).$File_type;
       //    $sqlrefundImage = str_replace("../", "", $refundImage);
       //    $sqlrefundImage = 'https://'.$_SERVER['SERVER_NAME']."/".$refundImage;
       //  }

    	  // $refundReview=$_POST['refundReview'];
        $refundImage = json_encode($data['refundImage'],JSON_UNESCAPED_UNICODE);
        // $refundImage=$data['refundImage'];
        $refundReview=$data['refundReview'];
    		$stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`, `refundImage`, `refundReview`)
		    										 VALUES ('$orderNumber','$refundImage','$refundReview')
		    						        ON DUPLICATE KEY UPDATE `refundImage`='$refundImage',`refundReview`='$refundReview'");
    		$stmt->execute();
        // if($_FILES['refundImage']['name'] != null){
        //   move_uploaded_file($_FILES['refundImage']['tmp_name'], $refundImage);
        // }
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
