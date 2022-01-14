<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  // include("creditPayment.php");
  include("checkUserBlock.php");
  include("google.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $language=$data['language'];
    $orderStateList = ORDER_STATE;
    $orderButtonStateList = ORDER_BUTTON_STATE;
    $deliverTypeList = DELIVER_TYPE;
    $storeDriverButton = ORDER_STORE_DRIVER_BUTTON_STATE;

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $orderNumber=$data['orderNumber'];
      $userNumber=$data['userNumber'];
      $driverNumber=$data['driverNumber'];
      $storeNumber=$data['storeNumber'];
      $deliverType=$data['deliverType'];
      //"0"=>"未付款","1"=>"已付款","2"=>"待接单","3"=>"备货中","4"=>"待取货","5"=>"配送中","6"=>"待收货","7"=>"待评价","8"=>"已完成","9"=>"申请退款","10"=>"已退款","11"=>"拒绝退款"
      //wait = "1"=>"已付款","2"=>"待接单","3"=>"备货中","4"=>"待取货","5"=>"配送中"
      $orderState=$data['orderState'];

      $searchSql .= isset($orderNumber)?" AND `orderId`='$orderNumber'":"";
      $searchSql .= isset($userNumber)?" AND `orderTable`.`userNumber`='$userNumber'":"";
      $searchSql .= isset($storeNumber)?" AND `orderTable`.`storeNumber`='$storeNumber'":"";
      $searchSql .= isset($driverNumber)?" AND `orderTable`.`driverNumber`='$driverNumber'":"";
      switch ($orderState) {
        case 'wait':
          $searchSql .= isset($orderState)?" AND `orderTable`.`orderState` IN (1,2,3,4,5)":"";
          break;
        case 'history':
          $searchSql .= isset($orderState)?" AND `orderTable`.`orderState` >= '6'":"";
          break;
        case '2':
          $searchSql .= isset($orderState)?" AND `orderTable`.`orderState`='$orderState' AND `orderTable`.`deliverType` != '0'":"";
          break;
        default:
          $searchSql .= isset($orderState)?" AND `orderTable`.`orderState`='$orderState'":"";
          break;
      }
      // if((string)$orderState == "wait"){
      //   $searchSql .= isset($orderState)?" AND `orderTable`.`orderState` IN (1,2,3,4,5)":"";
      // }else{
      //   $searchSql .= isset($orderState)?" AND `orderTable`.`orderState`='$orderState'":"";
      // }
      $orderList = array();
      $stmt = $pdo->prepare("SELECT `orderTable`.*,`orderTable`.`createTime` AS `orderCreateTime`,
                            `storeTable`.`storeAddress`,`storeTable`.`storeName`,`storeTable`.`storePhone`,`storeTable`.`storeRate` AS `storeOverAllRate`
                            ,`userTable`.`userName`,`userTable`.`userPhone`,`reviewTable`.*
                            ,`driverTable`.`driverName`,`driverUserInfo`.`userPhone` AS `driverPhone`,`driverUserInfo`.`userRate` AS `driverOverAllRate` From `orderTable`
                            LEFT JOIN `storeTable` ON `orderTable`.`storeNumber` = `storeTable`.`storeNumber` AND `storeTable`.`language` = '$language'
                            LEFT JOIN `userTable` ON `userTable`.`userId` = `orderTable`.`userNumber`
                            LEFT JOIN `driverTable` ON `driverTable`.`driverId` = `orderTable`.`driverNumber`
                            LEFT JOIN `userTable` `driverUserInfo` ON `driverUserInfo`.`userId` = `driverTable`.`userNumber`
                            LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId`
                             WHERE 1 ".$searchSql."ORDER BY `orderTable`.`createTime` DESC");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['orderNumber'] = $row['orderId'];
          $row['itemList'] = json_decode($row['itemList'],true);
          // var_dump($row['itemList']);
          $row['orderStateTitle'] = $orderStateList[$language][$row['orderState']];
          $row['deliverTypeTile'] = $deliverTypeList[$language][$row['deliverType']];
          $row['orderButtonText'] = $orderButtonStateList[$language][$row['orderState']];
          $expectDeliverTime = $row['expectDeliverTime'];
          $expectDeliverTime = date("m/d h:iA",strtotime($expectDeliverTime)); 
          $row['expectDeliverTimeDisplay'] = $expectDeliverTime;
          //查看历史订单 商家骑手按钮文字
          // if((isset($driverNumber) || isset($storeNumber)) && $row['orderState'] >= 5){
          //   $row['orderButtonText'] = $storeDriverButton[$language][$row['orderState']];
          // }
          $row['driverForUserRate'] = isset($row['driverForUserRate'])?$row['driverForUserRate']:0;
          $row['storeForUserRate'] = isset($row['storeForUserRate'])?$row['storeForUserRate']:0;
          //退款图片
		  $row["refundImage"] = json_decode($row["refundImage"], true);

	      //返回订单产品
	      // $tmpItemList = array();
       //  $totalQuantity = 0;
	      // foreach ($row['itemList'] as $key => $value) {
       //    $totalQuantity = (float)$totalQuantity + (float)$value['itemQuantity'];
	      // 	$itemNumber = $value['itemNumber'];
		     //  $substmt = $pdo->prepare("SELECT * From `itemTable` WHERE `itemNumber` = '$itemNumber' AND `language` = '$language'");
		     //  $substmt->execute();
		     //  if($substmt != null){
		     //    while($subrow=$substmt->fetch(PDO::FETCH_ASSOC)){
		     //      $subrow["itemImages"] = json_decode($subrow["itemImages"], true);
		     //      $subrow["itemTag"] = explode(",",$subrow["itemTag"]);
		     //      $subrow["itemCategory"] =explode(",",$subrow["itemCategory"]);
		     //      $subrow["itemParentCategory"] = explode(",",$subrow["itemParentCategory"]);
		     //      $subrow["itemQuantity"] = $value['itemQuantity'];
		     //      $subrow["itemDescription"] = json_decode($subrow["itemDescription"],true);
		     //      $tmpdescription = array();
		     //      foreach ($subrow["itemDescription"]['blocks'] as $key => $value) {
		     //        if($value['type'] == "atomic"){
		     //          $tmpimageurl = $subrow["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
		     //          $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
		     //        }else{
		     //          $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
		     //        }
		     //      }
		     //      $subrow["itemDescription"] = $tmpdescription;

		     //      $tmpItemList[] = $subrow;
		     //    }
		     //  }else{
		     //      echo json_encode(["message"=>"database error"]);
		     //      exit();
		     //  }
	      // }
	      // $row['itemList'] = $tmpItemList;
        $totalQuantity = 0;
        foreach ($row['itemList'] as $key => $value) {
          $totalQuantity = (float)$totalQuantity + (float)$value['itemQuantity'];
        }
        $row['totalQuantity'] = round($totalQuantity);
        $orderList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      echo json_encode(["message"=>"success","data"=>$orderList]);
      exit();
    }



    //删除
    if(isset($data['isDelete']) && isset($data['orderNumber'])){
      $orderNumber=$data['orderNumber'];
      foreach ($orderNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `orderTable` WHERE `orderId` = '$value' AND `orderState` = '0'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }
    //添加/修改
    $orderNumber=$data['orderNumber'];
    // $itemList=json_encode($_POST['itemList'], JSON_UNESCAPED_UNICODE);
    $userNumber=$data['userNumber'];
    $orderUserName=$data['orderUserName'];
    $orderUserPhone=$data['orderUserPhone'];
    $storeNumber=$data['storeNumber'];
    $driverNumber=$data['driverNumber'];
    $orderAddress=$data['orderAddress'];
    $deliverType=$data['deliverType'];
    $expectDeliverTime=$data['expectDeliverTime'];
    $storeReadyTime=$data['storeReadyTime'];
    $driverPickupTime=$data['driverPickupTime'];
    $driverDeliverTime=$data['driverDeliverTime'];
    $itemPrice=$data['itemPrice'];
    $deliverPrice=$data['deliverPrice'];
    $couponPrice=$data['couponPrice'];
    $orderTax=$data['orderTax'];
    $totalPrice=$data['totalPrice'];
    $paymentType=$data['paymentType'];
    $orderState=$data['orderState'];
    $orderComent=$data['orderComent'];
    $language=$data['language'];

    //判断block
    $result = checkUser($pdo,$userNumber);
    if($result == 0){
      echo json_encode(["message"=>"block"]);
      exit();
    }
    
    //判断用户是否存在，获取用户信息
    $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        if(!isset($row['userId']) || $row['userId'] == "" || $row['userId'] == null){
          echo json_encode(["message"=>"no user"]);
          exit();
        }
        $userBalance = $row['userBalance'];
      }
    }else{
      echo json_encode(["message"=>"database error"]);
      exit();
    }
    //用来判断下单时间
    $deliverTime = date("Y-m-d",strtotime($expectDeliverTime));
    $today = date("Y-m-d");

    //处理订单产品
    $userNumber=$data['userNumber'];
    $cartList = array();
    $stmt = $pdo->prepare("SELECT * From `cartTable` WHERE `userNumber` = '$userNumber'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        $row['cartNumber'] = $row['cartId'];
        $cartList = $row['cartList'] != ""?json_decode($row['cartList'],true):array();
      }
    }else{
        echo json_encode(["message"=>"database error"]);
        exit();
    }
    $tmpitemList=$cartList;
    $itemList=array();
    $outofstock=array();
    foreach ($tmpitemList as $key => $value) {
      $itemNumber = $value['itemNumber'];
      // $stmt = $pdo->prepare("SELECT *,`itemDisplayPrice` * `isTaxable` * 0.01 AS `itemTax`
      //                       From `itemTable` 
      //                       LEFT JOIN `stockTable` ON `itemTable`.`itemNumber` = `stockTable`.`itemNumber` AND `stockTable`.`storeNumber` = '$storeNumber'
      //                       WHERE `itemTable`.`itemNumber` = '$itemNumber' AND `language` = '$language'");
      $stmt = $pdo->prepare("SELECT *,`itemDisplayPrice` * `isTaxable` * 0.01 AS `itemTax`
                            From `itemTable` 
                            WHERE `itemTable`.`itemNumber` = '$itemNumber' AND `language` = '$language'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['itemNumber'] = $value['itemNumber'];
          $row["itemImages"] = json_decode($row["itemImages"], true);
          $row['itemQuantity'] = $value['itemQuantity'];

          $row["itemDescription"] = json_decode($row["itemDescription"],true);
          $tmpdescription = array();
          foreach ($row["itemDescription"]['blocks'] as $key => $value) {
            if($value['type'] == "atomic"){
              $tmpimageurl = $row["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
              $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
            }else{
              $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
            }
          }
          $row["itemDescription"] = $tmpdescription;
          // $row['stockForSell'] = (int)$row['stockForSell'] - (int)$value['quantity'];
          // if($deliverTime != $today){
          //   $row["stockForSell"] = -1;
          // }else{
          //   if($row['stockForSell'] <= 0){
          //     $outofstock[] = $row;
          //   }
          // }
          $itemList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
    }

    //没有库存了
    // if(count($outofstock) > 0){
    //   echo json_encode(["message"=>"out of stock","data"=>$outofstock]);
    //   exit();
    // }

    //购物车计算价格 产品已经在上面处理了
    if(isset($data['isGetPrice']) && $data['isGetPrice'] !== ""){
      $userGeometry=$data['userGeometry'];
      $storeGeometry=$data['storeGeometry'];
      $deliverType=$data['deliverType'];
      if(is_array($userGeometry) && is_array($storeGeometry)){
        $distance=(float)str_replace(" km", "", getDistance($userGeometry,$storeGeometry));
        // var_dump(getDistance($userGeometry,$storeGeometry));
      }
      $tax = 0;
      $itemSum = 0;
      $total = 0;
      $totalQuantity = 0;
      foreach ($itemList as $key => $value) {
        $tax = round((float)$tax + ((float)$value['itemTax'] * (float)$value['itemQuantity']),2);
        $itemSum = round((float)$itemSum + ((float)$value['itemDisplayPrice'] * (float)$value['itemQuantity']),2);
        $totalQuantity = round((float)$totalQuantity + (float)$value['itemQuantity'],2);
      }
      //运费：判断免运费，基础运费x(1+高峰时段附加百分比)+距离运费
      //"deliverFee":2,"minPrice":2,"minDistance":2,"busyHour":[15,19],"busyHourRate":10,"baseDeliveryFee":2
      $deliverFee = 0;
      if($distance != 0 && isset($data['userGeometry']) && isset($data['storeGeometry'])){
        $stmt = $pdo->prepare("SELECT `infoContent` From `infoTable` WHERE `infoType`= '2'");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $infoContent = json_decode($row["infoContent"], true);
            $deliverFee = $infoContent['deliverFee'];
            $minPrice = $infoContent['minPrice'];
            //基础距离 超过算距离运费
            $minDistance = $infoContent['minDistance'];
            //免运费最大距离
            $freeMaxDistance = $infoContent['freeMaxDistance'];
            $busyHour = $infoContent['busyHour'];
            $busyHourRate = $infoContent['busyHourRate'];
            $baseDeliveryFee = $infoContent['baseDeliveryFee'];

            $deliveryTime=strtotime($data['expectDeliverTime']);
            $deliveryTime = (float)date("h",$deliveryTime) + (float)date("i",$deliveryTime)/60;
            // echo $busyHour[1];
            // echo date("i",$busyHour[1]);
            $busyHour[1]=strtotime($busyHour[1]);
            $busyHour[1] = (float)date("h",$busyHour[1]) + (float)date("i",$busyHour[1])/60;
            $busyHour[0]=strtotime($busyHour[0]);
            $busyHour[0] = (float)date("h",$busyHour[0]) + (float)date("i",$busyHour[0])/60;
            //判断是否免运费 不判断距离
            // if((float)$itemSum>=(float)$minPrice && $distance <= $freeMaxDistance){
            //免运费只判断钱数不判断距离
            if((float)$itemSum>=(float)$minPrice){
              $deliverFee = 0;
            }else if($deliveryTime <= $busyHour[1] && $deliveryTime >= $busyHour[0]){
              //是否高峰时段
              //是否超过初始距离
              $distance = (float)$distance > (float)$minDistance ? (float)$distance - (float)$minDistance:0;
              $deliverFee = (float)$baseDeliveryFee * (100 + (float)$busyHourRate) * 0.01 + (float)$distance * (float)$deliverFee;
            }else{
              //是否超过初始距离
              $distance = (float)$distance > (float)$minDistance ? (float)$distance - (float)$minDistance:0;
              $deliverFee = (float)$baseDeliveryFee + (float)$distance * (float)$deliverFee;
            }
            $deliverFee = round($deliverFee,2);
          }
        }else{
          echo json_encode(["message"=>"database error"]);
          exit();
        }      
      }
      if($deliverType == 0){
        $deliverFee = 0;
      }else{
        $tax = round(($tax + (float)$deliverFee * 0.13),2);
      }
      $total = round(((float)$tax + (float)$itemSum + (float)$deliverFee),2);

      $priceList = ["orderTax"=>$tax,"itemPrice"=>$itemSum,"deliverPrice"=>$deliverFee,"totalPrice"=>(float)$total,"itemList"=>$itemList,"totalQuantity"=>$totalQuantity];
      echo json_encode(["message"=>"success", "data"=>$priceList]);
      exit();
    }

    //计算coupon
    if(isset($data['isApplyCoupon']) && $data['isApplyCoupon'] !== ""){
      $userNumber=$data['userNumber'];
      $couponNumber=$data['couponNumber'];
      $itemPrice=$data['itemPrice'];
      $deliverPrice=$data['deliverPrice'];
      $orderTax=$data['orderTax'];

      $stmt = $pdo->prepare("SELECT * From `couponTable` WHERE `couponId`= '$couponNumber'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          if($userNumber != $row['userNumber']){
            echo json_encode(["message"=>"user not match"]);
            exit();
          }
          if((float)$itemPrice < (float)$row['couponRequiredPrice']){
            echo json_encode(["message"=>"not enough"]);
            exit();
          }
          if($row['couponType'] == 1){
            $couponPrice= round(((float)$itemPrice * (float)$row['couponRate'] * 0.01),2);
          }else{
            $couponPrice = $row['couponRate'];
          }
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      $total = (float)$orderTax + (float)$itemPrice + (float)$deliverPrice - $couponPrice;
      $total = round($total,2);

      $priceList = ["orderTax"=>$orderTax,"itemPrice"=>$itemPrice,"deliverPrice"=>$deliverPrice,"couponPrice"=>$couponPrice,"totalPrice"=>(float)$total];
      echo json_encode(["message"=>"success", "data"=>$priceList]);
      exit();
    }
    //添加item
    // $tmpitemList = json_encode($tmpitemList, JSON_UNESCAPED_UNICODE);
    //保存当前产品信息
    // var_dump($itemlist);
    $tmpitemList = json_encode($itemList, JSON_UNESCAPED_UNICODE);
    //修改
    if(!isset($tmpitemList) || $tmpitemList == "" || $tmpitemList == "[]"){
      echo json_encode(["message"=>"noitem"]);
      exit();
    }
    if(isset($orderNumber) && $orderNumber !== ""){
      $cardId = $data['cardId'];
      $orderNo= date('YmdHis').$orderNumber;

      $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderNo` = '$orderNo', `itemList` = '$tmpitemList', `orderUserName` = '$orderUserName', `orderUserPhone` = '$orderUserPhone', `orderAddress` = '$orderAddress',`deliverType` = '$deliverType', `expectDeliverTime` = '$expectDeliverTime' ,`itemPrice` = '$itemPrice' ,`deliverPrice` = '$deliverPrice' ,`couponPrice` = '$couponPrice'  ,`orderTax` = '$orderTax'  ,`totalPrice` = '$totalPrice',`orderComent` = '$orderComent',`paymentType` = '$paymentType' WHERE `orderId` = '$orderNumber'");
      $stmt->execute();
      if($stmt != null){
        $stmt = $pdo->prepare("UPDATE `cartTable` SET `cartList`='' WHERE `userNumber` = '$userNumber'");
        $stmt->execute();
        echo json_encode(["message"=>"success","data"=>["orderNumber"=>$orderNumber,"orderNo"=>$orderNo,"paymentType"=>$paymentType,"cardId"=>$cardId,"userNumber"=>$userNumber]]);
        exit();
      }
      exit();
    }

    if(isset($data['isUpdate']) && $data['isUpdate'] == "1"){
      echo json_encode(["message"=>"noitem"]);
      exit();
    }

    if(!isset($storeNumber)){
      echo json_encode(["message"=>"noitem"]);
      exit();
    }
    //添加
    $createTime = date("Y-m-d H:i:s");
    $stmt = $pdo->prepare("INSERT INTO `orderTable`(`itemList`,`userNumber`,`orderUserName`,`orderUserPhone`,`storeNumber`,`driverNumber`,`orderAddress`,`deliverType`,`expectDeliverTime`,`itemPrice`,`deliverPrice`,`couponPrice`,`orderTax`,`totalPrice`,`paymentType`,`orderState`,`orderComent`,`createTime`) 
                          VALUES ('$tmpitemList','$userNumber','$orderUserName','$orderUserPhone','$storeNumber','$driverNumber','$orderAddress','$deliverType','$expectDeliverTime','$itemPrice','$deliverPrice','$couponPrice','$orderTax','$totalPrice','$paymentType','0','$orderComent','$createTime')");
    $stmt->execute();
    if($stmt != null){
      //order不需要number
      $orderId = $pdo->lastInsertId();
      $stmt = $pdo->prepare("UPDATE `cartTable` SET `cartList`='' WHERE `userNumber` = '$userNumber'");
      $stmt->execute();
      $orderNo= date('YmdHis').$orderId;
      $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderNo` = '$orderNo' 
                            WHERE `orderId` = '$orderId'");
      $stmt->execute();

      $cardId = $data['cardId'];
      //信用卡付款
      // if($paymentType == "CreditCard"){
      //   creditPayment($orderId,$totalPrice);
      // }

      // //余额付款
      // if($paymentType == "Balance"){
      //   if((float)$userBalance < (float)$totalPrice){
      //     echo json_encode(["message"=>"no enough balance"]);
      //     exit();
      //   }
      //   $stmt = $pdo->prepare("INSERT INTO `balanceTable`(`userNumber`,`balancePrice`,`balanceType`) 
      //                         VALUES ('$userNumber','$totalPrice','1')");
      //   $stmt->execute();
      //   if($stmt != null){
      //     $stmt = $pdo->prepare("UPDATE `userTable` SET `userBalance` = `userBalance` - '$totalPrice' 
      //                           WHERE `userId` = '$userNumber'");
      //     $stmt->execute();
      //   }
      // }

      echo json_encode(["message"=>"success","data"=>["orderNumber"=>$orderId,"orderNo"=>$orderNo,"paymentType"=>$paymentType,"cardId"=>$cardId,"userNumber"=>$userNumber]]);
      exit();
    }

  }
