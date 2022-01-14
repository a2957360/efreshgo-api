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
      $recommendType=$data['recommendType'];
      $userNumber=$data['userNumber'];

      $searchSql .= isset($recommendType)?" AND `recommendType`='$recommendType'":"";
      $searchSql .= isset($userNumber)?" AND `userNumber`='$userNumber'":"";
      $searchSql .= isset($userNumber)?" AND `language`='$language'":"";

      $recommendList = array();
      $stmt = $pdo->prepare("SELECT * From `recommendTable` WHERE 1".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['recommendNumber'] = $row['recommendId'];
          $row['recommendContent'] = json_decode($row['recommendContent'],true);
          $row['recommendContent'] = (array)$row['recommendContent'];
          $recommendList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$recommendList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['recommendNumber'])){
      $recommendNumber=$data['recommendNumber'];
      foreach ($recommendNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `recommendTable` WHERE `recommendNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $recommendNumber=$data['recommendNumber'];
    $recommendTitle=$data['recommendTitle'];
    // $recommendContent=$data['recommendContent'];
    $recommendContent = json_encode($data['recommendContent'],JSON_UNESCAPED_UNICODE);
    $recommendLink=$data['recommendLink'];
    $recommendType=$data['recommendType'];


    //修改
    if(isset($recommendNumber) && $recommendNumber !== ""){
      //为每种语言更新通用数据
      $stmt = $pdo->prepare("UPDATE `recommendTable` SET 
                            `recommendContent` = '$recommendContent'
                            WHERE `recommendId` = '$recommendNumber'");
      $stmt->execute();
      // if($stmt != null){
      //   //为每个语言更新相应字段
      //   foreach ($languageList as $key => $value) {
      //     $stmt = $pdo->prepare("UPDATE `recommendTable` SET `recommendTitle` = '$recommendTitle[$value]', `recommendContent` = '$recommendContent[$value]'
      //                       WHERE `recommendNumber` = '$recommendNumber' AND `language` = '$value'");
      //     $stmt->execute();
      //   }
      //   echo json_encode(["message"=>"success"]);
      // }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `recommendTable`(`recommendTitle`,`recommendContent`,`recommendLink`,`recommendType`) 
                          VALUES ('$recommendTitle','$recommendContent','$recommendLink','$recommendType')");
    $stmt->execute();
    if($stmt != null){
      // $recommendId = $pdo->lastInsertId();
      // $recommendNumber= date('YmdHis').$recommendId;
      // $stmt = $pdo->prepare("UPDATE `recommendTable` SET `recommendNumber` = '$recommendNumber' 
      //                       WHERE `recommendId` = '$recommendId' AND `language` = '$language'");
      // $stmt->execute();
      //为每种语言添加信息
      // foreach ($languageList as $key => $value) {
      //   if($value != $language){
      //     $stmt = $pdo->prepare("INSERT INTO `recommendTable`(`recommendNumber`,`recommendLink`,`recommendType`,`language`) 
      //                             VALUES ('$recommendNumber','$recommendLink','$recommendType','$value')");
      //     $stmt->execute();
      //   }
      // }
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
