
<html>
<head>
    <title>Return Url Page</title>
</head>
<body>

<?php
	if(isset($_REQUEST['out_order_no'])){
		echo 'Order NO:' . $_REQUEST['out_order_no'] . '<br>';
	}
    echo 'Pay Succeed!';
    /*
     * do business logic here, such as thank you customer.
     */
?>
</body>
</html>
