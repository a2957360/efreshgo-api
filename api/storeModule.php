<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  require_once("calDistance.php");

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //开店时间间隔
    $hourGap = HOUR_GAP;
    //最小可配送时间
    // $minAvailableTime = MIN_AVAILABLE_TIME;
    $languageList = LANGUAGE_LIST;
    //获取当前语言
    $language=isset($data['language'])?$data['language']:$_POST['language'];

    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $userLocation=$data['userLocation'];
      $userNumber=$data['userNumber'];
      $storeNumber=$data['storeNumber'];

      $searchSql .= isset($userNumber)?" AND `managerUserNumber`='$userNumber'":"";
      $searchSql .= isset($storeNumber)?" AND `storeNumber`='$storeNumber'":"";
      $searchSql .= isset($language)?" AND `language`='$language'":"";

      $storeList = array();
      $stmt = $pdo->prepare("SELECT * FROM `storeTable`
                            LEFT JOIN `userTable` ON `userTable`.`userId` = `storeTable`.`managerUserNumber`
                            WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          // $row['storeNumber'] = $row['storeId'];
          // $row["storeImages"] = $row["storeImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/".$row["storeImages"] : "";
          $row["userNumber"] = $row["userId"];
          $row["userImages"] = str_replace("../", "", $row["userImages"]);
          $row["userImages"] = $row["userImages"] != "" ?'https://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"] : "";
          
          $row["storeLocation"] = json_decode($row["storeLocation"],true);
          $storeLocation = $row["storeLocation"];
          //计算一周时间
          $storeOpenTime = json_decode($row["storeOpenTime"],true);
          $storeInterval = $row["storeInterval"];
          $dateList = array();
          //计算当前小时向上取整 加上最小可配送时间
          $houtnow = (int)date("i")>=30?(int)date("H")+1+(int)$storeInterval:(int)date("H")+(int)$storeInterval;
          for($i = 0; $i < 7; $i++){
            $timelist = array();
            //获取周几
            $tmptime = date("w",strtotime(date("Y-m-d")." +".$i." day"));

            if(isset($storeOpenTime[$tmptime][0]) && isset($storeOpenTime[$tmptime][1])){
              for ($time=(float)$storeOpenTime[$tmptime][0]; $time < (float)$storeOpenTime[$tmptime][1]; $time+=$hourGap) { 
                $next = (float)$time+(float)$hourGap;
                $timeminute=((float)$time - (int)$time) * 60;
                $nextminute=((float)$next - (int)$next) * 60;
                $timeminute=str_pad($timeminute,2,"0",STR_PAD_LEFT);
                $nextminute=str_pad($nextminute,2,"0",STR_PAD_LEFT);
                $timehour=str_pad((int)$time,2,"0",STR_PAD_LEFT);
                $nexthour=str_pad((int)$next,2,"0",STR_PAD_LEFT);
                if($i==0 && $houtnow > $timehour){
                  continue;
                }
                $timelist[] = $timehour.":".$timeminute." - ".$nexthour.":".$nextminute;
              }
            }
            $dateList[] = ["id"=>$i,"date"=>date("Y-m-d",strtotime(date("Y-m-d")." +".$i." day")),"time"=>$timelist];
            // $timeList[] = [date("Y-m-d",strtotime(date("Y-m-d")." +".$i." day"))=>$storeOpenTime[$tmptime]];
          }
          $row['storeAddress'] = explode(",",$row['storeAddress']);
          $row['address'] = $row['storeAddress'][0];
          $row['city'] = $row['storeAddress'][1];
          $row['postal'] = $row['storeAddress'][2];
          $row['storeOpenTime'] = $dateList;
          $row["distance"] = calculateDistance($storeLocation["lat"],$storeLocation["lng"],$userLocation["lat"],$userLocation["lng"]);
          $storeList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      $storeList = quick_sort($storeList);
      echo json_encode(["message"=>"success","data"=>$storeList]);
      exit();
    }

    //后台查询
    if(isset($data['isGetAdmin']) && $data['isGet'] !== ""){
      $userNumber=$data['userNumber'];
      $storeNumber=$data['storeNumber'];

      $searchSql .= isset($userNumber)?" AND `managerUserNumber`=".$userNumber:"";
      $searchSql .= isset($storeNumber)?" AND `storeId`=".$storeNumber:"";

      $storeList = array();
      $stmt = $pdo->prepare("SELECT * FROM `storeTable` WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          // $row["storeImages"] = $row["storeImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/".$row["storeImages"] : "";
          $row["storeLocation"] = json_decode($row["storeLocation"],true);
          $row["storeOpenTime"] = json_decode($row["storeOpenTime"],true);
          $storeList[$row['storeNumber']][$row['language']] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      $returnStoreList = array();
      foreach ($storeList as $key => $value) {
        $returnStoreList[] = $value;
      }
      echo json_encode(["message"=>"success","data"=>$returnStoreList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['storeNumber'])){
      $storeNumber=$data['storeNumber'];
      foreach ($storeNumber as $key => $value) {
        // $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `storeTable` WHERE `storeNumber` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $date= date('YmdHis');
    
    $storeNumber=$data['storeNumber'];
    $storeName=$data['storeName'];
    $storeImages=$data['storeImages'];
    $storePhone=$data['storePhone'];
    $storeEmail=$data['storeEmail'];
    $storeAddress=$data['storeAddress'];
    $storeInterval=$data['storeInterval'];
    $managerUserNumber=$data['managerUserNumber'];
    $storeLocation=json_encode($data['storeLocation']);
    $storeOpenTime=json_encode($data['storeOpenTime']);

    // if($_FILES['storeImages']['name'] != null){
    //   $File_type = strrchr($_FILES['storeImages']['name'], '.'); 
    //   $storeImages = '../include/pic/storeImagess/'.$date.rand(0,9).$File_type;
    //   $picsql .= ",`storeImages`='".$storeImages."'";
    // }
    // if($storeName == "" || $storePhone == ""|| $storeEmail==""){
    //   echo json_encode(["message"=>"error"]);
    //   exit();
    // }
    //修改
    if(isset($storeNumber) && $storeNumber !== ""){
      $stmt = $pdo->prepare("UPDATE `storeTable` SET `storeImages` = '$storeImages' ,`storePhone` = '$storePhone' , `storeEmail` = '$storeEmail' ,`storeAddress` = '$storeAddress'
                            , `storeLocation` = '$storeLocation', `storeOpenTime` = '$storeOpenTime', `managerUserNumber` = '$managerUserNumber', `storeInterval` = '$storeInterval'
                            WHERE `storeNumber` = '$storeNumber'");
      $stmt->execute();
      if($stmt != null){
        //为每个语言更新相应字段
        foreach ($languageList as $key => $value) {
          $stmt = $pdo->prepare("UPDATE `storeTable` SET `storeName` = '$storeName[$value]'
                            WHERE `storeNumber` = '$storeNumber' AND `language`='$value'");
          $stmt->execute();
        }
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `storeTable`(`storeImages`,`storeName`,`storePhone`,`storeEmail`,`storeAddress`,
                          `storeLocation`,`managerUserNumber`,`storeOpenTime`,`storeInterval`,`language`) 
                          VALUES ('$storeImages','$storeName[$language]','$storePhone','$storeEmail','$storeAddress','$storeLocation','$managerUserNumber','$storeOpenTime','$storeInterval','$language')");
    $stmt->execute();
    if($stmt != null){
      $storeId = $pdo->lastInsertId();
      $storeNumber= date('YmdHis').$storeId;
      $stmt = $pdo->prepare("UPDATE `storeTable` SET `storeNumber` = '$storeNumber' 
                            WHERE `storeId` = '$storeId'");
      $stmt->execute();
      // if($_FILES['storeImages']['name'] != null){
      //   move_uploaded_file($_FILES['storeImages']['tmp_name'], $storeImages);
      // }
      //为每种语言添加信息
      foreach ($languageList as $key => $value) {
        if($value != $language){
          $stmt = $pdo->prepare("INSERT INTO `storeTable`(`storeNumber`,`storeName`,`storeImages`,`storePhone`,`storeEmail`,`storeAddress`,`storeLocation`,`managerUserNumber`,`storeOpenTime`,`language`) 
                          VALUES ('$storeNumber','$storeName[$value]','$storeImages','$storePhone','$storeEmail','$storeAddress','$storeLocation','$userNumber','$storeOpenTime','$value')");
          $stmt->execute();
        }
      }
      //为店铺添加所有商品的库存
      $stmt = $pdo->prepare("INSERT INTO `stockTable`(`storeNumber`,`itemNumber`,`stockTotal`,`stockForPickup`,`stockForSell`) 
                          SELECT '$storeNumber',`itemNumber`,'-1','-1','-1' FROM `itemTable`  GROUP BY `itemNumber`");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
