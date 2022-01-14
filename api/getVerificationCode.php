<?php
include("../include/sql.php");

require __DIR__ . '/../twilio-php/src/Twilio/autoload.php';
use Twilio\Rest\Client;
// Include the bundled autoload from the Twilio PHP Helper Library
http_response_code(200);
header('content-type:application/json;charset=utf8');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

$data = file_get_contents('php://input');
$data = json_decode($data,true);

$phone = $data['userPhone'];
$userRole = $data['userRole'];
$language = $data['language'];

$phone=str_replace("-", " ", $phone);

//language setting
$msgList = ["Zh"=>[0=>"感谢注册eFreshGo平台 您的验证码为",1=>"欢迎登陆eFreshGo平台 您的验证码为"],"En"=>[0=>"Welcome to signup at eFreshGo, your verifiation Code is",1=>"Welcome to signin at eFreshGo, your verifiation Code is "]];

$checknumber = mt_rand(100000,999999);

$stmt = $pdo->prepare("SELECT count(*) as `num` From `userTable` WHERE `userPhone` = '$phone' AND `userRole` = '$userRole';");
$stmt->execute();
$order = 0;
if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        if($row['num'] == 0 && $userRole != 1 ){
            $msg = $msgList[$language][0];
            $state = "0";
        }else{
            $msg = $msgList[$language][1];
            $state = "1";
        }
    }
}else{
    echo json_encode(["message"=>"database error"]);
    exit();
}

$stmt = $pdo->prepare("REPLACE INTO `messageTable`(`messagePhone`,`messageCode`,`messageType`,`userRole`) VALUES ('$phone','$checknumber','$state','$userRole');");
$stmt->execute();
if($stmt == null){
    echo json_encode(["message"=>"database error"]);
    exit();
}


// Your Account SID and Auth Token from twilio.com/console
// $account_sid = 'AC0f4a3d1f13509aa8fbaa55b0b96fe001';
// $auth_token = '16889a48f8696471fddcdb9dfd7f8136';
// $twilio_number = "+12038840236";
// $account_sid = 'AC36744f54c466fa4a7f6271131a967d8c';
// $auth_token = '45d420a910c96bbb2ac77b47d96be028';
// $twilio_number = "+13343784234";
// $account_sid = 'ACe2fd4d8f8006b794b859df0b85fdbd5b';
// $auth_token = '857d15561e2852176582bab6b6cb536c';
// $twilio_number = "+12058989634";
//efreshGo
$account_sid = 'AC262312e514c2a1292f312de37e41b97a';
$auth_token = '09e4e3af6ebd1563dd3aa46b1a8a71a3';
$twilio_number = "+18078084231";

$client = new Client($account_sid, $auth_token);


try {
$client->messages->create(
    // Where to send a text message (your cell phone?)
    $phone,
    array(
        'from' => $twilio_number,
        'body' => $msg.$checknumber
    )
);
} catch (Exception $e) {
    echo json_encode(["message"=>"twilio error"]);
	exit();
}
$data = ["verificationCode"=>$checknumber];
$data["messageType"]=$state;
echo json_encode(["message"=>"success","data"=>$data]);
?>

