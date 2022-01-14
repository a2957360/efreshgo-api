
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
	
	if(isset($_REQUEST['sign_key'])){
		$signKey = $_REQUEST['sign_key'];
	}
	if(isset($_REQUEST['app_id'])){
		$app_id = $_REQUEST['app_id'];
	}
	if(!empty($_REQUEST['trans_currency'])){
		$trans_currency = $_REQUEST['trans_currency']; 
	}

	$out_order_no = '1';
	if(isset($_REQUEST['out_order_no'])){
		$out_order_no = $_REQUEST['out_order_no'];
	}else{
		$milliseconds = round(microtime(true) * 1000);
		$milliseconds = substr($milliseconds, 3);
		$randnum = rand(100, 999);
		$out_order_no = $milliseconds.$randnum;
	}
	
	$timestamp = '2020-01-01 00:00:00';
	if(isset($_REQUEST['timestamp'])){
		$timestamp = $_REQUEST['timestamp'];
	}else{
		$date = date_create('',timezone_open("UTC"));
		$timestamp = date_format($date, 'Y-m-d H:i:s');
	}
	
	$notify_url = $_REQUEST['notify_url'];
	$description = $_REQUEST['description'];
	$merchant_no = $_REQUEST['merchant_no'];
	
	$return_url = $_POST['return_url'];

	$post_data = array(
            'app_id' => $app_id,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'version' => '1.0',
            'timestamp' => $timestamp,
            'trans_currency' => $trans_currency,

            'method' => 'pay.qrcodepay',
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
	$codeStr = '';
	if($result_json['code'] === '0'){
		$qrcode_url = $result_json['data'][0]['qrcode_url'];
		//echo print_r($qrcode_url);
		$codeStr = $qrcode_url;
	}

?>

<body>
	<?php 
	if($result_json['code'] !== '0'){
		echo 'Create order fail. [' . $result_json['msg'] . ']';
	} else {
	?>

	<h>Please scan QR code with <?php echo $pay_type=='ALIPAY' ?  'Alipay': 'WeChat' ?></h>
	<br><br>
	<div id="code"></div> 
	<script type="text/javascript" src="lib/jquery-3.3.1.min.js"></script> 
	<script type="text/javascript" src="lib/jquery.qrcode.min.js"></script> 
	<script type="text/javascript">
		$("#code").qrcode({ 
		    width: 200,
		    height:200,
		    text: "<?php echo $codeStr ?>"
		}); 
	</script> 

	<p>Transaction status: <span class="status"></span></p>

	<script>
        (function() {
            var out_order_no = <?php echo $out_order_no ?>;
            var merchant_no = <?php echo $merchant_no ?>;
            var counter =0;
            var pollingurl = "../query/queryorder.php?out_order_no="+out_order_no+"&merchant_no="+merchant_no;
            var status = $('.status'),
                poll = function() {
                    status.text('Waiting ...');
                    console.log("waiting counter: " + counter);
                    if (counter++ >  30) { // timeout after 30 times
                        status.text(" TIMEOUT !!!  "); // timeout
                        clearInterval(pollInterval); // optional: stop poll function
                        console.log("waiting for user confirm transaction is timeout  " + " === ");
                    } else {
                        $.ajax({
                            url: pollingurl,
                            dataType: 'json',
                            type: 'get',
                            success: function(data) { 
								// check if it is available
								console.log("data.data[0].trans_status: " + data.data[0].trans_status);
                                if ( data.data[0].trans_status == 'SUCCESS' ) { // get and check data value
                                    status.text(data.data[0].trans_status); // get and print data string
                                    clearInterval(pollInterval); // optional: stop poll function
                                    console.log("queryorder.jsp return success " + " === ");
                                    window.location.replace("<?php echo $return_url.'?out_order_no='.$out_order_no ?>");
                                } else {
                                    status.text(data.data[0].trans_status);
                                    console.log("queryorder.jsp return not success   " + " === ");
                                }
                            },
                            error: function(xhr, status, error) { // error logging
                            	console.log(xhr);
                                var err = eval("(" + xhr.responseText + ")");
                                console.log(err.Message);
                            }
                        });
                    }

                };

            poll(); // also run function on init
            pollInterval = setInterval(function() { // run function every 5000 ms
                poll();
            }, 5000);
        })();
    </script>

	<?php	
	}
	?>
	
</body>
</html>
