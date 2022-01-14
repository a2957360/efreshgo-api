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
    //60秒失效
    // $today=date("Y-m-d H:i:s");
    // $second=floor((strtotime($today)-strtotime($uploadTime))%86400%3600);
    // echo $second;
    // if($second > 60){
    //   $message=["message"=>"fail"];
    //   echo json_encode($message);
    //   exit();
    // }
    $data['messageType'] = $messageType;
    //判断用户是否存在
    //判断是否是商家
    if($userRole == 1){
      $sql = "LEFT JOIN `storeTable` ON `storeTable`.`managerUserNumber` = `userTable`.`userId`";
    }else if($userRole == 2){
      $sql .= "LEFT JOIN `driverTable` ON `driverTable`.`userNumber` = `userTable`.`userId`";
    }
    if($messageType == '1'){
      $stmt = $pdo->prepare("UPDATE `userTable` SET `userExpoToken` = '$userExpoToken' WHERE `userPhone` = '$userPhone' AND `userRole` = '$userRole';");
      $stmt->execute();
      $stmt = $pdo->prepare("SELECT * FROM `userTable`".$sql." WHERE `userPhone` = '$userPhone' AND `userRole` = '$userRole';");
      $stmt->execute();
      if($stmt != null){
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
          $row["userImages"] = str_replace("../", "", $row["userImages"]);
          $row["userImages"] = $row["userImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"]:"";
          //删除国家码
          $row["userPhone"] = str_replace("+1", " ", $row["userPhone"]);
          $data = $row;
          $data['userNumber'] = $row['userId'];
          $data['driverNumber'] = $row['driverId'];
          //如果是商家确定有商家信息
          if($userRole == 1 && ($row['storeId'] == null || $row['storeId'] == "")){
            echo json_encode(["message"=>"not manager"]);
            exit();
          }
          echo json_encode(["message"=>"success","data"=>$data]);
          exit();
        }
        echo json_encode(["message"=>"no store"]);
        exit();
      }     
    }



    if($messageType == "0"){
      //判断注册的是用户还是骑手
      if($userRole == 0){
        $userState = 0;
        //插入stripe costomer
        try {
          $customer = \Stripe\Customer::create([
            'name'  => $userPhone
          ]);
        } catch(Exception $e) {

        }
        $userStripeToken = $customer["id"];
      }else if($userRole == 2){
        $userState = 3;
        $userStripeToken = "";
      }

      $userName = "User".date("YmdHis");
      $userImages = "";
      $stmt = $pdo->prepare("INSERT INTO `userTable`(`userImages`,`userName`,`userPhone`,`userEmail`,`userRole`,`userState`,`userStripeToken`)
                          VALUES ('$userImages','$userName','$userPhone','$userEmail','$userRole','$userState','$userStripeToken')");
      $stmt->execute();
      $data = array();
      if($stmt != null){
        $lastid = $pdo->lastinsertid();
        //给用户添加优惠券
        $couponStartDate = date("Y-m-d");
        $couponEndDate = "2022-12-31";
        $values = "";
        for ($i=0; $i < 20; $i++) { 
          if($i == 0){
            $values = $values."('$lastid','','5','100','0','0','$couponStartDate','$couponEndDate')";
          }else{
            $values = $values.",('$lastid','','5','100','0','0','$couponStartDate','$couponEndDate')";
          }
        }
        $stmt = $pdo->prepare("INSERT INTO `couponTable`(`userNumber`,`couponCode`,`couponRate`,`couponRequiredPrice`,`couponType`,`couponState`,`couponStartDate`,`couponEndDate`) 
                              VALUES ".$values);
        $stmt->execute();
        //获取用户信息
        $stmt = $pdo->prepare("SELECT * FROM `userTable` WHERE `userPhone` = '$userPhone' AND `userRole` = '$userRole';");
        $stmt->execute();
        if($stmt != null){
          while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $row['userNumber'] = $row['userId'];
            $row["userImages"] = str_replace("../", "", $row["userImages"]);
            $row["userImages"] = $row["userImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"] : "";
            //删除国家码
            $row["userPhone"] = str_replace("+1", " ", $row["userPhone"]);
            $data = $row;
          }
        }
        $data["logState"] = 0;
        $message=["message"=>"success","data"=>$data];
        echo json_encode($message);
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }
    }
    
  }else{
    echo json_encode(["message"=>"verificationCode wrong"]);
    exit();
  }
  
}
