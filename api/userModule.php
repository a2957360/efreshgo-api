<?php
  include("../include/sql.php");
  include("../include/conf/config.php");
  require_once "../stripe/config.php";

  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $languageList = LANGUAGE_LIST;
    $language=isset($data['language'])?$data['language']:$_POST['language'];
    //查询
    if(isset($data['isGet']) && $data['isGet'] !== ""){
      $userRole=$data['userRole'];//0:普通;1:商家;2 骑手
      $userState=$data['userState'];//  0：未批准；1：已批准；2：黑名单;3:未提交信息4:审核未通过  
      $userNumber=$data['userNumber'];
      $userPhone=$data['userPhone'];

      $searchSql .= isset($userRole)?"AND `userRole`='$userRole'":"";
      $searchSql .= isset($userState)?"AND `userState`='$userState'":"";
      $searchSql .= isset($userNumber)?"AND `userId`='$userNumber'":"";

      if($userRole == 1){
       $leftSql .= "LEFT JOIN `storeTable` ON `storeTable`.`managerUserNumber` = `userTable`.`userId` AND `language`='$language'";
      }else if($userRole == 2){
       $leftSql .= "LEFT JOIN `driverTable` ON `driverTable`.`userNumber` = `userTable`.`userId`";
      }
      $userlist = array();
      $stmt = $pdo->prepare("SELECT * From `userTable` 
      						".$leftSql."  WHERE 1 ".$searchSql);
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["userNumber"] = $row["userId"];
          $row['driverNumber'] = $row['driverId'];
          if($row["userImages"] != ""){
            if(strpos($row["userImages"],'http') !== false){
              $row["userImages"] = $row["userImages"];
            }else{
              $row["userImages"] = str_replace("../", "", $row["userImages"]);
              $row["userImages"] = 'https://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"];
            }
          }else{
            $row["userImages"] = "";
          }
          // $row["userImages"] = str_replace("../", "", $row["userImages"]);
          // $row["userImages"] = $row["userImages"] != "" ?'http://'.$_SERVER['SERVER_NAME']."/".$row["userImages"] : "";
          //获取订单总数
          if(isset($userNumber)){
            $row['orderInfo']=["0"=>0,"wait"=>0,"6"=>0,"7"=>0];
          	//如果是客户获取各类订单数量
          	if($userRole == 0){
              $orderstmt = $pdo->prepare("SELECT `orderState`, count(*) AS `Sum` From `orderTable` WHERE `userNumber` = '$userNumber' GROUP BY `orderState`");
      				$orderstmt->execute();
      				if($orderstmt != null){
      					while($orderrow=$orderstmt->fetch(PDO::FETCH_ASSOC)){
                  if($orderrow['orderState'] != ""){
                    if((int)$orderrow['orderState'] > 0 && (int)$orderrow['orderState'] <= 5){
                      $row['orderInfo']['wait']=(int)$row['orderInfo']['wait']+(int)$orderrow['Sum'];
                    }else{
                      $row['orderInfo'][$orderrow['orderState']]=(int)$orderrow['Sum'];
                    }
                  }

      					}
      				}else{
      				  echo json_encode(["message"=>"database error"]);
      				  exit();
      				}	
          	}else{
          		//商家和骑手
              $thismonth = date("Y-m");
              $orderstmt = $pdo->prepare("SELECT `orderState`, count(*) AS `Sum` From `orderTable` where  DATE_FORMAT(`create`,'%Y%m') = '$thismonth'");
      				$orderstmt->execute();
      				if($orderstmt != null){
      					while($orderrow=$orderstmt->fetch(PDO::FETCH_ASSOC)){
      						if((int)$orderrow['orderState'] > 0 && (int)$orderrow['orderState'] <= 5){
      					  		$row['orderInfo']['wait']=(int)$row['orderInfo']['wait']+(int)$orderrow['Sum'];
      						}else{
      					  		$row['orderInfo'][$orderrow['orderState']]=$orderrow['Sum'];
      						}
      					}
      				}else{
      				  echo json_encode(["message"=>"database error"]);
      				  exit();
      				} 		
          	}
	      }
          $userlist[] = $row;
        }
      }else{
          echo json_encode(["message"=>"database error"]);
          exit();
      }
      if(isset($userNumber)){
        $returndata = $userlist[0];
      }else{
        $returndata = $userlist;
      }

      echo json_encode(["message"=>"success","data"=>$returndata]);
      exit();
    }

    //删除
    if(isset($data['isDelete']) && isset($data['userNumber'])){
      $userNumber=$data['userNumber'];
      foreach ($userNumber as $key => $value) {
        $data = $value;

        $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$value'");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            unlink($row["userImages"]);
          }
        }else{
          echo json_encode(["message"=>"database error"]);
          exit();
        }

        $stmt = $pdo->prepare("DELETE FROM `userTable` WHERE `userId` = '$value'");
        $stmt->execute();
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

    // 修改骑手状态
    if(isset($data['isChangeState'])){
      $userNumber = $data['userNumber'];
      $userState = $data['userState'];
      $stmt = $pdo->prepare("UPDATE `userTable` SET `userState` = '$userState' WHERE `userId` = '$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"success"]);
      exit();
    }

    //添加/修改
    $date = date('YmdHis');

    $userNumber=$_POST['userNumber'];
    $userName=$_POST['userName'];
    $userPhone=$_POST['userPhone'];
    $userEmail=$_POST['userEmail'];
    $userRole=$_POST['userRole'];    
    $userExpoToken=$_POST['userExpoToken'];
    //用户状态
    $userState= "";
    //stripe token
    $userStripeToken= "";
    if($userRole == 0){
		$userState = 0;
		//插入stripe costomer
		try {
		  $customer = \Stripe\Customer::create([
		    'name'  => $userPhone
		  ]);
		} catch(Exception $e) {
          echo json_encode(["message"=>"stripe error"]);
          exit();
		}
		$userStripeToken = $customer["id"];
	}else if($userRole == 2){
		$userState = 3;
		$userStripeToken = "";
	}

    if($_FILES['userImages']['name'] != null){
      $File_type = strrchr($_FILES['userImages']['name'], '.'); 
      $userImages = '../include/pic/userImages/'.$date.rand(0,9).$File_type;
      $picsql .= ",`userImages`='".$userImages."'";
    }

    if(isset($data['setExpoToken'])){
      $userNumber = $data['userNumber'];
      $userExpoToken = $data['userExpoToken'];
      $stmt = $pdo->prepare("UPDATE `userTable` SET `userExpoToken` = '$userExpoToken' WHERE `userId` = '$userNumber'");
      $stmt->execute();
      echo json_encode(["message"=>"sucess"]);
      exit();
    }
    //修改
    if(isset($userNumber) && $userNumber !== ""){
      if(!isset($userName) || !isset($userEmail)){
        exit();
      }
      if($_FILES['userImages']['name'] != null){
        $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
        $stmt->execute();
        if($stmt != null){
          while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            unlink($row["userImages"]);
          }
        }else{
          echo json_encode(["message"=>"database error"]);
          exit();
        }
      }

      $stmt = $pdo->prepare("UPDATE `userTable` SET `userName` = '$userName' ,`userEmail` = '$userEmail' ".$picsql." WHERE `userId` = '$userNumber'");
      $stmt->execute();
      if($stmt != null){
        if($_FILES['userImages']['name'] != null){
          move_uploaded_file($_FILES['userImages']['tmp_name'], $userImages);
        }
      $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
	    $stmt->execute();
	    if($stmt != null){
	      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $row["userNumber"] = $row["userId"];
          $row["userImages"] = str_replace("../", "", $row["userImages"]);
          $row["userImages"] = $row["userImages"] != "" ?'https://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"] : "";
	      	$data = $row;
	      }
	    }else{
	      echo json_encode(["message"=>"database error"]);
	      exit();
	    }
        echo json_encode(["message"=>"success","data"=>[$data]]);
      }
      exit();
    }

    if(!isset($userName) || !isset($userPhone) || !isset($userRole)){
      exit();
    }
    $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userPhone` = '$userPhone' AND `userRole` = '$userRole'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        if(isset($row['userId']) && $row['userId'] != "" && $row['userId'] != null){
          echo json_encode(["message"=>"exist phone"]);
          exit();
        }
      }
    }else{
      echo json_encode(["message"=>"database error"]);
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `userTable`(`userImages`,`userName`,`userPhone`,`userEmail`,`userRole`,`userState`,`userStripeToken`)
                          VALUES ('$userImages','$userName','$userPhone','$userEmail','$userRole','$userState','$userStripeToken')");
    $stmt->execute();
    if($stmt != null){
      $userNumber = $pdo->lastInsertId();
	    $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
	    $stmt->execute();
	    if($stmt != null){
	      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $row["userImages"] = str_replace("../", "", $row["userImages"]);
          	$row["userImages"] = $row["userImages"] != "" ?'https://'.$_SERVER['SERVER_NAME']."/app/".$row["userImages"] : "";
	      	$data = $row;
	      }
	    }else{
	      echo json_encode(["message"=>"database error"]);
	      exit();
	    }
	    if($_FILES['userImages']['name'] != null){
	      move_uploaded_file($_FILES['userImages']['tmp_name'], $userImages);
	    }
	    echo json_encode(["message"=>"success","data"=>$data]);
    }

  }
