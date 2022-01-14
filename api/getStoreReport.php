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

    // $queryDate=isset($data['queryDate'])?$data['queryDate']:date("Y-m-d");
    // $startDate=isset($data['startDate'])?$data['startDate']:date("Y-m-d");
    // $endDate=isset($data['endDate'])?$data['endDate']:date("Y-m-d");
    $startDate=isset($data['startDate'])? "AND DATE_FORMAT(`expectDeliverTime`,'%Y-%m-%d') >= '".$data['startDate']."'":"";
    $endDate=isset($data['endDate'])?" AND  DATE_FORMAT(`expectDeliverTime`,'%Y-%m-%d') <= '".$data['endDate']."'":"";
    $storeNumber=$data['storeNumber'];
    $storeNumber=isset($storeNumber)?"AND `storeNumber` = '$storeNumber'":"";


    $returnData = array();
    $stmt = $pdo->prepare("SELECT `orderTable`.*,`orderTable`.`storeNumber`,`driverTable`.`driverName` FROM `orderTable` 
                          LEFT JOIN `driverTable` ON `driverTable`.`driverId` = `orderTable`.`driverNumber`
                          WHERE (`orderState` >= '6' AND `orderState` <= '8' OR `orderState` = '11')".$startDate.$endDate.$storeNumber);
    $stmt->execute();
    if($stmt != null){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $storeItemPriceRate = STORE_ITEM_PRICE;
        $storeSelfDeliverPriceRate = STORE_SELF_DELIVER_PRICE;
        $storeDriverDeliverPriceRate = STORE_DRIVER_DELIVER_PRICE;
        unset($row['itemList']);
        $storeNumber = $row['storeNumber'];
        $row['storeItemPrice'] = round(((float)$row['itemPrice'] * (float)$storeItemPriceRate),2);
        $row['storeDeliverPrice'] = $row['deliverType'] == 1?$storeDriverDeliverPriceRate:$storeSelfDeliverPriceRate;
        $returnData[]=$row;
      }
    }

    echo json_encode(["message"=>"success","data"=>$returnData]);
    exit();
  }
