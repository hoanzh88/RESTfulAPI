<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// $uri = explode( '/', $uri );

$requestmethod = $_SERVER["REQUEST_METHOD"];

if($requestmethod == 'GET') {
	$response['response_desc'] = $response_desc;	
	$json_response = json_encode($response);
	echo $json_response;
}else if($requestmethod == 'POST') {

}else if($requestmethod == 'PUT') {

}else if($requestmethod == 'DELETE') {

}

