
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
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
<body text=#000000 bgColor="#ffffff" leftMargin=0 topMargin=4>
<div id="main">

	<div class="cashier-nav">
		<ol>
			<li>[ Retail/Web E-commerce – Scan Merchant Dynamic QR PHP Demo ]  </li>
			<li class="current"> Create Order</li>
		</ol>
	</div>
	<?php
		$milliseconds = round(microtime(true) * 1000);
		$milliseconds = substr($milliseconds, 3);
		$randnum = rand(100, 999);
		$orderId = $milliseconds.$randnum;
		$date = date_create('',timezone_open("UTC"));
		$date = date_format($date, 'Y-m-d H:i:s');
		$server_path = $_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], "/"));
	?>

	<form  action=payapi.php  method=post >
		<div id="body" style="clear: left">
			<dl class="content">
				<dt>trans_amount: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30" name="trans_amount" value="0.01"/>
					<span> </span>
				</dd>
				<dt>payment_method: </dt>
				<dd>
					<span class="null-star">*</span>
					<select name="payment_method" id="pay_type">
						<option value="ALIPAY">ALIPAY</option>
						<option value="WECHATPAY">WECHATPAY</option>
					</select>
					<span> payment_method</span>
				</dd>

				<dt>out_order_no: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30" name="out_order_no"
															value="<?php echo $orderId ?>"/> <span> out_order_no</span>
				</dd>
				<dt>timestamp: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30"
															name="timestamp" value="<?php echo $date ?>"/> <span> timestamp(UTC)</span>
				</dd>
				<dt>notify_url: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30"
															name="notify_url" value="http://<?php echo $server_path ?>/notifyurl.php" /> <span> notify_url</span>
				</dd>
				<dt>return_url: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30"
															name="return_url" value="http://<?php echo $server_path ?>/returnurl.php" /> <span> return_url</span>
				</dd>
				<dt>description: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30"
															name="description" value="mobile-shopping-phptest" /> <span> description</span>
				</dd>
				<dt>merchant_no: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30" name="merchant_no" value="901800000116" /> <span> merchant_no </span>
				</dd>
				<dt>sign_key: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30" name="sign_key" value="7e2083699dd510575faa1c72f9e35d43" /> <span> sign_key </span>
				</dd>
				<dt>app_id: </dt>
				<dd>
					<span class="null-star">*</span> <input size="30" name="app_id" value="9f00cd9a873c511e" /> <span> app_id </span>
				</dd>
				<dt>trans_currency: </dt>
				<dd>
					<span class="null-star">*</span> 
					<select name="trans_currency" id="trans_currency">
						<option value="CAD">CAD</option>
						<option value="USD">USD</option>
					</select> <span> trans_currency </span>
				</dd>

				<br/>
				<dd>
						<span class="new-btn-login-sp">
							<button class="new-btn-login" type="submit"
									style="text-align: center;">Create</button>
						</span>
				</dd>
			</dl>
		</div>
	</form>
	<br><br>
	<div id="foot">
		<a href="../demo.zip">Download PHP Demo files</a>
	</div>
</div>

</body>
</html>
