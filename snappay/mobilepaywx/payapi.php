
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Snappay mobile payment demo</title>
	<style>
		* {
			margin: 0;
			padding: 0;
		}

		ul, ol {
			list-style: none;
		}

		.title {
			color: #ADADAD;
			font-size: 14px;
			font-weight: bold;
			padding: 8px 16px 5px 10px;
		}

		.hidden {
			display: none;
		}

		.new-btn-login-sp {
			border: 1px solid #D74C00;
			padding: 1px;
			display: inline-block;
		}

		.new-btn-login {
			background-color: #ff8c00;
			color: #FFFFFF;
			font-weight: bold;
			border: medium none;
			width: 82px;
			height: 28px;
		}

		.new-btn-login:hover {
			background-color: #ffa300;
			width: 82px;
			color: #FFFFFF;
			font-weight: bold;
			height: 28px;
		}

		.bank-list {
			overflow: hidden;
			margin-top: 5px;
		}

		.bank-list li {
			float: left;
			width: 153px;
			margin-bottom: 5px;
		}

		#main {
			width: 750px;
			margin: 0 auto;
			font-size: 14px;
			font-family: '宋体';
		}

		#logo {
			background-color: transparent;
			background-image: url("images/new-btn-fixed.png");
			border: medium none;
			background-position: 0 0;
			width: 166px;
			height: 35px;
			float: left;
		}

		#pay_type{
			width:187px;
		}

		#pay_type option{
			width:187px;
		}

		.red-star {
			color: #f00;
			width: 10px;
			display: inline-block;
		}

		.null-star {
			color: #fff;
		}

		.content {
			margin-top: 5px;
		}

		.content dt {
			width: 160px;
			display: inline-block;
			text-align: right;
			float: left;
		}

		.content dd {
			margin-left: 100px;
			margin-bottom: 5px;
		}

		#foot {
			margin-top: 10px;
		}

		.foot-ul li {
			text-align: center;
		}

		.note-help {
			color: #999999;
			font-size: 12px;
			line-height: 130%;
			padding-left: 3px;
		}

		.cashier-nav {
			font-size: 14px;
			margin: 15px 0 10px;
			text-align: left;
			height: 30px;
			border-bottom: solid 2px #CFD2D7;
		}

		.cashier-nav ol li {
			float: left;
		}

		.cashier-nav li.current {
			color: #AB4400;
			font-weight: bold;
		}

		.cashier-nav li.last {
			clear: right;
		}

		.pay_link {
			text-align: right;
		}

		.pay_link a:link {
			text-decoration: none;
			color: #8D8D8D;
		}

		.pay_link a:visited {
			text-decoration: none;
			color: #8D8D8D;
		}
	</style>
</head>

<?php
	require_once( 'lib/snappay-sign-utils.php' );

	//MUST keep sign key secretly. Best to load from somewhere else, like database.
	$signKey = '44effea5ca03b71dfd350093514a5342';
	$app_id = 'b74e5c6dc35fd080';

	$trans_amount = $_REQUEST['trans_amount'];
	$payment_method = $_REQUEST['payment_method'];
	$out_order_no = $_REQUEST['out_order_no'];
	$timestamp = $_REQUEST['timestamp'];
	$notify_url = $_REQUEST['notify_url'];
	$description = $_REQUEST['description'];
	$merchant_no = '902000074387';

	$return_url = $_POST['return_url'];

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
        'return_url' => $return_url,
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
