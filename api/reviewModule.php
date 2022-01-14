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
      $reviewNumber=$data['reviewNumber'];
      $userNumber=$data['userNumber'];
      $orderNumber=$data['orderNumber'];

      $searchSql .= isset($reviewNumber)?" AND `reviewTable`.`reviewNumber`=".$reviewNumber:"";
      $searchSql .= isset($userNumber)?" AND `orderTable`.`userNumber`=".$userNumber:"";
      $searchSql .= isset($orderNumber)?" AND `orderTable`.`orderId`=".$orderNumber:"";

      $reviewList = array();
      $stmt = $pdo->prepare("SELECT * From `reviewTable`
                              LEFT JOIN `orderTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                              WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['reviewNumber'] = $row['reviewId'];
          $reviewList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      echo json_encode(["message"=>"success", "data"=>$reviewList]);
      exit();
    }

    if(isset($data['isChange']) && $data['isChange'] !== ""){
      $orderNumber=$data['orderNumber'];

      $sql .= isset($data['storeRate'])?", `storeRate` = '".$data['storeRate']."'":"";
      $sql .= isset($data['storeReview'])?", `storeReview` = '".$data['storeReview']."'":"";
      $sql .= isset($data['driverRate'])?", `driverRate` = '".$data['driverRate']."'":"";
      $sql .= isset($data['driverReview'])?", `driverReview` = '".$data['driverReview']."'":"";
      $sql .= isset($data['driverForUserRate'])?", `driverForUserRate` = '".$data['driverForUserRate']."'":"";
      $sql .= isset($data['driverForUserReview'])?", `driverForUserReview` = '".$data['driverForUserReview']."'":"";
      $sql .= isset($data['storeForUserRate'])?", `storeForUserRate` = '".$data['storeForUserRate']."'":"";
      $sql .= isset($data['storeForUserReview'])?", `storeForUserReview` = '".$data['storeForUserReview']."'":"";
      $sql .= isset($data['refundImage'])?", `refundImage` = '".$data['refundImage']."'":"";
      $sql .= isset($data['refundReview'])?", `refundReview` = '".$data['refundReview']."'":"";
      $sql .= isset($data['refundReason'])?", `refundReason` = '".$data['refundReason']."'":"";

      $stmt = $pdo->prepare("UPDATE `reviewTable` SET `createTime` = 'CURRENT_TIMESTAMP()'".$sql."
                            WHERE `orderNumber` = '$orderNumber'");
      $stmt->execute();

      $stmt = $pdo->prepare("SELECT `storeNumber`,`driverNumber` From `orderTable` WHERE `orderId` = '$orderNumber' ");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $storeNumber = $row['storeNumber'];
          $driverNumber = $row['driverNumber'];
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      if(isset($data['storeRate'])){
        $stmt = $pdo->prepare("UPDATE `storeTable` SET `storeRate` = (
                              select sum(`storeRate`)/count(*) from `orderTable` 
                              LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                              WHERE `orderTable`.`storeNumber` = '$storeNumber' AND `reviewTable`.`reviewId` != '') 
                              WHERE `storeNumber` = '$storeNumber'");
        $stmt->execute();
      }

      if(isset($data['driverRate'])){
        $stmt = $pdo->prepare("UPDATE `userTable` SET `userRate` = (
                              select sum(`driverRate`)/count(*) from `orderTable` 
                              LEFT JOIN `reviewTable` ON `reviewTable`.`orderNumber` = `orderTable`.`orderId` 
                              WHERE `orderTable`.`driverNumber` = '$driverNumber' AND `reviewTable`.`reviewId` != '') 
                              WHERE `userId` = (SELECT `userNumber` FROM `driverTable` WHERE `driverId` = '$driverNumber')");
        $stmt->execute();
      }
    }


    //删除
    if(isset($data['isDelete']) && isset($data['reviewNumber'])){
      $reviewNumber=$data['reviewNumber'];
      foreach ($reviewNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `reviewTable` WHERE `reviewId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }


  }
