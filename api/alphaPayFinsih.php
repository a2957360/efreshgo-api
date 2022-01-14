<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  include("sendNotifition.php");

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

	$order_id=$data['partner_order_id'];

	//查询订单状态
    $partner_code=PARTNER_CODE;
    $credential_code=CREDENTIAL_CODE;

    $time=(int)(microtime(true)*1000);
    $nonce_str=rand(00000000000,99999999999);

    $valid_string = $partner_code."&".$time."&".$nonce_str."&".$credential_code;
    // $sign=strtolower(bin2hex(hash('sha256', $valid_string)));
    $sign=strtolower(hash('sha256', $valid_string));
    // echo $sign;

    $url = "https://pay.alphapay.ca/api/v1.0/gateway/partners/".$partner_code."/orders/".$order_id."?time=".$time."&nonce_str=".$nonce_str."&sign=".$sign;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: application/json','Content-Length: ' . strlen($data_string))); 
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    // date_default_timezone_set("America/Toronto");
    // var_dump(date_default_timezone_get()." ".date("Y-m-d H:i:s"));
    // var_dump($result['result_code']);

    if($result['result_code'] == "PAY_SUCCESS"){
		//修改订单状态
		$stmt = $pdo->prepare("UPDATE `orderTable` SET `orderState`='1',`orderToken` = '$order_id' WHERE `orderNo` = '$order_id' AND `orderState` = '0'");
		$stmt->execute();
    }
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
                          WHERE `orderNo` = '$order_id' GROUP BY `orderTable`.`orderId`");
    $stmt->execute();
    $sqldriverNumber = 0;
    if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $orderList = $row;
        }
    }else{
          echo json_encode(["message"=>"database error"]);
          exit();
    }
    
    //给商品添加销量
    $itemList = json_decode($orderList["itemList"], true);
    foreach ($itemList as $key => $value) {
      $itemNumber = $value['itemNumber'];
      $itemQuantity = (int)$value['itemQuantity'];
      $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemSaleNum` = `itemSaleNum`+'$itemQuantity' WHERE `itemNumber` = '$itemNumber'");
      $stmt->execute();
    }

    // 推送内容
    $pushcontent = $pushlist['1'];
    $pushcontent["orderNumber"] = $orderList['orderId'];
    $pushtarget = $pushcontent['pushTarget'];
    $pushtarget = json_decode($pushtarget, true);
    //发送推送
    foreach ($pushtarget as $value) {
      sendpush($orderList[$value],$pushcontent);
    }
	
	// $stmt = $pdo->prepare("UPDATE `orderTable` SET `orderState`='1' WHERE `orderNo` = '$order_id' AND `orderState` = '0'");
	// $stmt->execute();

?>

