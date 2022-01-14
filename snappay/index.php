
<html>
<head>
	<title>SnapPay OpenAPI Demo</title>
</head>
<body>

<h2>SnapPay OpenAPI Demo</h2>

<div id="main">
	<a href="mobilepay/index.php">Mobile H5 Alipay</a> (Customer using Alipay App to open H5 page)<br><br>
	<a href="mobilepaywx/index.php">Mobile H5 WeChat</a> (Customer using WeChat App to open H5 page)<br><br>
	<a href="qrcode/index.php">Retail/Web E-commerce – Display Merchant Dynamic QR</a> (Retail POS device or PC web browser display QR, customer scan with Alipay or WeChat)<br><br>
	<a href="aliwebpay/index.php">Alipay Web – Sign in with Alipay account</a> (PC web browser redirect to Alipay, scan QR or login with Alipay account)<br><br>
	<a href="barcode/index.php">Barcode Payment</a> (Provides the way to pay for an order by scanning the barcode on customer's phone.)<br><br>
	<a href="query/index.php">Query Order</a> <br><br>
	<a href="refund/index.php">Refund Order</a> <br><br>
	<a href="cancel/index.php">Cancel Order</a> (if waiting pay and timeout, cancel this payment process. if already paid, then refund)<br><br>
	<a href="accounting/index.php">Download Bill</a> (Only download complete transaction)<br><br>

	<a href="demo.zip">Download PHP Demo files</a>

	<P>
		Some note:<br>
		1) About notify_url, make sure have public domain or IP; don't add any custom parameters(can't have ? mark in url path). <br>
		   If still don't get notify callbak, try this tool <a href="notify/index.php">Simulate Notify</a>, input your merchant_id, sign_key and notify_url for testing.
		   <br>
		   <br>
		2）Only Mobile H5 Alipay dosen't support return url. Scan QR on web browser supports, but need merchant implement on their own, please check demo.
	</P>
	
</div>

</body>
</html>
