<?php	
function getDistance($userLocation,$storeLocation){
	if($storeLocation['lat'] == "" ||$storeLocation['lng'] == "" ||$userLocation['lat'] == "" ||$userLocation['lng'] == ""){
		return 0;
	}
	$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$storeLocation['lat'].",".$storeLocation['lng']."&destinations=".$userLocation['lat'].",".$userLocation['lng']."&mode=driving&language=en&key=AIzaSyAAFCZbZcKy7Por0AAmy-2MqF1mnV_0aNE";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$geoloc = json_decode(curl_exec($ch), true);
	$distance = $geoloc['rows'][0]['elements'][0]['distance']['text'];
	$distance = isset($distance)?$distance:0;

	return $distance;
}
?>