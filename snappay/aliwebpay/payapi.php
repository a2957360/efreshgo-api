
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Snappay mobile payment demo</title>
</head>

<?php
	require_once( 'lib/snappay-sign-utils.php' );

	//MUST keep sign key secretly. Best to load from somewhere else, like database.
	$signKey = '00b5269f998f17b7100a04f9a7710f48';
	$app_id = '4bc288c26666fa1a';
	$trans_currency = 'CAD';

	if(!empty($_REQUEST['signKey'])){
		$signKey = $_REQUEST['signKey']; 
	}
	if(!empty($_REQUEST['app_id'])){
		$app_id = $_REQUEST['app_id']; 
	}
	if(!empty($_REQUEST['trans_currency'])){
		$trans_currency = $_REQUEST['trans_currency']; 
	}

	$trans_amount = $_REQUEST['trans_amount'];
	$payment_method = $_REQUEST['payment_method'];
	$out_order_no = $_REQUEST['out_order_no'];
	$timestamp = $_REQUEST['timestamp'];
	$notify_url = $_REQUEST['notify_url'];
	$description = $_REQUEST['description'];
	$merchant_no = $_REQUEST['merchant_no'];

	$return_url = $_POST['return_url'];
	$browser_type = $_POST['browser_type'];

	$post_data = array(
        'app_id' => $app_id,
        'format' => 'JSON',
        'charset' => 'UTF-8',
        'sign_type' => 'MD5',
        'version' => '1.0',
        'timestamp' => $timestamp,
        'trans_currency' => $trans_currency,

        'method' => 'pay.webpay',
        'merchant_no' => $merchant_no,
        'payment_method' => $payment_method,
        'out_order_no' => $out_order_no,
        'trans_amount' => $trans_amount,
        'notify_url' => $notify_url,
        'return_url' => $return_url,
        'description' => $description,
        'browser_type' => $browser_type
    );

	$post_data_sign = snappay_sign_post_data($post_data, $signKey);

	//echo print_r($post_data_sign);

	$url = 'https://open.snappay.ca/api/gateway';

	$options = array(
		'http' => array(
		    'method'  => 'POST',
		    'header'  =>  "Content-Type: application/json\r\n"."Accept: application/json\r\n",
		    'content' => json_encode($post_data_sign)
		)
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) {  
		//Handle error  
	}

	// var_dump($result);
	
	$result_json = json_decode($result, true);

	if($result_json['code'] === '0'){
		$webpay_url = $result_json['data'][0]['webpay_url'];
		// echo print_r($webpay_url);
		// header('location:'.$webpay_url);
		// echo "<script>window.location.href='".$webpay_url."';</script>";
		echo $webpay_url;
		exit();
	}

?>

<body>
	
</body>
</html>
