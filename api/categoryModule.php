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
      $categoryType=$data['categoryType'];
      $categoryParentId=$data['categoryParentId'];
      $noParentId=$data['noParentId'];

      $searchSql .= isset($categoryType)?" AND `categoryType`='$categoryType'":"";
      $searchSql .= isset($categoryParentId)?" AND `categoryParentId`=$categoryParentId":"";
      $searchSql .= isset($noParentId)?" AND `categoryParentId`=''":"";
      // $searchSql .= isset($categoryParentId)?" AND `categoryParentId`='$categoryParentId'":" AND `categoryParentId`=''";

      $categoryList = array();
      $stmt = $pdo->prepare("SELECT * From `categoryTable` WHERE `language` = '$language' ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          // $row["categoryImages"] = str_replace("../", "", $row["categoryImages"]);
          // $row["categoryImages"] = $row["categoryImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/".$row["categoryImages"] : "";
          $categoryList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$categoryList]);
      exit();
    }

    if(isset($data['isGetAdmin']) && $data['isGetAdmin'] !== ""){
      $categoryType=$data['categoryType'];
      $categoryParentId=$data['categoryParentId'];

      $searchSql .= isset($categoryType)?" AND `categoryType`=".$categoryType:"";
      $searchSql .= isset($categoryParentId)?" AND `categoryParentId`=".$categoryParentId:"";

      $categoryList = array();
      $stmt = $pdo->prepare("SELECT * From `categoryTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $categoryList[$row['categoryNumber']][$row['language']] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      $returnCategory = array();
      foreach ($categoryList as $key => $value){
        $returnCategory[] = $value;
      }

      echo json_encode(["message"=>"success","data"=>$returnCategory]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['categoryNumber'])){
      $categoryNumber=$data['categoryNumber'];
      foreach ($categoryNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `categoryTable` WHERE `categoryNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $date=date("YmdHis");
    $categoryNumber=$data['categoryNumber'];
    $categoryTitle=$data['categoryTitle'];
    $categoryType=$data['categoryType'];
    $categoryParentId=$data['categoryParentId'];
    $categoryImages=$data['categoryImages'];
    $language=$data['language'];

    // if($_FILES['categoryImages']['name'] != null){
    //   $File_type = strrchr($_FILES['categoryImages']['name'], '.'); 
    //   $categoryImages = '../include/pic/categoryImages/'.$date.rand(0,9).$File_type;
    //   $picsql .= ",`categoryImages`='".$categoryImages."'";
    // }
    //修改
    if(isset($categoryNumber) && $categoryNumber !== ""){
      // $stmt = $pdo->prepare("UPDATE `categoryTable` SET `categoryTitle` = '$categoryTitle', 
      //                       `categoryType` = '$categoryType' , `categoryParentId` = '$categoryParentId'".$picsql."
      //                       WHERE `categoryNumber` = '$categoryNumber' AND `language` = '$language'");
      $stmt = $pdo->prepare("UPDATE `categoryTable` SET `categoryParentId` = '$categoryParentId',`categoryImages` = '$categoryImages'
                      WHERE `categoryNumber` = '$categoryNumber'");
      $stmt->execute();
      if($stmt != null){
        // if($_FILES['categoryImages']['name'] != null){
        //   move_uploaded_file($_FILES['categoryImages']['tmp_name'], $categoryImages);
        // }
        //为每个语言更新相应字段
        foreach ($languageList as $key => $value) {
          $stmt = $pdo->prepare("UPDATE `categoryTable` SET `categoryTitle` = '$categoryTitle[$value]' WHERE `categoryNumber` = '$categoryNumber' AND `language` = '$value'");
          $stmt->execute();
        }
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }
    
    //添加
    $stmt = $pdo->prepare("INSERT INTO `categoryTable`(`categoryImages`,`categoryTitle`,`categoryType`,`categoryParentId`,`language`) 
                          VALUES ('$categoryImages','$categoryTitle[$language]','$categoryType','$categoryParentId','$language')");
    $stmt->execute();
    if($stmt != null){
      $categoryId = $pdo->lastInsertId();
      $categoryNumber= date('YmdHis').$categoryId;
      $stmt = $pdo->prepare("UPDATE `categoryTable` SET `categoryNumber` = '$categoryNumber' 
                            WHERE `categoryId` = '$categoryId' AND `language` = '$language'");
      $stmt->execute();
      // if($_FILES['categoryImages']['name'] != null){
      //   move_uploaded_file($_FILES['categoryImages']['tmp_name'], $categoryImages);
      // }
      //为每种语言添加信息
      foreach ($languageList as $key => $value) {
        if($value != $language){
          $stmt = $pdo->prepare("INSERT INTO `categoryTable`(`categoryNumber`,`categoryImages`,`categoryTitle`,`categoryType`,`categoryParentId`,`language`) 
                                VALUES ('$categoryNumber','$categoryImages','$categoryTitle[$value]','$categoryType','$categoryParentId','$value')");
          $stmt->execute();
        }
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
