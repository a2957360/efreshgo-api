<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  require_once "../stripe/config.php";
  include("sendNotifition.php");

  //paypal设置
  require 'vendor/autoload.php';
  use PayPalCheckoutSdk\Core\PayPalHttpClient;
  use PayPalCheckoutSdk\Core\ProductionEnvironment;
  use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
  use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
  use PayPalCheckoutSdk\Payments\RefundsGetRequest;

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
    $paymentType=$data['paymentType'];
    //评价
    $storeRate=$data['storeRate'];
    $storeReview=$data['storeReview'];
    $itemRate=$data['itemRate'];
    $itemReview=$data['itemReview'];
    $driverRate=$data['driverRate'];
    $driverReview=$data['driverReview'];
    //退款
    $refundReview=$data['refundReview'];
    $refundReason=$data['refundReason'];
    $refundPrice=$data['refundPrice'];

    // $storeReadyTime=$data['storeReadyTime'];
    // $driverPickupTime=$data['driverPickupTime'];
    // $driverDeliverTime=$data['driverDeliverTime'];
    //"0"=>"未付款","1"=>"已付款","2"=>"待接单","3"=>"备货中","4"=>"待取货","5"=>"配送中","6"=>"待收货","7"=>"待评价","8"=>"已完成","9"=>"申请退款","10"=>"已退款","11"=>"拒绝退款"
    $orderState=$data['orderState'];

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
    $stmt = $pdo->prepare("SELECT `orderTable`.*,
                            `driverTable`.`userExpoToken` AS `driverToken`, 
                            `storeManagerTable`.`userExpoToken` AS `storeToken`, 
                            `userTable`.`userExpoToken` AS `userToken`
                            From `orderTable` 
                          LEFT JOIN `userTable` `driverTable` ON `driverTable`.`userId` = `orderTable`.`driverNumber` 
                          LEFT JOIN `storeTable` ON `storeTable`.`storeNumber` = `orderTable`.`storeNumber` 
                          LEFT JOIN `userTable` `storeManagerTable` ON `storeManagerTable`.`userId` = `storeTable`.`managerUserNumber` 
                          LEFT JOIN `userTable` ON `userTable`.`userId` = `orderTable`.`userNumber` 
                          WHERE `orderId`='$orderNumber' GROUP BY `orderTable`.`orderId` ORDER BY `createTime` DESC");
    $stmt->execute();
    $sqldriverNumber = 0;
    if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        	// if($storeNumber != $row['storeNumber']){
	        //   echo json_encode(["message"=>"Wrong Store"]);
	        //   exit();
        	// }
            // $sqldriverNumber = $row['driverNumber'];
            $totalPrice = $row['totalPrice'];
            $paymentType = $row['paymentType'];
            $orderToken = $row['orderToken'];
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
			$updateSql .= ", `paymentType` = '$paymentType'";
			break;  
		case '2':
			//发布到骑手平台
			break;  
    	case '3':
    		//添加骑手信息到订单
            if($sqldriverNumber != 0){
              echo json_encode(["message"=>"exist driver"]);
              exit();
            }
    		$driverNumber=$data['driverNumber'];
    		break;      	
    	case '4':
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
    	case '7':
    	    $storeRate=$data['storeRate'];
		    $storeReview=$data['storeReview'];
		    $itemRate=$data['itemRate'];
		    $itemReview=$data['itemReview'];
		    $driverRate=$data['driverRate'];
		    $driverReview=$data['driverReview'];
		    $stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`, `storeRate`, `storeReview`, `itemRate`, `itemReview`, `driverRate`, `driverReview`, `refundReview`)
		    										 VALUES ('$orderNumber','$storeRate','$storeReview','$itemRate','$itemReview','$driverRate','$driverReview','$refundReview')");
    		$stmt->execute();
    		//评价
    		break;
    	case '9':
    		//申请退款
    	    $refundReview=$data['refundReview'];
    		$stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`, `refundReview`)
		    										 VALUES ('$orderNumber','$refundReview')
		    						ON DUPLICATE KEY UPDATE `refundReview`='$refundReview'");
    		$stmt->execute();
    		break;
    	case '10':
            if($refundPrice == "" || $refundPrice == null){
                $refundPrice = $totalPrice;
            }
            $updateSql .= ", `refundPrice` = '$refundPrice'";
    		$refundReason=$data['refundReason'];
    		$stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`,`refundReason`)
		    										 VALUES ('$orderNumber','$refundReason')
		    						ON DUPLICATE KEY UPDATE `refundReason`='$refundReason'");
    		$stmt->execute();
            //信用卡支付
            if($orderToken != "" && $paymentType == "CreditCard"){
              $striperefundPrice=(float)$refundPrice * 100;
              try {
                  $re = \Stripe\Refund::create([
                    'amount' => $striperefundPrice,
                    'payment_intent' => $orderToken,
                  ]);
                } catch(Exception $e) {
                    echo json_encode(["message"=>"error"]);
                    exit();
              }

            }
            //余额支付
            if($orderToken != "" && $paymentType == "Balance"){
                $stmt = $pdo->prepare("UPDATE `userTable` SET `userBalance` = `userBalance` + '$refundPrice' 
                                      WHERE `userId` = '$userNumber'");
                $stmt->execute();
            }
            //支付宝/微信支付
            if($orderToken != "" && ($paymentType == "Alipay" || $paymentType == "WeChatPay")){
                $partner_code=PARTNER_CODE;
                date_default_timezone_set("UTC");
                $time=(int)(microtime(true)*1000);
                $nonce_str=rand(00000000000,99999999999);
                $credential_code=CREDENTIAL_CODE;
                $valid_string = $partner_code."&".$time."&".$nonce_str."&".$credential_code;
                $sign=strtolower(hash('sha256', $valid_string));
                $url = "https://pay.alphapay.ca/api/v1.0/gateway/partners/".$partner_code."/orders/".$orderToken."/refunds/".$orderToken."?time=".$time."&nonce_str=".$nonce_str."&sign=".$sign;
                $fee = round($refundPrice * 100);
                $data = array("fee"=>(int)$fee);
                $data_string = json_encode($data);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: application/json','Content-Length: ' . strlen($data_string))); 
                $result = curl_exec($ch);
                $result = json_decode($result, true);
                if($result['return_code'] != "SUCCESS"){
                    echo json_encode(["message"=>"error"]);
                    exit();    
                }
 
            }
            //paypal支付
            if($orderToken != "" && $paymentType == "Paypal"){
                $clientId=PAYPAL_CLIENT_ID;
                $clientSecret=PAYPAL_SECRET;
                $environment = new ProductionEnvironment($clientId, $clientSecret);
                $client = new PayPalHttpClient($environment);
                //获取订单
                // $request = new OrdersCaptureRequest($orderToken);
                // $request->prefer('return=representation');
                // try {
                //     // Call API with your client and get a response for your call
                //     $response = $client->execute($request);
                //     // If call returns body in response, you can get the deserialized version from the result attribute of the response
                //     // print_r($response);
                // }catch (HttpException $ex) {
                // echo "!11";
                //     echo $ex->statusCode;
                //     print_r($ex->getMessage());
                // }
                // $response = $client->execute($request);
                // $captureId = $response->result->purchase_units[0]->payments->captures[0]->id;
                //退款
                $refundrequest = new CapturesRefundRequest($orderToken);
                $refundrequest->body = array(
                                                'amount' =>
                                                    array(
                                                        'value' => $refundPrice,
                                                        'currency_code' => 'CAD'
                                                    )
                                            );
                // $refundresponse = $client->execute($refundrequest);
                try {
                    // Call API with your client and get a response for your call
                    $refundresponse = $client->execute($refundrequest);
                    // If call returns body in response, you can get the deserialized version from the result attribute of the response
                    // print_r($response);
                }catch (exception $ex) {
                    // echo $ex->statusCode;
                    // print_r($ex->getMessage());
                    echo json_encode(["message"=>"paypal error"]);
                    exit();
                }
                // $captureId = $response->result->purchase_units->payments->captures[0]->id;
            }
    		//已退款
    		break;
    	case '11':
    		$refundReason=$data['refundReason'];
    		$stmt = $pdo->prepare("INSERT INTO `reviewTable`(`orderNumber`,`refundReason`)
		    										 VALUES ('$orderNumber','$refundReason')
		    						ON DUPLICATE KEY UPDATE `refundReason`='$refundReason'");
    		$stmt->execute();
    		//拒绝退款
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
