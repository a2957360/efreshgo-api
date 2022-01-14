<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  require_once "../stripe/config.php";

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $languageList = LANGUAGE_LIST;
    //查询
    if(isset($data['isCheck']) && $data['isCheck'] !== ""){
      $userFacebookToken=$data['userFacebookToken'];
      $userWechatToken=$data['userWechatToken'];
      $userAppleToken=$data['userAppleToken'];
      $userExpoToken=$data['userExpoToken'];

      $searchSql .= isset($userFacebookToken)?"AND `userFacebookToken`='$userFacebookToken'":"";
      $searchSql .= isset($userWechatToken)?"AND `userWechatToken`='$userWechatToken'":"";
      $searchSql .= isset($userAppleToken)?"AND `userAppleToken`='$userAppleToken'":"";
      if(!isset($userFacebookToken)&&!isset($userWechatToken)&&!isset($userAppleToken)){
        exit();
      }

      $userlist = array();
      $stmt = $pdo->prepare("SELECT * From `userTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!isset($row["userId"]) || $row["userId"] == null || $row["userId"] == 0){
          //如果不存在就添加
          $date = date('YmdHis');
          // $userName=$data['userName']!=""?$data['userName']:"新用户".$date;
          // $userPhone=$data['userPhone']!=""?$data['userPhone']:$date;
          // $userEmail=$data['userEmail']!=""?$data['userEmail']:$date."@email.com";
          $userImages=$data['userImages'];
          $userName=$data['userName']!=""?$data['userName']:"";
          $userPhone=$data['userPhone']!=""?$data['userPhone']:"";
          $userEmail=$data['userEmail']!=""?$data['userEmail']:"";
          //用户状态
          $userState= "";
          //stripe token
          $userStripeToken= "";
          //插入stripe costomer
          try {
            $customer = \Stripe\Customer::create([
              'name'  => $userPhone
            ]);
          } catch(Exception $e) {
            echo json_encode(["message"=>"stripe error"]);
            exit();
          }
          $userStripeToken = $customer["id"];
        }else{
          //返回用户信息
          $row["userNumber"] = $row["userId"];
          $row["userImages"] = str_replace("../", "", $row["userImages"]);
          if($row["userImages"] != ""){
            if(strpos($row["userImages"],'http')){
              $row["userImages"] = $row["userImages"];
            }
            $row["userImages"] = str_replace("../", "", $row["userImages"]);
            $row["userImages"] = 'http://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"];
          }else{
            $row["userImages"] = "";
          }
          $userlist = $row;
          echo json_encode(["message"=>"success","data"=>$userlist]);
          exit();
        }
          // $userId = $row["userId"];
          // $stmt = $pdo->prepare("UPDATE `userTable` SET `userName` = '$userName',`userPhone` = '$userPhone',`userEmail` = '$userEmail' WHERE `userId` = '$userId'");
          // $stmt->execute();

        
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      //添加
      $stmt = $pdo->prepare("INSERT INTO `userTable`(`userName`,`userImages`,`userPhone`,`userEmail`,`userRole`,`userState`,`userExpoToken`,`userStripeToken`,`userFacebookToken`,`userWechatToken`,`userAppleToken`)
                            VALUES ('$userName','$userImages','$userPhone','$userEmail','0','0','$userExpoToken','$userStripeToken','$userFacebookToken','$userWechatToken','$userAppleToken')");
      $stmt->execute();
      if($stmt != null){
        $userId = $pdo->lastInsertId();
        if($userId == 0){
          echo json_encode(["message"=>"fail"]);
          exit();
        }
        //给用户添加优惠券
        $couponStartDate = date("Y-m-d");
        $couponEndDate = "2022-12-31";
        $values = "";
        for ($i=0; $i < 20; $i++) { 
          if($i == 0){
            $values = $values."('$userId','','5','100','0','0','$couponStartDate','$couponEndDate')";
          }else{
            $values = $values.",('$userId','','5','100','0','0','$couponStartDate','$couponEndDate')";
          }
        }
        $stmt = $pdo->prepare("INSERT INTO `couponTable`(`userNumber`,`couponCode`,`couponRate`,`couponRequiredPrice`,`couponType`,`couponState`,`couponStartDate`,`couponEndDate`) 
                              VALUES ".$values);
        $stmt->execute();
        $data['userNumber']=$userId;
        $data['userName']=$userName;
        $data['userPhone']=$userPhone;
        $data['userEmail']=$userEmail;
        echo json_encode(["message"=>"success","data"=>$data]);
        exit();
      }
    }
  }
