<?php
  include("../include/sql.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $couponNumber=$data['couponNumber'];
      $userNumber=$data['userNumber'];
      $admin=$data['admin'];

      $searchSql .= isset($couponNumber)?" AND `couponId`='$couponNumber'":"";
      $searchSql .= isset($userNumber)?" AND `userNumber`='$userNumber'":"";
      $searchSql .= isset($admin)?" AND `userNumber`='0'":"";


      //只在客户查看自己的优惠券时查看是否过期
      if($admin != 1 && $admin == null){
        $stmt = $pdo->prepare("UPDATE `couponTable` SET `couponState` = '2'
                            WHERE `couponEndDate` < CURRENT_DATE AND `couponState` = '0' ".$searchSql);
        $stmt->execute();    
      }

      $couponList = array();
      $stmt = $pdo->prepare("SELECT * From `couponTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['couponNumber']=$row['couponId'];
          $couponList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$couponList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['couponNumber'])){
      $couponNumber=$data['couponNumber'];
      foreach ($couponNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `couponTable`WHERE `couponId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //用户用code添加优惠券
    if(isset($data['isRender']) && isset($data['userNumber']) && isset($data['couponCode'])){
      $userNumber=$data['userNumber'];
      $couponCode=$data['couponCode'];
      
      $couponInfo = array();
      $stmt = $pdo->prepare("SELECT * From `couponTable` WHERE `couponCode` = '$couponCode' AND `couponEndDate` > CURRENT_DATE");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $couponInfo=$row;
        }
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }
      
      //判断用户是否已经有这张优惠券
      $stmt = $pdo->prepare("SELECT * From `couponTable` WHERE `couponCode` = '$couponCode' AND `userNumber` = '$userNumber'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          if(isset($row['couponId'])){
            echo json_encode(["message"=>"already add"]);
            exit();
          }
        }
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }

      if(array_key_exists("couponId",$couponInfo)){
        $couponCode=$couponInfo['couponCode'];
        $couponRate=$couponInfo['couponRate'];
        $couponRequiredPrice=$couponInfo['couponRequiredPrice'];
        $couponType=$couponInfo['couponType'];
        $couponState=$couponInfo['couponState'];
        $couponStartDate=$couponInfo['couponStartDate'];
        $couponEndDate=$couponInfo['couponEndDate'];

        $stmt = $pdo->prepare("INSERT INTO `couponTable`(`userNumber`,`couponCode`,`couponRate`,`couponRequiredPrice`,`couponType`,`couponState`,`couponStartDate`,`couponEndDate`) 
                              VALUES ('$userNumber','$couponCode','$couponRate','$couponRequiredPrice','$couponType','$couponState','$couponStartDate','$couponEndDate')");
        $stmt->execute();
      }else{
        echo json_encode(["message"=>"fail"]);
        exit();  
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //后台给全部用户添加优惠券
    if(isset($data['isAddAll'])){
      $couponRate=$data['couponRate'];
      $couponRequiredPrice=$data['couponRequiredPrice'];
      $couponType=$data['couponType'];
      $couponState=$data['couponState'];
      $couponStartDate=$data['couponStartDate'];
      $couponEndDate=$data['couponEndDate'];

      $stmt = $pdo->prepare("INSERT INTO `couponTable`(`userNumber`,`couponRate`,`couponRequiredPrice`,`couponType`,`couponState`,`couponStartDate`,`couponEndDate`) 
                            SELECT `userId`,'$couponRate','$couponRequiredPrice','$couponType','$couponState','$couponStartDate','$couponEndDate' FROM `userTable` WHERE `userRole` = '0'");
      $stmt->execute();

      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改 给客户用来兑换的优惠券
    $couponNumber=$data['couponNumber'];//就是couponId
    $couponRate=$data['couponRate'];
    $couponRequiredPrice=$data['couponRequiredPrice'];
    $couponType=$data['couponType'];
    $couponState=$data['couponState'];
    $couponStartDate=$data['couponStartDate'];
    $couponEndDate=$data['couponEndDate'];


    //修改
    if(isset($couponNumber) && $couponNumber !== ""){
      $stmt = $pdo->prepare("UPDATE `couponTable` SET  
                            `couponRate` = '$couponRate' , `couponRequiredPrice` = '$couponRequiredPrice' , `couponType` = '$couponType' , `couponState` = '$couponState', `couponStartDate` = '$couponStartDate', `couponEndDate` = '$couponEndDate'
                            WHERE `couponId` = '$couponNumber'");
      $stmt->execute();
      if($stmt != null){
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `couponTable`(`couponRate`,`couponRequiredPrice`,`couponType`,`couponStartDate`,`couponEndDate`) 
                          VALUES ('$couponRate','$couponRequiredPrice','$couponType','$couponStartDate','$couponEndDate')");
    $stmt->execute();
    if($stmt != null){
      //不需要number，没有双语 改成优惠券码
      $couponId = $pdo->lastInsertId();
      $couponCode= chr(rand(97,122)).chr(rand(97,122)).$couponId.chr(rand(97,122)).chr(rand(97,122));
      $stmt = $pdo->prepare("UPDATE `couponTable` SET `couponCode` = '$couponCode' 
                            WHERE `couponId` = '$couponId'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
