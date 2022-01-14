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
    $startDate=isset($data['startDate'])? "AND DATE_FORMAT(`createTime`,'%Y') = '".$data['startDate']."'":"";
    // $endDate=isset($data['endDate'])?" AND  DATE_FORMAT(`expectDeliverTime`,'%Y-%m-%d') <= '".$data['endDate']."'":"";
    $storeNumber=$data['storeNumber'];
    $storeNumber=isset($storeNumber)?" AND `storeNumber` = '$storeNumber'":"";

    $itemlist = array();
    $sumitemlist = array();
    $itemstring = "";
    $stmt = $pdo->prepare("SELECT `orderTable`.`itemList`,`orderTable`.`storeNumber`,DATE_FORMAT(`createTime`,'%m') AS `Date` FROM `orderTable` WHERE `orderState` > '0' ".$startDate.$endDate.$storeNumber);
    $stmt->execute();
    
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $storeNumber = $row['storeNumber'];
        $row["itemList"] = json_decode($row["itemList"], true);
        $date = $row["Date"];
        foreach ($row['itemList'] as $key => $value) {
          //总数
          $itemQuantity = (int)$sumitemlist[$value['itemNumber']][$date]['itemQuantity'];
          $totalPrice = round((float)$value['itemQuantity'] * (float)$value['itemDisplayPrice'],2);
          //根据itemnumber分

          if(!array_key_exists($value['itemNumber'], $sumitemlist)){
            $sumitemlist[$value['itemNumber'].$value['itemDisplayPrice']] = ["itemNumber"=>$value['itemNumber'],
                                      "efreshgoNo"=>$value['efreshgoNo'],
                                      "itemPrice"=>$value['itemDisplayPrice'],
                                      "itemName"=>$value['itemTitle']];
          }
          $sumitemlist[$value['itemNumber'].$value['itemDisplayPrice']][$date] = [
                                                "itemQuantity"=>(int)$itemQuantity + (int)$value['itemQuantity'],
                                                "itemPrice"=>$value['itemDisplayPrice'],
                                                "totalPrice"=>round((float)$totalPrice + (float)$sumitemlist[$value['itemNumber']][$date]['totalPrice'],2)
                                                ];
          //分店铺
          $itemQuantity = (int)$itemlist[$storeNumber.$value['itemNumber']][$date]['itemQuantity'];
          $totalPrice = round((float)$value['itemQuantity'] * (float)$value['itemDisplayPrice'],2);
          //根据itemnumber分
          if(!array_key_exists($storeNumber.$value['itemNumber'], $itemlist)){
            $itemlist[$storeNumber.$value['itemNumber'].$value['itemDisplayPrice']] = ["storeNumber"=>$storeNumber,
                                                            "itemNumber"=>$value['itemNumber'],
                                                            "efreshgoNo"=>$value['efreshgoNo'],
                                                            "itemPrice"=>$value['itemDisplayPrice'],
                                                            "itemName"=>$value['itemTitle']];
          }
          $itemlist[$storeNumber.$value['itemNumber'].$value['itemDisplayPrice']][$date] = [
                                                          "itemQuantity"=>(int)$itemQuantity + (int)$value['itemQuantity'],
                                                          "itemPrice"=>$value['itemDisplayPrice'],
                                                          "totalPrice"=>round((float)$totalPrice + (float)$itemlist[$storeNumber.$value['itemNumber']][$date]['totalPrice'],2)
                                                          ];
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
