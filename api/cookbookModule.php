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
    $likeLimet = YOU_LIKE_QUERY_LIMIT;

    //获取当前语言
    $language=isset($data['language'])?$data['language']:$_POST['language'];
    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $cookbookNumber=$data['cookbookNumber'];
      $cookbookCategory=$data['cookbookCategory'] == ""?null:$data['cookbookCategory'];
      $searchText=$data['searchText'];
      //搜索返回
      $limit=QUERY_LIMIT;
      $offset=isset($data['offset'])?$data['offset']:0;

      $searchSql .= isset($cookbookNumber)?"AND `cookbookNumber`='$cookbookNumber'":"";
      $searchSql .= isset($cookbookCategory)?" AND FIND_IN_SET('".$cookbookCategory."',`cookbookCategory`)":"";
      $searchSql .= isset($searchText)?"AND (`cookbookTag` LIKE '%".$searchText."%' OR `cookbookTitle` LIKE '%".$searchText."%' OR `cookbookSubTitle` LIKE '%".$searchText."%')":"";

      //如果是菜谱详情显示菜品购物车
      if(isset($cookbookNumber) && $cookbookNumber != ""){
        $userNumber=$data['userNumber'];
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
        $tmpcartList =array();
        //方便查询
        foreach ($cartList as $key => $value) {
          $tmpcartList[$value['itemNumber']] = $value['itemQuantity'];
        }
        $cartList = $tmpcartList;
      }

      $cookbookList = array();
      $stmt = $pdo->prepare("SELECT * From `cookbookTable` WHERE `language` = '$language' ".$searchSql."limit $offset,$limit");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["cookbookImages"] = json_decode($row['cookbookImages'], true);
          $row["cookbookTag"] = explode(",",$row["cookbookTag"]);
          $row["cookbookCategory"] = $tmpcookbookCategory = explode(",",$row["cookbookCategory"]);
          $row["itemList"] = json_decode($row['itemList'], true);


          $tmpitemList = array();
          foreach ($row["itemList"] as $key => $value) {
            $substmt = $pdo->prepare("SELECT * From `itemTable` WHERE `language` = '$language' AND `itemNumber` = '$value'");
            $substmt->execute();
            if($substmt != null){
              while($subrow=$substmt->fetch(PDO::FETCH_ASSOC)){
                $subrow["itemImages"] = json_decode($subrow['itemImages'], true);
                $subrow["itemTag"] = explode(",",$subrow["itemTag"]);
                $subrow["itemCategory"] = json_decode($subrow['itemCategory'], true);
                $subrow["itemParentCategory"] = json_decode($subrow['itemParentCategory'], true);
                //如果是菜谱详情显示菜品购物车
                if(isset($cookbookNumber) && $cookbookNumber != ""){
                  $subrow["itemQuantity"] = 0;
                  if(array_key_exists($subrow["itemNumber"],$cartList)){
                    $subrow["itemQuantity"] = $cartList[$subrow["itemNumber"]];
                  }
                }
 
                //获取富文本
                $subrow["itemDescription"] = json_decode($subrow["itemDescription"],true);
                $tmpdescription = array();
                foreach ($subrow["itemDescription"]['blocks'] as $key => $value) {
                  if($value['type'] == "atomic"){
                    $tmpimageurl = $subrow["itemDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
                    $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
                  }else{
                    $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
                  }
                }
                $subrow["itemDescription"] = $tmpdescription;
                // $row["courseArea"]=$courseArealist[$row["courseArea"]];
                $tmpitemList[] = $subrow;
              }
            }else{
                echo json_encode(["message"=>"database error"]);
                exit();
            }
          }
          $row["itemList"] = $tmpitemList;
          //获取富文本
          $row["cookbookDescription"] = json_decode($row["cookbookDescription"],true);
          $tmpdescription = array();
          foreach ($row["cookbookDescription"]['blocks'] as $key => $value) {
            if($value['type'] == "atomic"){
              $tmpimageurl = $row["cookbookDescription"]['entityMap'][$value['entityRanges'][0]['key']]['data']['url'];
              $tmpdescription[] = ["type"=>"image","value"=>$tmpimageurl];
            }else{
              $tmpdescription[] = ["type"=>"text","value"=>$value['text']];
            }
          }
          $row["cookbookDescription"] = $tmpdescription;
          // $row["courseArea"]=$courseArealist[$row["courseArea"]];
          $cookbookList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      //如果是菜谱详情显示推荐菜品
      if(isset($cookbookNumber) && $cookbookNumber != ""){
      	  $likeList = array();
	      $stmt = $pdo->prepare("SELECT * From `cookbookTable` WHERE `language` = '$language' AND `cookbookCategory` = '$tmpcookbookCategory' limit $likeLimet");
	      $stmt->execute();
	      if($stmt != null){
	        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
	        	$row["cookbookImages"] = json_decode($row['cookbookImages'], true);
          		$row["cookbookTag"] = explode(",",$row["cookbookTag"]);
          		$row["cookbookCategory"] = $tmpcookbookCategory = explode(",",$row["cookbookCategory"]);
	        	$likeList[] = $row;
	        }
	      }
      	$cookbookList[0]['like'] = $likeList;
      }
      echo json_encode(["message"=>"success","data"=>$cookbookList]);
      exit();
    }

    if(isset($data['isGetAdmin']) && $data['isGetAdmin'] !== ""){
      $cookbookNumber=$data['cookbookNumber'];
      $cookbookCategory=$data['cookbookCategory'];
      $searchText=$data['searchText'];
      //搜索返回
      $limit=QUERY_LIMIT;
      $offset=isset($data['offset'])?$data['offset']:0;

      $searchSql .= isset($cookbookNumber)?"AND `cookbookNumber`='$cookbookNumber'":"";
      $searchSql .= isset($cookbookCategory)?"AND `cookbookCategory`='$cookbookCategory'":"";
      $searchSql .= isset($searchText)?"AND (`cookbookTag` LIKE '%".$searchText."%' OR `cookbookTitle` LIKE '%".$searchText."%')":"";

      $cookbookList = array();
      $stmt = $pdo->prepare("SELECT * From `cookbookTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["cookbookImages"] = json_decode($row["cookbookImages"], true);
          $row["cookbookTag"] = $row["cookbookTag"] != ""?explode(",",$row["cookbookTag"]):[];
          $row["cookbookCategory"] = explode(",",$row["cookbookCategory"]);
          $row["itemList"] = json_decode($row['itemList'], true);
          // $row["courseArea"]=$courseArealist[$row["courseArea"]];
          $cookbookList[$row['cookbookNumber']][$row['language']] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      $returnItemList = array();
      foreach ($cookbookList as $key => $value) {
        $returnItemList[] = $value;
      }
      echo json_encode(["message"=>"success","data"=>$returnItemList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['cookbookNumber'])){
      $cookbookNumber=$data['cookbookNumber'];
      foreach ($cookbookNumber as $key => $value) {
        $data = $value;

        $stmt = $pdo->prepare("SELECT * From `cookbookTable` WHERE `cookbookNumber` = '$value' AND `language` = '$languageList[0]'");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            unlink($row["cookbookImages"]);
          }
        }else{
          echo json_encode(["message"=>"database error"]);
          exit();
        }

        $stmt = $pdo->prepare("DELETE FROM `cookbookTable`WHERE `cookbookNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $date = date('YmdHis');
    $cookbookNumber=$data['cookbookNumber'];
    $cookbookImages=json_encode($data['cookbookImages'], JSON_UNESCAPED_UNICODE);
    $cookbookTitle=$data['cookbookTitle'];
    $cookbookSubTitle=$data['cookbookSubTitle'];
    $cookbookDescription=$data['cookbookDescription'];
    $cookbookCategory=implode(",", $data['cookbookCategory']);

    $cookbookTag=implode(",", $data['cookbookTag'][$language]);
    
    $itemList=json_encode($data['itemList'], JSON_UNESCAPED_UNICODE);
    
    //修改
    if(isset($cookbookNumber) && $cookbookNumber !== ""){
      
      //为每种语言更新通用数据
      $stmt = $pdo->prepare("UPDATE `cookbookTable` SET 
                            `cookbookImages` = '$cookbookImages' ,`itemList` = '$itemList' ,`cookbookCategory` = '$cookbookCategory'".$picsql." 
                            WHERE `cookbookNumber` = '$cookbookNumber'");
      $stmt->execute();
      if($stmt != null){
        // if($_FILES['cookbookImages']['name'] != null){
        //   move_uploaded_file($_FILES['cookbookImages']['tmp_name'], $cookbookImages);
        // }
        //为每个语言更新相应字段
        foreach ($languageList as $key => $value) {
          $tmpcookbookTag=implode(",", $data['cookbookTag'][$value]);
          $stmt = $pdo->prepare("UPDATE `cookbookTable` SET `cookbookTitle` = '$cookbookTitle[$value]', `cookbookSubTitle` = '$cookbookSubTitle[$value]' , `cookbookDescription` = '$cookbookDescription[$value]' , `cookbookTag` = '$tmpcookbookTag'
                            WHERE `cookbookNumber` = '$cookbookNumber' AND `language` = '$value'");
          $stmt->execute();
        }
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `cookbookTable`(`cookbookImages`,`cookbookTitle`,`cookbookSubTitle`,`cookbookDescription`,`cookbookCategory`,`itemList`,`cookbookTag`,`language`)
                          VALUES ('$cookbookImages','$cookbookTitle[$language]','$cookbookSubTitle[$language]','$cookbookDescription[$language]','$cookbookCategory','$itemList','$cookbookTag','$language')");
    $stmt->execute();
    if($stmt != null){
      $cookbookId = $pdo->lastInsertId();
      $cookbookNumber= date('YmdHis').$cookbookId;
      $stmt = $pdo->prepare("UPDATE `cookbookTable` SET `cookbookNumber` = '$cookbookNumber' WHERE `cookbookId` = '$cookbookId' AND `language` = '$language'");
      $stmt->execute();
      // if($_FILES['cookbookImages']['name'] != null){
      //   move_uploaded_file($_FILES['cookbookImages']['tmp_name'], $cookbookImages);
      // }
      //为每种语言添加信息
      foreach ($languageList as $key => $value) {
        if($value != $language){
          $tmpcookbookTag=implode(",", $data['cookbookTag'][$value]);
          $stmt = $pdo->prepare("INSERT INTO `cookbookTable`(`cookbookNumber`,`cookbookImages`,`cookbookTitle`,`cookbookSubTitle`,`cookbookDescription`,`cookbookCategory`,`itemList`,`cookbookTag`,`language`)
                          VALUES ('$cookbookNumber','$cookbookImages','$cookbookTitle[$value]','$cookbookSubTitle[$value]','$cookbookDescription[$value]','$cookbookCategory','$itemList','$tmpcookbookTag','$value')");
          $stmt->execute();
        }
      }
      echo json_encode(["message"=>"success"]);
    }

  }
