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

    $language=$data['language'];
    $searchHistory=SEARCH_HISTORY;

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $userNumber=$data['userNumber'];
      $storeNumber=$data['storeNumber'];
      $itemNumber=$data['itemNumber'];
      $itemCategory=$data['itemCategory'];
      $itemParentCategory=$data['itemParentCategory'];
      $searchText=$data['searchText'];
      //如果是单个产品返回购物车数量
      if(isset($itemNumber) && $itemNumber != "" && $itemNumber != null){
        $cartList = array();
        $itemQuantity = 0;
        $stmt = $pdo->prepare("SELECT * From `cartTable` WHERE `userNumber` = '$userNumber'");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $row['cartNumber'] = $row['cartId'];
            $cartList = $row['cartList'] != ""?json_decode($row['cartList'],true):array();
            foreach ($cartList as $key => $value) {
              if($value['itemNumber'] == $itemNumber){
                $itemQuantity = $value['itemQuantity'];
              }
            }
          }
        }else{
            echo json_encode(["message"=>"database error"]);
            exit();
        }
      }
      //添加搜索记录
      if(isset($data['searchText']) && $data['searchText'] != ""){
        $stmt = $pdo->prepare("SELECT * From `recommendTable` WHERE `language` = '$language' AND `userNumber` = '$userNumber'");
        $stmt->execute();
        if($stmt != null){
          $row=$stmt->fetch(PDO::FETCH_ASSOC);
          $row['recommendContent'] = json_decode($row['recommendContent'],true);
          $tmprecommendContent = $row['recommendContent'];
          if(!isset($row['recommendContent']) || !in_array($data['searchText'],$row['recommendContent'])){
            if(count($tmprecommendContent) == $searchHistory){
              // unset($tmprecommendContent[0]);
            }
            $tmprecommendContent[] = $data['searchText'];
            $tmprecommendContent = json_encode($tmprecommendContent,JSON_UNESCAPED_UNICODE);
            //删除原有的重新添加
            if(isset($userNumber) && $userNumber != ""){
              $stmt = $pdo->prepare("DELETE FROM `recommendTable` WHERE `language` = '$language' AND `userNumber` = '$userNumber'");
              $stmt->execute();
              $stmt = $pdo->prepare("INSERT INTO `recommendTable`(`userNumber`,`recommendTitle`,`recommendContent`,`recommendType`,`language`) 
                          VALUES ('$userNumber','userSearch','$tmprecommendContent','1','$language')");
              $stmt->execute();   
            }
          }  
        }else{
            echo json_encode(["message"=>"database error"]);
            exit();
        }
      }
      //用来判断下单时间
      $expectDeliverTime = date("Y-m-d",strtotime($data['expectDeliverTime']));
      $today = date("Y-m-d");

      //搜索返回
      $limit=QUERY_LIMIT;
      $offset=isset($data['offset'])?$data['offset']:0;

      $stockNumber=$data['stockNumber'];

      $searchSql .= isset($storeNumber)?" AND `stockTable`.`storeNumber`='$storeNumber'":"";
      $searchSql .= isset($itemNumber)?" AND `itemTable`.`itemNumber`='$itemNumber'":"";
      $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";
      $searchSql .= isset($itemParentCategory)?" AND FIND_IN_SET('".$itemParentCategory."',`itemTable`.`itemParentCategory`)":"";
      $searchSql .= isset($searchText)?" AND (FIND_IN_SET('".$searchText."',`itemTable`.`itemTag`) OR `itemTable`.`itemTitle` LIKE '%$searchText%')":"";
      $searchSql .= isset($data['newestItem'])?" ORDER BY `createTime`":"";

      $stockList = array();
      // $stmt = $pdo->prepare("SELECT `itemTable`.*,`stockTable`.`storeNumber`,`stockTable`.`stockTotal`,`stockTable`.`stockForPickup`,`stockTable`.`stockForSell`,
      //                   `savedItemTable`.`savedItemId` AS `savedItemNumber`
      //                       From `stockTable` 
      //                       LEFT JOIN `itemTable` ON `stockTable`.`itemNumber` = `itemTable`.`itemNumber` AND `itemTable`.`language` = '$language'
      //                       LEFT JOIN `savedItemTable` ON `stockTable`.`itemNumber` = `savedItemTable`.`itemNumber` AND `savedItemTable`.`userNumber` = '$userNumber'
      //                       WHERE 1 ".$searchSql." GROUP BY `itemTable`.`itemNumber` limit $offset,$limit");
      $stmt = $pdo->prepare("SELECT `itemTable`.*,`stockTable`.`storeNumber`,`stockTable`.`stockTotal`,`stockTable`.`stockForPickup`,`stockTable`.`stockForSell`,
                        `savedItemTable`.`savedItemId` AS `savedItemNumber`
                            From `stockTable` 
                            LEFT JOIN `itemTable` ON `stockTable`.`itemNumber` = `itemTable`.`itemNumber` AND `itemTable`.`language` = '$language'
                            LEFT JOIN `savedItemTable` ON `stockTable`.`itemNumber` = `savedItemTable`.`itemNumber` AND `savedItemTable`.`userNumber` = '$userNumber'
                            WHERE 1 ".$searchSql." GROUP BY `itemTable`.`itemNumber`");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          //所以查询都是要用number
          $row["stockNumber"] = $row["stockId"];
          $row["itemImages"] = json_decode($row["itemImages"], true);
          $row["itemTag"] = explode(",",$row["itemTag"]);
          $row["offset"] = (int)$offset + $limit;
          $row["itemCategory"] =explode(",",$row["itemCategory"]);
          $row["itemParentCategory"] = explode(",",$row["itemParentCategory"]);
          //添加购物车数量
          if(isset($itemNumber) && $itemNumber != "" && $itemNumber != null){
            $row["itemQuantity"] = $itemQuantity;
          }else{
            $row["itemQuantity"] = 0;
          }
          
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
          $row["itemDescription"] = $tmpdescription;
          if($expectDeliverTime != $today){
            $row["stockForSell"] = -1;
          }
          if($row["itemId"] != "" || $row["itemId"] != null){
            $stockList[] = $row;
          }
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      //猜你喜欢
      if(isset($itemNumber) && $itemNumber != "" && $itemNumber != null){
        $storeNumber=$data['storeNumber'];
        $itemCategory=$$stockList[0]['itemCategory'][0];
        //用来判断下单时间
        $expectDeliverTime = date("Y-m-d",strtotime($data['expectDeliverTime']));
        $today = date("Y-m-d");

        //搜索返回
        $limit=YOU_LIKE_QUERY_LIMIT;

        $searchSql .= isset($storeNumber)?" AND `stockTable`.`storeNumber`='$storeNumber'":"";
        $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";

        $likeList = array();
        $stmt = $pdo->prepare("SELECT `itemTable`.*,`stockTable`.`storeNumber`,`stockTable`.`stockTotal`,`stockTable`.`stockForPickup`,`stockTable`.`stockForSell`,`savedItemTable`.`savedItemId`
                              From `stockTable` 
                              LEFT JOIN `itemTable` ON `stockTable`.`itemNumber` = `itemTable`.`itemNumber` AND `itemTable`.`language` = '$language'
                              LEFT JOIN `savedItemTable` ON `stockTable`.`itemNumber` = `savedItemTable`.`itemNumber` AND `savedItemTable`.`userNumber` = '$userNumber'
                              WHERE 1 ".$searchSql." ORDER BY RAND() limit $limit");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            //所以查询都是要用number
            $row["stockNumber"] = $row["stockId"];
            $row["itemImages"] = json_decode($row["itemImages"], true);
            $row["itemTag"] = explode(",",$row["itemTag"]);
            $row["offset"] = (int)$offset + $limit;
            $row["itemCategory"] =explode(",",$row["itemCategory"]);
            $row["itemParentCategory"] = explode(",",$row["itemParentCategory"]);
            //获取富文本
            $row["itemDescription"] = json_decode($row["itemDescription"],true);
            $row["itemQuantity"] = 0;
            $tmpdescription = array();
            foreach ($row["itemDescription"]['blocks'] as $key => $value) {
              if($value['type'] == "atomic"){
                $tmpimageurl = $row["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
                $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
              }else{
                $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
              }
            }
            $row["itemDescription"] = $tmpdescription;
            if($expectDeliverTime != $today){
              $row["stockForSell"] = -1;
            }
            $likeList[] = $row;
          }
        }else{
            echo json_encode(["message"=>"database error"]);
            exit();
        }
        $stockList[0]['like'] = $likeList;
      }
      echo json_encode(["message"=>"success", "data"=>$stockList]);
      exit();
    }

    if(isset($data['isGetAdmin']) && $data['isGetAdmin'] !== ""){
      $userNumber=$data['userNumber'];
      $storeNumber=$data['storeNumber'];
      $itemNumber=$data['itemNumber'];
      $itemCategory=$data['itemCategory'];
      $itemParentCategory=$data['itemParentCategory'];
      $searchText=$data['searchText'];

      $stockNumber=$data['stockNumber'];

      $searchSql .= isset($storeNumber)?" AND `stockTable`.`storeNumber`='$storeNumber'":"";
      $searchSql .= isset($itemNumber)?" AND `itemTable`.`itemNumber`='$itemNumber'":"";
      $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";
      $searchSql .= isset($itemParentCategory)?" AND FIND_IN_SET('".$itemParentCategory."',`itemTable`.`itemParentCategory`)":"";
      $searchSql .= isset($searchText)?" AND FIND_IN_SET('".$searchText."',`itemTable`.`itemTag`)":"";
      $searchSql .= isset($data['newestItem'])?" ORDER BY `createTime`":"";

      $stockList = array();
      $stmt = $pdo->prepare("SELECT `itemTable`.*,`stockTable`.`stockId`,`stockTable`.`storeNumber`,`stockTable`.`stockTotal`,
                            `stockTable`.`stockForPickup`,`stockTable`.`stockForSell`,`savedItemTable`.`savedItemId`
                            From `stockTable` 
                            LEFT JOIN `itemTable` ON `stockTable`.`itemNumber` = `itemTable`.`itemNumber` AND `itemTable`.`language` = '$language'
                            LEFT JOIN `savedItemTable` ON `stockTable`.`itemNumber` = `savedItemTable`.`itemNumber` AND `savedItemTable`.`userNumber` = '$userNumber'
                            WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          //所以查询都是要用number
          $row["stockNumber"] = $row["stockId"];
          $row["itemImages"] = json_decode($row["itemImages"], true);
          $row["itemTag"] = explode(",",$row["itemTag"]);
          $row["offset"] = (int)$offset + $limit;
          $row["itemCategory"] =explode(",",$row["itemCategory"]);
          $row["itemParentCategory"] = explode(",",$row["itemParentCategory"]);
          
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
          $row["itemDescription"] = $tmpdescription;
          $stockList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      echo json_encode(["message"=>"success", "data"=>$stockList]);
      exit();
    }

    //猜你喜欢
    if(isset($data['isGetLike']) && $data['isGetLike'] !== ""){
      $storeNumber=$data['storeNumber'];
      $itemCategory=$data['itemCategory'];
      //用来判断下单时间
      $expectDeliverTime = date("Y-m-d",strtotime($data['expectDeliverTime']));
      $today = date("Y-m-d");

      //搜索返回
      $limit=YOU_LIKE_QUERY_LIMIT;

      $searchSql .= isset($storeNumber)?" AND `stockTable`.`storeNumber`='$storeNumber'":"";
      $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";

      $stockList = array();
      $stmt = $pdo->prepare("SELECT `itemTable`.*,`stockTable`.`storeNumber`,`stockTable`.`stockTotal`,`stockTable`.`stockForPickup`,`stockTable`.`stockForSell`,`savedItemTable`.`savedItemId`
                            From `stockTable` 
                            LEFT JOIN `itemTable` ON `stockTable`.`itemNumber` = `itemTable`.`itemNumber` AND `itemTable`.`language` = '$language'
                            LEFT JOIN `savedItemTable` ON `stockTable`.`itemNumber` = `savedItemTable`.`itemNumber` AND `savedItemTable`.`userNumber` = '$userNumber'
                            WHERE 1 ".$searchSql." ORDER BY RAND() limit $limit");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          //所以查询都是要用number
          $row["stockNumber"] = $row["stockId"];
          $row["itemImages"] = json_decode($row["itemImages"], true);
          $row["itemTag"] = explode(",",$row["itemTag"]);
          $row["offset"] = (int)$offset + $limit;
          $row["itemCategory"] =explode(",",$row["itemCategory"]);
          $row["itemParentCategory"] = explode(",",$row["itemParentCategory"]);
          //获取富文本
          $row["itemDescription"] = json_decode($row["itemDescription"],true);
          $row["itemQuantity"] = 0;
          $tmpdescription = array();
          foreach ($row["itemDescription"]['blocks'] as $key => $value) {
            if($value['type'] == "atomic"){
              $tmpimageurl = $row["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
              $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
            }else{
              $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
            }
          }
          $row["itemDescription"] = $tmpdescription;
          if($expectDeliverTime != $today){
            $row["stockForSell"] = -1;
          }
          $stockList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success", "data"=>$stockList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['stockNumber'])){
      $stockNumber=$data['stockNumber'];
      foreach ($stockNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `stockTable` WHERE `stockId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $stockNumber=$data['stockNumber'];
    $storeNumber=$data['storeNumber'];
    $itemNumber=$data['itemNumber'];
    $stockTotal=$data['stockTotal'];
    $stockForPickup=$data['stockForPickup'];
    $stockForSell=$data['stockForSell'];
    if(!isset($stockTotal)){
      $stockTotal = (int)$stockForPickup + (int)$stockForSell;
    }

    //修改
    if(isset($stockNumber) && $stockNumber !== ""){
      $stmt = $pdo->prepare("UPDATE `stockTable` SET `stockTotal` = '$stockTotal', `stockForPickup` = '$stockForPickup' ,
                            `stockForSell` = '$stockForSell' 
                            WHERE `stockId` = '$stockNumber'");
      $stmt->execute();
      if($stmt != null){
        echo json_encode(["message"=>$data]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `stockTable`(`storeNumber`,`itemNumber`,`stockTotal`,`stockForPickup`,`stockForSell`) 
                          VALUES ('$storeNumber','$itemNumber','$stockTotal','$stockForPickup','$stockForSell')");
    $stmt->execute();
    if($stmt != null){
      //stock不需要number
      $stockId = $pdo->lastInsertId();
      // $stockNumber= date('YmdHis').$stockId;
      // $stmt = $pdo->prepare("UPDATE `stockTable` SET `stockNumber` = '$stockNumber' 
      //                       WHERE `stockId` = '$stockId' AND `language` = '$language'");
      // $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
