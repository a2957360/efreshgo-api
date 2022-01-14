
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Snappay mobile payment demo</title>
</head>

<?php
	require_once( 'lib/snappay-sign-utils.php' );

	//MUST keep sign key secretly. Best to load from somewhere else, like database.
	$signKey = '7e2083699dd510575faa1c72f9e35d43';
	$app_id = '9f00cd9a873c511e';

	$trans_amount = $_REQUEST['trans_amount'];
	$payment_method = $_REQUEST['payment_method'];
	$out_order_no = $_REQUEST['out_order_no'];
	$timestamp = $_REQUEST['timestamp'];
	$notify_url = $_REQUEST['notify_url'];
	$description = $_REQUEST['description'];
	$merchant_no = $_REQUEST['merchant_no'];

	$post_data = array(
        'app_id' => $app_id,
        'format' => 'JSON',
        'charset' => 'UTF-8',
        'sign_type' => 'MD5',
        'version' => '1.0',
        'timestamp' => $timestamp,

        'method' => 'pay.h5pay',
        'merchant_no' => $merchant_no,
        'payment_method' => $payment_method,
        'out_order_no' => $out_order_no,
        'trans_amount' => $trans_amount,
        'notify_url' => $notify_url,
        'description' => $description
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

	var_dump($result);

	$result = preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;', $result);
	$result_json = json_decode($result, true);
	if($result_json['code'] === '0'){
		$h5pay_url = $result_json['data'][0]['h5pay_url'];
		//echo print_r($h5pay_url);
		header('Location: '.$h5pay_url);
		exit();
	}

?>

<body>
	
</body>
</html>
