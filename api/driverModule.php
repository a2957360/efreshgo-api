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
    //添加/修改
    $date = date('YmdHis');

    $driverNumber=$_POST['driverNumber'];
    $driverName=$_POST['driverName'];
    $userNumber=$_POST['userNumber'];
    $carModle=$_POST['carModle'];
    $driverAddress=$_POST['driverAddress'];
    $drvierPhone=$_POST['drvierPhone'];
    $driverSin=$_POST['driverSin'];  

    // userNumber是否发送
    if($userNumber == "" || $userNumber == null ){
      echo json_encode(["message"=>"no user"]);
      exit();
    }
    $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
    $stmt->execute();
    if($stmt != null){
      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        if(!isset( $row['userId'])){
          echo json_encode(["message"=>"no user"]);
          exit();
        }
      }
    }else{
      echo json_encode(["message"=>"database error"]);
      exit();
    }
    
    if($_FILES['driverLicenceFont']['name'] != null){
      $File_type = strrchr($_FILES['driverLicenceFont']['name'], '.'); 
      $driverLicenceFont = '../include/pic/userImages/'.$date.rand(0,9).$File_type;
      $driverLicenceFont = str_replace("../", "", $driverLicenceFont);
      $driverLicenceFont = 'https://'.$_SERVER['SERVER_NAME']."/app/".$driverLicenceFont;
      $picsql .= ",`driverLicenceFont`='".$driverLicenceFont."'";
    }
    if($_FILES['driverLicenceBack']['name'] != null){
      $File_type = strrchr($_FILES['driverLicenceBack']['name'], '.'); 
      $driverLicenceBack = '../include/pic/userImages/'.$date.rand(0,9).$File_type;
      $driverLicenceBack = str_replace("../", "", $driverLicenceBack);
      $driverLicenceBack = 'https://'.$_SERVER['SERVER_NAME']."/app/".$driverLicenceBack;
      $picsql .= ",`driverLicenceBack`='".$driverLicenceBack."'";
    }
    if($_FILES['driverOwnership']['name'] != null){
      $File_type = strrchr($_FILES['driverOwnership']['name'], '.'); 
      $driverOwnership = '../include/pic/userImages/'.$date.rand(0,9).$File_type;
      $driverOwnership = str_replace("../", "", $driverOwnership);
      $driverOwnership = 'https://'.$_SERVER['SERVER_NAME']."/app/".$driverOwnership;
      $picsql .= ",`driverOwnership`='".$driverOwnership."'";
    }
    if($_FILES['driverInsurance']['name'] != null){
      $File_type = strrchr($_FILES['driverInsurance']['name'], '.'); 
      $driverInsurance = '../include/pic/userImages/'.$date.rand(0,9).$File_type;
      $driverInsurance = str_replace("../", "", $driverInsurance);
      $driverInsurance = 'https://'.$_SERVER['SERVER_NAME']."/app/".$driverInsurance;
      $picsql .= ",`driverInsurance`='".$driverInsurance."'";
    }

    //修改
    if(isset($driverNumber) && $driverNumber !== ""){
      // if($_FILES['driverLicenceFont']['name'] != null ||$_FILES['driverLicenceBack']['name'] != null ||$_FILES['driverOwnership']['name'] != null||$_FILES['driverInsurance']['name'] != null){
      //   $stmt = $pdo->prepare("SELECT * From `userTable` WHERE `userId` = '$userNumber'");
      //   $stmt->execute();
      //   if($stmt != null){
      //     while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      //       if($_FILES['driverLicenceFont']['name'] != null){
      //         $driverLicenceFont = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverLicenceFont);
      //         unlink($row["driverLicenceFont"]);
      //       }
      //       if($_FILES['driverLicenceBack']['name'] != null){
      //         $driverLicenceBack = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverLicenceBack);
      //         unlink($row["driverLicenceBack"]);
      //       }
      //       if($_FILES['driverOwnership']['name'] != null){
      //         $driverOwnership = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverOwnership);
      //         unlink($row["driverOwnership"]);
      //       }
      //       if($_FILES['driverInsurance']['name'] != null){
      //         $driverInsurance = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverInsurance);
      //         unlink($row["driverInsurance"]);
      //       }
      //     }
      //   }else{
      //     echo json_encode(["message"=>"database error"]);
      //     exit();
      //   }
      // }

      // $stmt = $pdo->prepare("UPDATE `driverTable` SET `userNumber` = '$userNumber' ,`carModle` = '$carModle' ,`driverAddress` = '$driverAddress' , `drvierPhone` = '$drvierPhone' ,`driverSin` = '$driverSin' ".$picsql." WHERE `driverId` = '$driverNumber'");
      // $stmt->execute();
      $stmt = $pdo->prepare("UPDATE `driverTable` SET `driverName` = '$driverName' ,`driverAddress` = '$driverAddress' WHERE `driverId` = '$driverNumber'");
      $stmt->execute();
      if($stmt != null){
        // if($_FILES['driverLicenceFont']['name'] != null){
        //   $driverLicenceFont = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverLicenceFont);
        //   move_uploaded_file($_FILES['driverLicenceFont']['tmp_name'], $driverLicenceFont);
        // }
        // if($_FILES['driverLicenceBack']['name'] != null){
        //   $driverLicenceBack = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverLicenceBack);
        //   move_uploaded_file($_FILES['driverLicenceBack']['tmp_name'], $driverLicenceBack);
        // }
        // if($_FILES['driverOwnership']['name'] != null){
        //   $driverOwnership = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverOwnership);
        //   move_uploaded_file($_FILES['driverOwnership']['tmp_name'], $driverOwnership);
        // }
        // if($_FILES['driverInsurance']['name'] != null){
        //   $driverInsurance = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/", "", $driverInsurance);
        //   move_uploaded_file($_FILES['driverInsurance']['tmp_name'], $driverInsurance);
        // }
      $stmt = $pdo->prepare("SELECT * From `driverTable` WHERE `driverId` = '$driverNumber'");
	    $stmt->execute();
	    if($stmt != null){
	      while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
	      	$data = $row;
	      }
	    }else{
	      echo json_encode(["message"=>"database error"]);
	      exit();
	    }
        echo json_encode(["message"=>"success","data"=>$data]);
      }
      exit();
    }

    //添加
    $stmt = $pdo->prepare("INSERT INTO `driverTable`(`userNumber`,`driverName`,`carModle`,`driverAddress`,`drvierPhone`,`driverSin`,`driverLicenceFont`,`driverLicenceBack`,`driverOwnership`,`driverInsurance`)
                          VALUES ('$userNumber','$driverName','$carModle','$driverAddress','$drvierPhone','$driverSin','$driverLicenceFont','$driverLicenceBack','$driverOwnership','$driverInsurance')");
    $stmt->execute();
    if($stmt != null){
      $driverNumber = $pdo->lastInsertId();
      $stmt = $pdo->prepare("SELECT * From `driverTable` WHERE `driverId` = '$driverNumber'");
      $stmt->execute();
      if($stmt != null){
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
          $data = $row;
        }
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }
      $stmt = $pdo->prepare("UPDATE `userTable` SET `userState` = '0' WHERE `userId` = '$userNumber'");
      $stmt->execute();
      if($_FILES['driverLicenceFont']['name'] != null){
        $driverLicenceFont = "../".str_replace('https://'.$_SERVER['SERVER_NAME']."/app/", "", $driverLicenceFont);
        move_uploaded_file($_FILES['driverLicenceFont']['tmp_name'], $driverLicenceFont);
      }
      if($_FILES['driverLicenceBack']['name'] != null){
        $driverLicenceBack = "../".str_replace('https://'.$_SERVER['SERVER_NAME']."/app/", "", $driverLicenceBack);
        move_uploaded_file($_FILES['driverLicenceBack']['tmp_name'], $driverLicenceBack);
      }
      if($_FILES['driverOwnership']['name'] != null){
        $driverOwnership = "../".str_replace('https://'.$_SERVER['SERVER_NAME']."/app/", "", $driverOwnership);
        move_uploaded_file($_FILES['driverOwnership']['tmp_name'], $driverOwnership);
      }
      if($_FILES['driverInsurance']['name'] != null){
        $driverInsurance = "../".str_replace('https://'.$_SERVER['SERVER_NAME']."/app/", "", $driverInsurance);
        move_uploaded_file($_FILES['driverInsurance']['tmp_name'], $driverInsurance);
      }
	    echo json_encode(["message"=>"success","data"=>$data]);
    }

  }
