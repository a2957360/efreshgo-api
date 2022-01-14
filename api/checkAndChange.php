<?php
include("../include/sql.php");
require_once "../stripe/config.php";

http_response_code(200);
header('content-type:application/json;charset=utf8');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

$data = file_get_contents('php://input');
$data = json_decode($data,true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if($data['userPhone'] == "" || $data['verificationCode'] == ""){
      $message=["message"=>"fail"];
      echo json_encode($message);
      exit();
  }
  $userNumber=$data['userNumber'];
  $userPhone=$data['userPhone'];
  $userRole = $data['userRole'];
  $verificationCode=$data['verificationCode'];
  $userExpoToken = $data['userExpoToken'];

  // $str = md5(uniqid(md5(microtime(true)),true));
  // $token = sha1($str.$userPhone);

  $stmt = $pdo->prepare("SELECT * From `messageTable` WHERE `messagePhone` = '$userPhone' AND `userRole` = '$userRole';");
  $stmt->execute();
  if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      $messageCode=$row['messageCode'];
      $messageType=$row['messageType'];
      $uploadTime=$row['uploadTime'];
    }
  }
  if($messageCode == $verificationCode){

    $stmt = $pdo->prepare("SELECT * FROM `userTable`".$sql." WHERE `userPhone` = '$userPhone' AND `userRole` = '$userRole';");
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        if(isset($row['userId'])){
          echo json_encode(["message"=>"exist user"]);
          exit(); 
        }
      }
    }     

    if($messageType == "0"){
      $stmt = $pdo->prepare("UPDATE `userTable`SET `userPhone` = '$userPhone' WHERE `userId`='$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();   
    }else{
      echo json_encode(["message"=>"message wrong"]);
      exit();   
    }
    
  }else{
    echo json_encode(["message"=>"verificationCode wrong"]);
    exit();
  }
  
}
