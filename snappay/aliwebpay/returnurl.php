
<html>
<head>
    <title>Return Url Page</title>
</head>
<body>

<?php

	if(isset($_REQUEST['out_order_no'])){
		$out_order_no = $_REQUEST['out_order_no'];
		echo 'out_order_no:' . $out_order_no . '<br>';
	}
    echo 'Pay Succeed!';
    /*
     * do business logic here, such as thank you customer.
     */
?>
</body>
</html>
