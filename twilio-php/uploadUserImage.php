<?php
  include("include/sql.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = file_get_contents('php://input');
    $data = json_decode($data,true);

    $uploadUserId=$_POST['uploadUserId'];
    $date= date('YmdHis');
    $picsql = "";

    if($_FILES['uploadImage']['name'] != null){
      $File_type = strrchr($_FILES['uploadImage']['name'], '.'); 
      $picture = 'include/pic/'.$uploadUserId."/".$date.$File_type;
      $picsql = "`userPhoto`='".$picture."'";
    }else{
      $message=["message"=>"fail"];
      echo json_encode($message);
      exit();
    }

    $stmt = $pdo->prepare("UPDATE `userInfo` SET ".$picsql." WHERE `userId` = '$uploadUserId'");
    $stmt->execute();
    if($stmt != null){
      if($_FILES['uploadImage']['name'] != null){
        if (!is_dir('include/pic/'.$uploadUserId)) {
          mkdir('include/pic/'.$uploadUserId);
        }
        move_uploaded_file($_FILES['uploadImage']['tmp_name'], $picture);
      }
      $message=["message"=>"success"];
      echo json_encode($message);
      }else{
        echo json_encode(["message"=>"database error"]);
        exit();
      }    

  }

