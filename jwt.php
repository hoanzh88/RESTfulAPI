<?php

// Create token header as a JSON string
$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

// Create token payload as a JSON string
$payload = json_encode(['user_id' => 890, 'user_name' => 'hoanchuong']);

// Encode Header to Base64Url String
$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
// Encode Payload to Base64Url String
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

// Create Signature Hash
$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);
// Encode Signature to Base64Url String
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

// Create JWT
$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

echo $jwt;

$token = $jwt;

$tokenParts = explode(".", $token);  
$tokenHeader = base64_decode($tokenParts[0]);
$tokenPayload = base64_decode($tokenParts[1]);
$jwtHeader = json_decode($tokenHeader);
$jwtPayload = json_decode($tokenPayload);
echo "<pre>";
print_r($jwtPayload);
echo "</pre>";
?>