<?php
  include("../include/sql.php");
  include("../include/conf/config.php");

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

	$pushId=$data['pushId'];
	$pushContent=$data['pushContent'];
	$stmt = $pdo->prepare("UPDATE `pushTable` SET `pushContent` = '$pushContent' WHERE `pushId` = '$pushId'");
	$stmt->execute();
