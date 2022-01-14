<?php
	require_once( 'lib/snappay-sign-utils.php' );

	//MUST keep sign key secretly. Best to load from somewhere else, like database.
	$signKey = '7e2083699dd510575faa1c72f9e35d43';
	$app_id = '9f00cd9a873c511e';

	if(empty($_REQUEST['out_order_no']) || empty($_REQUEST['merchant_no'])){
		echo 'order no and merchant no can\'t be null'; 
	}else{
		$out_order_no = $_REQUEST['out_order_no'];
		$merchant_no = $_REQUEST['merchant_no'];

		if(!empty($_REQUEST['sign_key'])){
			$signKey = $_REQUEST['sign_key'];
		}

		// Must be UTC time here
		$timestamp = date_create('',timezone_open("UTC"));
		$timestamp = date_format($timestamp, 'Y-m-d H:i:s');

		$post_data = array(
            'app_id' => $app_id,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'version' => '1.0',
            'timestamp' => $timestamp,

			'method' => 'pay.orderquery',
            'out_order_no' => $out_order_no,
            'merchant_no' => $merchant_no
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

		//var_dump($result);

		$result = preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;', $result);
		echo $result;
	}

?>
