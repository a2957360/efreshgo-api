<?php
  include("../include/sql.php");
  include("arrayImages.php");
  http_response_code(200);
  header('content-type:application/json;charset=utf8');
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

  $data = file_get_contents('php://input');
  $data = json_decode($data,true);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //上传多个/单个图片
    if(isset($_POST['isUploadImage'])){
      $date=date("ymdhis");
      $returnImages = array();
      $uploadImages = reArrayFiles($_FILES['uploadImages']);
      foreach ($uploadImages as $imagekey => $imagevalue) {
        if($imagevalue['name'] != null){
          $File_type = strrchr($imagevalue['name'], '.'); 
          $pageImage = '../include/pic/itemImages/'.$date.$imagekey.rand(0,9).$File_type;
          //上传图片
          move_uploaded_file($imagevalue['tmp_name'], $pageImage);
          // $picsql .= ",`uploadImages`='".$pageImage."'";
          $pageImage = str_replace("../", "", $pageImage);
          $returnImages[] = 'https://'.$_SERVER['SERVER_NAME']."/app/".$pageImage;
        }
      }
      echo json_encode(["message"=>"success","data"=>$returnImages]);
      exit();
    }

    if(isset($data['deleteImages']) && $data['deleteImages'] !== ""){
      $deleteImage = $data['deleteImages'];
      foreach ($deleteImage as $key => $value) {
        $tmplink = "../".str_replace('http://'.$_SERVER['SERVER_NAME']."/app/","",$value);
        // echo  $tmplink;
        unlink($tmplink);
      }
      echo json_encode(["message"=>"success"]);
      exit();
    }

  }
