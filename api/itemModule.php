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
    //获取当前语言
    $language=isset($data['language'])?$data['language']:$_POST['language'];

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $itemNumber=$data['itemNumber'];
      $itemCategory=$data['itemCategory'];
      $itemParentCategory=$data['itemParentCategory'];
      $searchText=$data['searchText'];

      $searchSql .= isset($itemNumber)?"AND `itemNumber`=".$itemNumber:"";
      $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";
      $searchSql .= isset($itemParentCategory)?" AND FIND_IN_SET('".$itemParentCategory."',`itemTable`.`itemParentCategory`)":"";
      $searchSql .= isset($searchText)?"AND (`itemTag` LIKE '%".$searchText."%' OR `itemTitle` LIKE '%".$searchText."%')":"";

      $itemlist = array();
      $stmt = $pdo->prepare("SELECT * From `itemTable` WHERE `language` = '$language' ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["itemImages"] = json_decode($row['itemImages'], true);
          $row["itemTag"] = explode(",",$row["itemTag"]);
          $row["itemCategory"] = json_decode($row['itemCategory'], true);
          $row["itemParentCategory"] = json_decode($row['itemParentCategory'], true);
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
          // $row["courseArea"]=$courseArealist[$row["courseArea"]];
          $itemlist[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success","data"=>$itemlist]);
      exit();
    }

    //后台列表
    if(isset($data['isGetAdmin']) && $data['isGetAdmin'] !== ""){
      $itemNumber=$data['itemNumber'];
      $itemCategory=$data['itemCategory'];
      $itemParentCategory=$data['itemParentCategory'];
      $searchText=$data['searchText'];

      $searchSql .= isset($itemNumber)?"AND `itemNumber`=".$itemNumber:"";
      $searchSql .= isset($itemCategory)?" AND FIND_IN_SET('".$itemCategory."',`itemTable`.`itemCategory`)":"";
      $searchSql .= isset($itemParentCategory)?" AND FIND_IN_SET('".$itemParentCategory."',`itemTable`.`itemParentCategory`)":"";
      $searchSql .= isset($searchText)?"AND (`itemTag` LIKE '%".$searchText."%' OR `itemTitle` LIKE '%".$searchText."%')":"";

      $itemlist = array();
      $stmt = $pdo->prepare("SELECT * From `itemTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          //为每个图片添加url
          $row["itemImages"] = json_decode($row["itemImages"], true);
          $row["itemTag"] = $row["itemTag"] != ""?explode(",",$row["itemTag"]):[];
          $row["itemCategory"] =explode(",",$row["itemCategory"]);
          $row["itemParentCategory"] = explode(",",$row["itemParentCategory"]);
          $row["itemDescription"] = json_decode($row["itemDescription"],true);
          $row["itemPrice"] = (float)$row["itemPrice"];
          // $row["courseArea"]=$courseArealist[$row["courseArea"]];
          $itemlist[$row['itemNumber']][$row['language']] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      $returnItemList = array();
      foreach ($itemlist as $key => $value) {
        $returnItemList[] = $value;
      }

      echo json_encode(["message"=>"success","data"=>$returnItemList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['itemNumber'])){
      $itemNumber=$data['itemNumber'];
      foreach ($itemNumber as $key => $value) {
        $data = $value;

        // $stmt = $pdo->prepare("SELECT * From `itemTable` WHERE `itemNumber` = '$value' AND `language` = '$languageList[0]'");
        // $stmt->execute();
        // if($stmt != null){
        //   while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        //     unlink($row["itemImages"]);
        //   }
        // }else{
        //   echo json_encode(["message"=>"database error"]);
        //   exit();
        // }

        $stmt = $pdo->prepare("DELETE FROM `itemTable` WHERE `itemNumber` = '$value'");
        $stmt->execute();
        $stmt = $pdo->prepare("DELETE FROM `stockTable` WHERE `itemNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //上架下架
    if(isset($data['isChangeState']) && isset($data['isChangeState'])){
      $itemNumber=$data['itemNumber'];
      $itemState=$data['itemState'];
      $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemState` = '$itemState' WHERE `itemNumber` = '$itemNumber'");
      $stmt->execute();
      if($stmt != null){

      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }

      echo json_encode(["message"=>"success"]);
      exit();
    }


    //添加/修改
    $date = date('YmdHis');

    $itemNumber=$data['itemNumber'];
    $efreshgoNo=$data['efreshgoNo'];
    $itemImages=json_encode($data['itemImages'], JSON_UNESCAPED_UNICODE);
    $itemTitle=$data['itemTitle'];
    $itemSubTitle=$data['itemSubTitle'];
    $itemDescription=json_encode($data['itemDescription'], JSON_UNESCAPED_UNICODE);
    $itemUnit=$data['itemUnit'];    
    $itemPrice=$data['itemPrice'];
    $itemSalesPrice=$data['itemSalesPrice'];
    //最小购买单位
    $minimumUnit=$data['minimumUnit'];
    //判断是否有优惠价格 displayprice存储优惠价格
    $itemDisplayPrice=$data['itemSalesPrice'] != 0?$data['itemSalesPrice']:$itemPrice;
    //给上传的分类转换成字符串
    $itemCategory= isset($data['itemCategory'])?implode(",",$data['itemCategory']):"";
    $itemParentCategory= isset($data['itemParentCategory'])?implode(",",$data['itemParentCategory']):"";
    $itemTag=implode(",", $data['itemTag'][$language]);
    $isTaxable=$data['isTaxable'];

    // if($_FILES['itemImages']['name'] != null){
    //   $File_type = strrchr($_FILES['itemImages']['name'], '.'); 
    //   $itemImages = '../include/pic/itemImages/'.$date.rand(0,9).$File_type;
    //   $picsql .= ",`itemImages`='".$itemImages."'";
    // }

    //修改
    if(isset($itemNumber) && $itemNumber !== ""){

      // if($_FILES['itemImages']['name'] != null){
      //   $stmt = $pdo->prepare("SELECT * From `itemTable` WHERE `itemNumber` = '$itemNumber' AND `language` = '$languageList[0]'");
      //   $stmt->execute();
      //   if($stmt != null){
      //     while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      //       unlink($row["itemImages"]);
      //     }
      //   }else{
      //     echo json_encode(["message"=>"database error"]);
      //     exit();
      //   } 
      // }

      //为每种语言更新通用数据
      $stmt = $pdo->prepare("UPDATE `itemTable` SET 
                             `efreshgoNo` = '$efreshgoNo' ,`itemPrice` = '$itemPrice' ,`itemImages` = '$itemImages' , `itemSalesPrice` = '$itemSalesPrice' , `itemDisplayPrice` = '$itemDisplayPrice' ,`itemCategory` = '$itemCategory' ,`minimumUnit` = '$minimumUnit' ,
                              `itemParentCategory` = '$itemParentCategory', `isTaxable` = '$isTaxable'".$picsql." WHERE `itemNumber` = '$itemNumber'");
      $stmt->execute();
      if($stmt != null){
        // if($_FILES['itemImages']['name'] != null){
        //   move_uploaded_file($_FILES['itemImages']['tmp_name'], $itemImages);
        // }
        //为每个语言更新相应字段
        foreach ($languageList as $key => $value) {
          $tmpitemDescription=json_encode($data['itemDescription'][$value], JSON_UNESCAPED_UNICODE);
          $tmpitemTag=implode(",", $data['itemTag'][$value]);
          $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemTitle` = '$itemTitle[$value]', `itemSubTitle` = '$itemSubTitle[$value]' , `itemDescription` = '$tmpitemDescription',
                                `itemUnit` = '$itemUnit[$value]' ,`itemTag` = '$tmpitemTag' 
                                 WHERE `itemNumber` = '$itemNumber' AND `language` = '$value'");
          $stmt->execute();
        }
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    $tmpitemDescription=json_encode($data['itemDescription'][$language], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO `itemTable`(`efreshgoNo`,`itemImages`,`itemTitle`,`itemSubTitle`,`itemDescription`,`itemUnit`,`minimumUnit`,`itemPrice`,`itemSalesPrice`,`itemDisplayPrice`,`itemCategory`,`itemParentCategory`,`itemTag`,`isTaxable`,`language`)
                          VALUES ('$efreshgoNo','$itemImages','$itemTitle[$language]','$itemSubTitle[$language]','$itemDescription[$language]','$itemUnit[$language]','$minimumUnit','$itemPrice','$itemSalesPrice','$itemDisplayPrice','$itemCategory','$itemParentCategory','$itemTag','$isTaxable','$language')");
    $stmt->execute();
    if($stmt != null){
      $itemId = $pdo->lastInsertId();
      $itemNumber= date('YmdHis').$itemId;
      $stmt = $pdo->prepare("UPDATE `itemTable` SET `itemNumber` = '$itemNumber' WHERE `itemId` = '$itemId' AND `language` = '$language'");
      $stmt->execute();
      // if($_FILES['itemImages']['name'] != null){
      //   move_uploaded_file($_FILES['itemImages']['tmp_name'], $itemImages);
      // }
      //为每种语言添加信息
      foreach ($languageList as $key => $value) {
        if($value != $language){
          $tmpitemTag=implode(",", $data['itemTag'][$value]);
          $tmpitemDescription=json_encode($data['itemDescription'][$value], JSON_UNESCAPED_UNICODE);
          $stmt = $pdo->prepare("INSERT INTO `itemTable`(`itemNumber`,`itemImages`,`itemTitle`,`itemSubTitle`,`itemDescription`,`itemUnit`,`itemPrice`,`itemSalesPrice`,`itemDisplayPrice`,`itemCategory`,`itemParentCategory`,`itemTag`,`isTaxable`,`language`)
                          VALUES ('$itemNumber','$itemImages','$itemTitle[$value]','$itemSubTitle[$value]','$itemDescription[$value]','$itemUnit[$value]','$itemPrice','$itemSalesPrice','$itemDisplayPrice','$itemCategory','$itemParentCategory','$tmpitemTag','$isTaxable','$value')");
          // $stmt = $pdo->prepare("INSERT INTO `itemTable`(`itemNumber`,`itemImages`,`itemPrice`,`itemSalesPrice`,`itemDisplayPrice`,`itemCategory`,`itemParentCategory`,`isTaxable`,`language`) VALUES ('$itemNumber','$itemImages','$itemPrice','$itemSalesPrice','$itemDisplayPrice','$itemCategory','$itemParentCategory','$isTaxable','$value')");
          $stmt->execute();
        }
      }
      //为店铺添加所有商品的库存
      $stmt = $pdo->prepare("INSERT INTO `stockTable`(`storeNumber`,`itemNumber`,`stockTotal`,`stockForPickup`,`stockForSell`) 
                          SELECT `storeNumber`,'$itemNumber','-1','-1','-1' FROM `storeTable` GROUP BY `storeNumber`");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
    }

  }
