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

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $languageList = LANGUAGE_LIST;

    $language=$data['language'];
    //查询
    $userNumber=$data['userNumber'];
    if(!isset($userNumber) || $userNumber== "" || $userNumber== 0){
      echo json_encode(["message"=>"no user"]);
      exit();
    }
    $cartList = array();
    $stmt = $pdo->prepare("SELECT * From `cartTable` WHERE `userNumber` = '$userNumber'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        $row['cartNumber'] = $row['cartId'];
        $cartList = $row['cartList'] != ""?json_decode($row['cartList'],true):array();
      }
    }else{
        echo json_encode(["message"=>"database error"]);
        exit();
    }


    //删除
    if(isset($data['isDelete']) && isset($data['userNumber'])){
      $userNumber=$data['userNumber'];
      $stmt = $pdo->prepare("UPDATE `cartTable` SET `cartList`='' WHERE `userNumber` = '$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //重新付款
    if(isset($data['isRepayment']) && isset($data['isRepayment'])){
      $userNumber=$data['userNumber'];
      $itemList= $data['itemList'];
      //清空购物车
      foreach ($itemList as $key => $value) {
        $updatecartList[] = ["itemNumber"=>$value['itemNumber'],"itemQuantity"=>$value['itemQuantity']];
      }
      // echo count($updatecartList);
      $cartList = json_encode($updatecartList ,JSON_UNESCAPED_UNICODE);
      $stmt = $pdo->prepare("UPDATE `cartTable` SET `cartList`='$cartList' WHERE `userNumber` = '$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $userNumber=$data['userNumber'];
    $itemNumber=$data['itemNumber'];
    $itemQuantity=$data['itemQuantity'];
    $insert = false;
    // if(count($cartList)>0){
    //   foreach ($cartList as $key => $value) {
    //     if($value['itemNumber'] == $itemNumber){

    //       $cartList[$key]['itemQuantity'] = $itemQuantity;
    //       $insert = true;
    //       if($itemQuantity <= 0){
    //         unset($cartList[$key]);
    //       }
    //     }
    //   }  
    // }
    //同时上传
    if(count($cartList)>0){
      foreach ($cartList as $key => $value) {
        if(in_array($value['itemNumber'],$itemNumber)){
          $getkey = array_search($value['itemNumber'],$itemNumber);
          $tmpitemQuantity = $itemQuantity[$getkey];
          unset($itemNumber[$getkey]);
          unset($itemQuantity[$getkey]);
          $cartList[$key]['itemQuantity'] = $tmpitemQuantity;
          if($tmpitemQuantity <= 0){
            unset($cartList[$key]);
          }
        }
      }
      foreach ($itemNumber as $key => $value) {
        $insert = true;
        if($itemQuantity[$key] <= 0){
          unset($itemNumber[$getkey]);
          unset($itemQuantity[$getkey]);
        }else{
          $cartList[] = ["itemNumber"=>$value,"itemQuantity"=>$itemQuantity[$key]];
        }
      }
      $cartList = array_values($cartList);
    }

    if(!$insert){
      foreach ($itemNumber as $key => $value) {
        if($itemQuantity[$key] > 0){
          $cartList[]=['itemNumber' => $itemNumber[$key],'itemQuantity' => $itemQuantity[$key]];
        }
      }
    }

    $itemPrice=0;
    foreach ($cartList as $key => $value) {
      $itemNumber = $value['itemNumber'];
      $stmt = $pdo->prepare("SELECT `itemDisplayPrice`
                            From `itemTable` 
                            WHERE `itemNumber` = '$itemNumber' AND `language` = '$languageList[0]'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $itemPrice = round($itemPrice + (float)$row['itemDisplayPrice'] * (float)$value['itemQuantity'],2);
        }
      }
    }

    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $cartList = $cartList != null?$cartList:[];
      echo json_encode(["message"=>"success","data"=>$cartList,"price"=>$itemPrice]);
      exit();
    }

    $uploadcartList = json_encode($cartList,JSON_UNESCAPED_UNICODE);
    //解决id自增问题
    $stmt = $pdo->prepare("UPDATE `cartTable` SET 
                      `cartList` = '$uploadcartList'
                      WHERE `userNumber` = '$userNumber'");
    $stmt->execute();
    if($stmt->rowCount() == 0){
      $stmt = $pdo->prepare("INSERT INTO `cartTable`(`userNumber`,`cartList`) 
                            VALUES ('$userNumber','$uploadcartList')");
      $stmt->execute();
      $emailId = $pdo->lastInsertId();
    }
    // $stmt = $pdo->prepare("INSERT INTO `cartTable` (`userNumber`,`cartList`) VALUES ('$userNumber','$uploadcartList')
    //                         ON DUPLICATE KEY UPDATE `userNumber`='$userNumber',`cartList`='$uploadcartList';");
    // $stmt->execute();
    // if($stmt != null){

    // }
    echo json_encode(["message"=>"success","data"=>$cartList,"price"=>$itemPrice]);
    exit();

    //添加
    // $stmt = $pdo->prepare("INSERT INTO `cartTable`(`cartTitle`,`cartContent`,`cartLink`,`cartType`,`language`) 
    //                       VALUES ('$cartTitle','$cartContent','$cartLink','$cartType','$language')");
    // $stmt->execute();
    // if($stmt != null){
    //   $cartId = $pdo->lastInsertId();
    //   $cartNumber= date('YmdHis').$cartId;
    //   $stmt = $pdo->prepare("UPDATE `cartTable` SET `cartNumber` = '$cartNumber' 
    //                         WHERE `cartId` = '$cartId' AND `language` = '$language'");
    //   $stmt->execute();
    //   //为每种语言添加信息
    //   foreach ($languageList as $key => $value) {
    //     if($value != $language){
    //       $stmt = $pdo->prepare("INSERT INTO `cartTable`(`cartNumber`,`cartLink`,`cartType`,`language`) 
    //                               VALUES ('$cartNumber','$cartLink','$cartType','$value')");
    //       $stmt->execute();
    //     }
    //   }
    //   echo json_encode(["message"=>"success"]);
    //   exit();
    // }

  }
