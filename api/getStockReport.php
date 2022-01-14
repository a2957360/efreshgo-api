<?php
  include("../include/sql.php");
  include("sendNotifition.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // $queryDate=isset($data['queryDate'])?$data['queryDate']:date("Y-m-d");
    // $startDate=isset($data['startDate'])? $data['startDate']:date("Y-m-d");
    // $endDate=isset($data['endDate'])?$data['endDate']:date("Y-m-d");
    if($data['queryMonth']){
      $queryMonth = $data['queryMonth'];
      $startDate = date("Y-m-d 20:00:00",strtotime("$queryMonth -1 day")); 
      $endDate = date("Y-m-d 20:00:00",strtotime("$queryMonth +1 month -1 day"));
    }else{
      $startDate=$data['startDate'];
      $endDate=$data['endDate'];
    }
    $startDate=isset($startDate)? "AND DATE_FORMAT(`createTime`,'%Y-%m-%d %H:$i:%s') >= '".$startDate."'":"";
    $endDate=isset($endDate)?" AND  DATE_FORMAT(`createTime`,'%Y-%m-%d %H:$i:%s') <= '".$endDate."'":"";

    $storeNumber=$data['storeNumber'];
    $storeNumber=isset($storeNumber)?" AND `storeNumber` = '$storeNumber'":"";
    //获取全部产品
    $allItemList = array();
    $stmt = $pdo->prepare("SELECT `itemSaleNum`,`itemNumber`,`itemTitle` FROM `itemTable` WHERE `language` = 'Zh'");
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $allItemList[$row['itemNumber']] = $row;
      }
    }

    $itemlist = array();
    $sumitemlist = array();
    $itemstring = "";
    $stmt = $pdo->prepare("SELECT `orderTable`.`itemList`,`orderTable`.`storeNumber`FROM `orderTable` WHERE `orderState` > '0' ".$startDate.$endDate.$storeNumber);
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $storeNumber = $row['storeNumber'];
        $row["itemList"] = json_decode($row["itemList"], true);
        foreach ($row['itemList'] as $key => $value) {
          //总数
          $itemQuantity = (int)$sumitemlist[$value['itemNumber'].$value['itemDisplayPrice']]['itemQuantity'];
          $totalPrice = round((float)((int)$itemQuantity + (int)$value['itemQuantity']) * (float)$value['itemDisplayPrice'],2);

          $amountSale = $allItemList[$value['itemNumber']]== null?"0":$allItemList[$value['itemNumber']]['itemSaleNum'];
          $amountPrice = round((float)$amountSale* (float)$value['itemDisplayPrice'],2);
          $itemName = $allItemList[$value['itemNumber']]['itemTitle'];

          $sumitemlist[$value['itemNumber'].$value['itemDisplayPrice']] = ["itemNumber"=>$value['itemNumber'],"efreshgoNo"=>$value['efreshgoNo'],"itemName"=>$itemName,"itemPrice"=>$value['itemDisplayPrice'],"itemQuantity"=>(int)$itemQuantity + (int)$value['itemQuantity'],"totalPrice"=>$totalPrice,
            "amountSale"=>$amountSale,
            "amountPrice"=>$amountPrice];
              //分店铺
          $itemQuantity = (int)$itemlist[$storeNumber.$value['itemNumber'].$value['itemDisplayPrice']]['itemQuantity'];
          $totalPrice = round((float)((int)$itemQuantity + (int)$value['itemQuantity']) * (float)$value['itemDisplayPrice'],2);

          $itemlist[$storeNumber.$value['itemNumber'].$value['itemDisplayPrice']] = ["storeNumber"=>$storeNumber,"itemNumber"=>$value['itemNumber'],"efreshgoNo"=>$value['efreshgoNo'],"itemName"=>$itemName,"itemPrice"=>$value['itemDisplayPrice'],"itemQuantity"=>(int)$itemQuantity + (int)$value['itemQuantity'],"totalPrice"=>$totalPrice,
            "amountSale"=>$amountSale,
            "amountPrice"=>$amountPrice];
          // $itemstring = $itemstring.$value['itemNumber'].",";
        }
      }
    }
    $itemlist = array_values($itemlist);
    $sumitemlist = array_values($sumitemlist);

    $returnData=["storeData"=>$itemlist,"sumData"=>$sumitemlist];
    echo json_encode(["message"=>"success","data"=>$returnData]);
    exit();
  }
