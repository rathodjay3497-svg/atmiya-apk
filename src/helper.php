<?php

use Slim\Http\UploadedFile;

function verifyRequiredParams($request_params, $required_fields)
{
    $responseArr = array();
    $error = false;
    $error_fields = "";

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $responseArr["error"] = true;
        $responseArr["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
    }

    return $responseArr;
}

function moveUploadedFile($directory, $filename, UploadedFile $uploadedFile)
{
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return true;
    }

    return false;
}

function isEmail($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL))
        return true;
    else
        return false;
}

function isPhoneNumber($phone)
{
    if (preg_match("/^[0-9]{6,20}$/", $phone))
        return true;
    else
        return false;
}

function isName($name)
{
    if (preg_match("/^[0-9a-zA-Z\s\.]{3,25}$/", $name))
        return true;
    else
        return false;
}

function isAddress($address)
{
    if (preg_match("/^[A-Za-z0-9\/\#\-\_\()\s\.\,\:\&]{3,120}$/", $address))
        return true;
    else
        return false;
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}

function is_jwt_valid() {
	// split the jwt
	$jwt = getBearerToken();
    $secret = 'rKc53w39uH59326Fe4Ky';
	$tokenParts = explode('.', $jwt);
	$header = base64_decode($tokenParts[0]);
	$payload = base64_decode($tokenParts[1]);
	$signature_provided = $tokenParts[2];

	// check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
	$expiration = json_decode($payload)->exp;
	$is_token_expired = ($expiration - time()) < 0;

	// build a signature based on the header and payload using the secret
	$base64_url_header = base64url_encode($header);
	$base64_url_payload = base64url_encode($payload);
	$signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
	$base64_url_signature = base64url_encode($signature);

	// verify it matches the signature provided in the jwt
	$is_signature_valid = ($base64_url_signature === $signature_provided);
	
	if ($is_token_expired || !$is_signature_valid) {
		return false;
	} else {
	  
		return true;
	}
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
