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
    if(isset($data['isLogin']) && $data['isLogin'] !== ""){
      $userName=$data['userName'];
      $userPassword=$data['userPassword'];

      $stmt = $pdo->prepare("SELECT * From `adminUserTable` WHERE `userName` = '$userName'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          if(password_verify($userPassword,$row['userPassword'])){
            echo json_encode(["message"=>"success",'data'=>$row]);
            exit();
          }
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      echo json_encode(["message"=>"fail"]);
      exit();
    }
    
    //添加/修改 
    $adminUserId=$data['adminUserId'];
    $userName=$data['userName'];
    $userPassword=password_hash(isset($data['userPassword'])?$data['userPassword']:"", PASSWORD_DEFAULT);

    //修改
    if(isset($adminUserId) && $adminUserId !== ""){
      $stmt = $pdo->prepare("UPDATE `adminUserTable` 
                            SET `userName` = '$userName',`userPassword` = '$userPassword'
                            WHERE `adminUserId` = '$adminUserId'");
      $stmt->execute();
      if($stmt != null){
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    //带有用户注册的功能
    // $stmt = $pdo->prepare("INSERT INTO `userTable`(`storeId`,`userName`,`userPhone`,`userPassword`) 
    //                       VALUES ('$storeId','$userName','$userPhone','$userPassword')");
    //没有用户注册
    $stmt = $pdo->prepare("INSERT INTO `adminUserTable`(`userName`,`userPassword`) 
                      VALUES ('$userName','$userPassword')");
    $stmt->execute();
    if($stmt != null){
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
