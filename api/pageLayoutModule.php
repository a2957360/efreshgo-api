<?php
  include("../include/sql.php");
  require_once("arrayImages.php");
  include("../include/conf/config.php");

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
      $languageList = LANGUAGE_LIST;
      //以防多页面
      // $pageId=$data['pageId'];

      $pageNumber="0";
      $language=$data['language'];

      $pageList = array();
      $stmt = $pdo->prepare("SELECT * From `pageLayoutTable` WHERE `pageNumber`='$pageNumber'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["componentNumber"] = $row["componentId"];
          // 0:图片；1：产品；2：菜谱； 
          switch ($row['componentType']) {
            case '1':
                $tmparray = array();
                $itemList = json_decode($row["componentContent"],true);
                $itemList = implode(",", $itemList);
                $substmt = $pdo->prepare("SELECT * From `itemTable` WHERE `itemNumber` IN ($itemList) AND `language` = '$language' order by field(`itemNumber`,$itemList)");
                $substmt->execute();
                if($substmt != null){
                  while($subrow=$substmt->fetch(PDO::FETCH_ASSOC)){
                    $subrow["itemImages"] = json_decode($subrow["itemImages"], true);
                    $subrow["itemTag"] = explode(",",$subrow["itemTag"]);
                    $subrow["itemCategory"] =explode(",",$subrow["itemCategory"]);
                    $subrow["minimumUnit"] = (float)$subrow["minimumUnit"];
                    $subrow["itemParentCategory"] = explode(",",$subrow["itemParentCategory"]);
                    $subrow["itemQuantity"] = 0;
                    $tmparray[] = $subrow;
                  }
                }
                // echo $row["componentTitle"].PHP_EOL;
                // var_dump($tmparray);
                $row["componentContent"] = $tmparray;
              break;
            case '2':
                $tmparray = array();
                $itemList = json_decode($row["componentContent"],true);
                $itemList = implode(",", $itemList);
                $substmt = $pdo->prepare("SELECT * From `cookbookTable` WHERE `cookbookNumber` IN ('$itemList') AND `language` = '$language' order by field(`cookbookNumber`,$itemList)");
                $substmt->execute();
                if($substmt != null){
                  while($subrow=$substmt->fetch(PDO::FETCH_ASSOC)){
                    $subrow["cookbookImages"] = json_decode($subrow['cookbookImages'], true);
                    $subrow["cookbookTag"] = explode(",",$subrow["cookbookTag"]);
                    $subrow["cookbookCategory"] = explode(",",$subrow["cookbookCategory"]);
                    $tmparray[] = $subrow;
                  }
                }
                $row["componentContent"] = $tmparray;
              break;
            case '3':
                $tmparray = array();
                $categoryList = json_decode($row["componentContent"],true);
                foreach ($categoryList as $key => $value) {
                  if($key == 0){
                    $searchcCategoryList = $value['categoryNumber'];
                  }else{
                    $searchcCategoryList .= ",".$value['categoryNumber'];
                  }
                }
                $substmt = $pdo->prepare("SELECT * From `categoryTable` WHERE `categoryNumber` IN ($searchcCategoryList) AND `language` = '$language' order by field(`categoryNumber`,$searchcCategoryList)");
                $substmt->execute();
                if($substmt != null){
                  while($subrow=$substmt->fetch(PDO::FETCH_ASSOC)){
                    $tmparray[] = $subrow;
                  }
                }
                $row["componentContent"] = $tmparray;
              break;
            default:
              $row["componentContent"] = json_decode($row["componentContent"],true);
              break;

          }
          $pageList[] = $row;
        }
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }
      // $pageList = quick_sort($pageList);
      echo json_encode(["message"=>"success","data"=>$pageList]);
      exit();
    }

    //后台查询
    if(isset($data['isGetAdmin']) && $data['isGetAdmin'] !== ""){
      $languageList = LANGUAGE_LIST;
      //以防多页面
      // $pageId=$data['pageId'];

      $pageNumber="0";
      $language=$data['language'];

      $pageList = array();
      $stmt = $pdo->prepare("SELECT * From `pageLayoutTable` WHERE `pageNumber`='$pageNumber'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["componentNumber"] = $row["componentId"];
          $row["componentContent"] = json_decode($row["componentContent"],true);
          $pageList[] = $row;
        }
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }
      // $pageList = quick_sort($pageList);
      echo json_encode(["message"=>"success","data"=>$pageList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['pageLayoutNumber'])){
      $pageLayoutNumber=$data['pageLayoutNumber'];
      foreach ($pageNumber as $key => $value) {
        $stmt = $pdo->prepare("DELETE FROM `pageTable`WHERE `pageLayoutId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    // $pageNumber=$_POST['pageNumber'];
    $date= date('YmdHis');
    $pageNumber=0;
    //pageLayout数组
    $componentNumber=$data['componentNumber'];
    $componentContent = json_encode($data['componentContent'],JSON_UNESCAPED_UNICODE);
    $language = $_POST['language'];

    // $componentContent = array();
    //判断是否上传图片
    // if($componentType == '0'){
    //   $componentImages = reArrayFiles($_FILES['componentContent']);
    //   foreach ($componentImages as $imagekey => $imagevalue) {
    //     if($imagevalue['name'] != null){
    //       $File_type = strrchr($imagevalue['name'], '.'); 
    //       $pageImage = '../include/pic/componentImages/'.$date.$imagekey.rand(0,9).$File_type;
    //       //上传图片
    //       move_uploaded_file($imagevalue['tmp_name'], $pageImage);
    //       // $picsql .= ",`componentImages`='".$pageImage."'";
    //       $componentContent[] = 'http://'.$_SERVER['SERVER_NAME']."/".str_replace("../","",$pageImage);
    //     }
    //   }
    // }
    // $componentContent=$componentType == '0'?$componentContent:$_POST['componentContent'];
    //修改

    //为每种语言更新通用数据
    $stmt = $pdo->prepare("UPDATE `pageLayoutTable` SET `componentContent` = '$componentContent' WHERE `componentId` = '$componentNumber'");
    $stmt->execute();
    if($stmt != null){
      echo json_encode(["message"=>"success"]);
    }
    exit();

    
    $stmt = $pdo->prepare("INSERT INTO `pageLayoutTable`(`pageNumber`,`componentTitle`,`componentContent`,`componentType`) 
                          VALUES ('$pageNumber','$componentTitle','$componentContent','$componentType')");
    $stmt->execute();
    if($stmt != null){
    }
    echo json_encode(["message"=>"success"]);
    exit();

  }
