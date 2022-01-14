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

    $language=$data['language'];
    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $savedItemType=$data['savedItemType'];
      $userNumber=$data['userNumber'];
      $language=$data['language'];

      $searchSql .= isset($savedItemType)?" AND `savedItemTable`.`savedItemType`='$savedItemType'":"";
      $searchSql .= isset($userNumber)?" AND `savedItemTable`.`userNumber`='$userNumber'":"";

      $savedItemList = array();
      $stmt = $pdo->prepare("SELECT `savedItemTable`.`savedItemId`,`itemTable`.`itemNumber`,`itemTable`.`itemImages`,`itemTable`.`itemTitle`,`itemTable`.`itemSubTitle`,
                              `itemTable`.`itemUnit`,`minimumUnit`,`itemPrice`,`itemTable`.`itemDisplayPrice`,`itemTable`.`itemTag`,`itemTable`.`itemDescription`,
                              `cookbookTable`.`cookbookNumber`,`cookbookTable`.`cookbookImages`,`cookbookTable`.`cookbookTitle`,`cookbookTable`.`cookbookSubTitle`
                              FROM `savedItemTable` 
                              LEFT JOIN `itemTable` ON `itemTable`.`itemNumber` = `savedItemTable`.`itemNumber` 
                              AND `savedItemTable`.`savedItemType` = '0' 
                              AND `itemTable`.`language` = '$language'
                              LEFT JOIN `cookbookTable` ON `cookbookTable`.`cookbookNumber` = `savedItemTable`.`itemNumber` 
                              AND `savedItemTable`.`savedItemType` = '1' 
                              AND `cookbookTable`.`language` = '$language'
                              WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["savedItemNumber"] = $row['savedItemId'];
          $row["itemImages"] = json_decode($row['itemImages'], true);
          //获取富文本
          $row["itemDescription"] = json_decode($row["itemDescription"],true);
          $tmpdescription = array();
          foreach ($row["itemDescription"]['blocks'] as $key => $value) {
            if($value['type'] == "atomic"){
              $tmpimageurl = $row["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
              $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
            }else{
              $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
            }
          }
          $row["minimumUnit"] = (float)$row["minimumUnit"];
          $row["itemDescription"] = $tmpdescription;
          $row["itemTag"] = explode(",",$row["itemTag"]);
          if($row["itemNumber"] != "" && $row["itemNumber"] != null){
            $savedItemList[] = $row;
          }
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$savedItemList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['savedItemNumber'])){
      $savedItemNumber=$data['savedItemNumber'];
      foreach ($savedItemNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `savedItemTable` WHERE `savedItemId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $savedItemNumber=$data['savedItemNumber'];
    $userNumber=$data['userNumber'];
    $itemNumber=$data['itemNumber'];
    $savedItemType=$data['savedItemType'];  //0：收藏产品；1：收藏菜谱 

    //修改
    if(isset($savedItemNumber) && $savedItemNumber !== ""){
      $stmt = $pdo->prepare("UPDATE `savedItemTable` SET `userNumber` = '$userNumber', `itemNumber` = '$itemNumber', `savedItemType` = '$savedItemType' WHERE `savedItemId` = '$savedItemNumber'");
      $stmt->execute();
      if($stmt != null){
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `savedItemTable`(`userNumber`,`itemNumber`,`savedItemType`) VALUES ('$userNumber','$itemNumber','$savedItemType')");
    $stmt->execute();
    if($stmt != null){
      //savedItem不需要number
      // $savedItemId = $pdo->lastInsertId();
      // $savedItemNumber= date('YmdHis').$savedItemId;
      // $stmt = $pdo->prepare("UPDATE `savedItemTable` SET `savedItemNumber` = '$savedItemNumber' 
      //                       WHERE `savedItemId` = '$savedItemId' AND `language` = '$language'");
      // $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
