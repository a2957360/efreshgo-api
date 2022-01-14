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
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $infoType=$data['infoType'];

      $searchSql .= isset($infoType)?" AND `infoType`='$infoType'":"";

      $infoList = array();
      $stmt = $pdo->prepare("SELECT * From `infoTable` WHERE 1".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['infoNumber'] = $row['infoId'];
          $row["infoContent"] = json_decode($row["infoContent"], true);
          $infoList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$infoList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['infoNumber'])){
      $infoNumber=$data['infoNumber'];
      foreach ($infoNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `infoTable` WHERE `infoNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    // $infoData=json_decode($row["infoData"], true);

    // foreach ($infoData as $key => $value) {
    //   $tmpinfoId = $value['infoId'];
    //   $tmpinfoContent = $value['infoContent'];
    //   $tmpinfoLink = $value['infoLink'];

    //   $stmt = $pdo->prepare("UPDATE `infoTable` SET 
    //                         `infoLink` = '$tmpinfoLink' ,`infoContent` = '$tmpinfoContent' 
    //                         WHERE `infoId` = '$tmpinfoId'");
    //   $stmt->execute();
    //   echo json_encode(["message"=>"success"]);
    //   exit();
    // }

    //添加/修改
    $infoNumber=$data['infoNumber'];
    // $infoContent=$data['infoContent'];
    $infoContent = json_encode($data['infoContent'],JSON_UNESCAPED_UNICODE);
    $infoLink=$data['infoLink'];

    //修改
    if(isset($infoNumber) && $infoNumber !== ""){
      //为每种语言更新通用数据
      $stmt = $pdo->prepare("UPDATE `infoTable` SET 
                            `infoLink` = '$infoLink' ,`infoContent` = '$infoContent' 
                            WHERE `infoId` = '$infoNumber'");
      $stmt->execute();
      if($stmt != null){
        //为每个语言更新相应字段
        // foreach ($languageList as $key => $value) {
        //   $stmt = $pdo->prepare("UPDATE `infoTable` SET `infoContent` = '$infoContent[$value]'
        //                     WHERE `infoNumber` = '$infoNumber' AND `language` = '$value'");
        //   $stmt->execute();
        // }
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }




    //添加
    // $stmt = $pdo->prepare("INSERT INTO `infoTable`(`infoTitle`,`infoContent`,`infoLink`,`infoType`,`language`) 
    //                       VALUES ('$infoTitle','$infoContent','$infoLink','$infoType','$language')");
    // $stmt->execute();
    // if($stmt != null){
    //   $infoId = $pdo->lastInsertId();
    //   $infoNumber= date('YmdHis').$infoId;
    //   $stmt = $pdo->prepare("UPDATE `infoTable` SET `infoNumber` = '$infoNumber' 
    //                         WHERE `infoId` = '$infoId' AND `language` = '$language'");
    //   $stmt->execute();
    //   //为每种语言添加信息
    //   foreach ($languageList as $key => $value) {
    //     if($value != $language){
    //       $stmt = $pdo->prepare("INSERT INTO `infoTable`(`infoNumber`,`infoLink`,`infoType`,`language`) 
    //                               VALUES ('$infoNumber','$infoLink','$infoType','$value')");
    //       $stmt->execute();
    //     }
    //   }
    //   echo json_encode(["message"=>"success"]);
    //   exit();
    // }

  }
