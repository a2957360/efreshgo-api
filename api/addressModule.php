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
      $addressNumber=$data['addressNumber'];
      $userNumber=$data['userNumber'];

      $searchSql .= isset($addressNumber)?" AND `addressId`='$addressNumber'":"";

      $addressList = array();
      $stmt = $pdo->prepare("SELECT * From `addressTable` WHERE `userNumber`='$userNumber' ".$searchSql."order by `addressDefault` DESC");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row['addressNumber'] = $row['addressId'];
          $row['addressGeometry'] = json_decode($row['addressGeometry'],true);
          $addressList[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }

      echo json_encode(["message"=>"success", "data"=>$addressList]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['addressNumber'])){
      $addressNumber=$data['addressNumber'];
      foreach ($addressNumber as $key => $value) {
        $data = $value;
        $stmt = $pdo->prepare("DELETE FROM `addressTable` WHERE `addressId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $addressNumber=$data['addressNumber'];
    $userNumber=$data['userNumber'];
    $addressUsername=$data['addressUsername'];
    $addressStreet=$data['addressStreet'];
    $addressRoomNo=$data['addressRoomNo'];
    $addressPhone=$data['addressPhone'];
    $addressEmail=$data['addressEmail'];
    $addressComment=$data['addressComment'];
    $addressDefault=$data['addressDefault'];
    $addressGeometry=json_encode($data['addressGeometry'], JSON_UNESCAPED_UNICODE);

    //设定默认地址
    if(isset($data['isSetDufault'])){
    	//$addressNumber=$data['addressNumber'];
    	//$userId=$data['userId'];
    	$stmt = $pdo->prepare("UPDATE `addressTable` SET `addressDefault` = '0' WHERE `userNumber` = '$userNumber'");
    	$stmt->execute();
    	$stmt = $pdo->prepare("UPDATE `addressTable` SET `addressDefault` = '1' WHERE `addressId` = '$addressNumber'");
    	$stmt->execute();
    	if($stmt != null){
	        echo json_encode(["message"=>"success"]);
	    }
	    exit();
    }

    //修改
    if(isset($addressNumber) && $addressNumber !== ""){
		if($addressDefault == 1){
	    	$stmt = $pdo->prepare("UPDATE `addressTable` SET `addressDefault` = '0' WHERE `userNumber` = '$userNumber'");
	    	$stmt->execute();
		}
      $stmt = $pdo->prepare("UPDATE `addressTable` SET `userNumber` = '$userNumber', `addressUsername` = '$addressUsername',`addressStreet` = '$addressStreet', `addressRoomNo` = '$addressRoomNo' ,
                            `addressPhone` = '$addressPhone' ,`addressEmail` = '$addressEmail' ,`addressComment` = '$addressComment' ,`addressDefault` = '$addressDefault' ,`addressGeometry` = '$addressGeometry' 
                            WHERE `addressId` = '$addressNumber'");
      $stmt->execute();
      if($stmt != null){
        echo json_encode(["message"=>"success"]);
      }
      exit();
    }

    //添加
	if($addressDefault == 1){
    	$stmt = $pdo->prepare("UPDATE `addressTable` SET `addressDefault` = '0' WHERE `userNumber` = '$userNumber'");
    	$stmt->execute();
	  }
    $stmt = $pdo->prepare("INSERT INTO `addressTable`(`userNumber`,`addressUsername`,`addressStreet`,`addressRoomNo`,`addressPhone`,`addressEmail`,`addressComment`,`addressDefault`,`addressGeometry`) 
                          VALUES ('$userNumber','$addressUsername','$addressStreet','$addressRoomNo','$addressPhone','$addressEmail','$addressComment','$addressDefault','$addressGeometry')");
    $stmt->execute();
    if($stmt != null){
      //address不需要number
      // $addressId = $pdo->lastInsertId();
      // $addressNumber= date('YmdHis').$addressId;
      // $stmt = $pdo->prepare("UPDATE `addressTable` SET `addressNumber` = '$addressNumber' 
      //                       WHERE `addressId` = '$addressId' AND `language` = '$language'");
      // $stmt->execute();
      echo json_encode(["message"=>"success","data"=>$data]);
      exit();
    }

  }
