<?php

$lastday = date("Y-m-d 23:59:59", strtotime(-date('d').'day'));
echo $lastday;

?>

