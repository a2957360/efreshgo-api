<?php
 require_once __DIR__.'/vendor/autoload.php';

// $key = "ExponentPushToken[S3SNIUMPn08WILHdpQOCRx]";
// $userId = 'userId from your database';
// $notification = ['title' => $title,'body' => $msg];
//   try{
//       $expo = \ExponentPhpSDK\Expo::normalSetup();
//       $expo->notify($userId,$notification);//$userId from database
//       $status = 'success';
// }catch(Exception $e){
//         $expo->subscribe($userId, $key); //$userId from database
//         $expo->notify($userId,$notification);
//         $status = 'new subscribtion';
// }
//   echo $status;
  // $channelName = 'news';
  // $recipient= 'ExponentPushToken[U-OVwqPQk3PSWSP1SRWtsc]';
  // $interestDetails = ['https://exp.host/--/api/v2/push/send',$recipient];
  // // You can quickly bootup an expo instance
  // try {
  // 	  $expo = \ExponentPhpSDK\Expo::normalSetup();
	 //   // $expo->subscribe($interestDetails[0], $interestDetails[1]);
	 //  // Subscribe the recipient to the server
	 //  $expo->subscribe($channelName, $recipient);
	  
	 //  // Build the notification data
	 //  $notification = ['body' => 'Hello World!'];
	  
	 //  // Notify an interest with a notification
	 //  $expo->notify([$channelName], $notification);
  // } catch (Exception $e) {
  // 	echo $e;
  // }

?>
 <?php
 function sendpush($pushtarget,$pushcontent){
    $payload = array(
        'to' => $pushtarget,
        'sound' => 'default',
        'title' => 'New Message',
    	'body'=> $pushcontent["pushContent"],
        'data'=> $pushcontent
    );
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://exp.host/--/api/v2/push/send",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($payload),
	  CURLOPT_HTTPHEADER => array(
	    "Accept: application/json;charset=utf8",
	    "Accept-Encoding: gzip, deflate",
	    "Content-Type: application/json",
	    "cache-control: no-cache",
	    "host: exp.host"
	  ),
	));
	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  // echo $response;
	}
 }
?>