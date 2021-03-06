<?php

//DB Credentials
$servername = "localhost";
$username   = "cemmlxmy_admin";
$password   = 'tKR+uEee?7RS';
$dbname     = "cemmlxmy_CU_PRD";

$webhook_content = NULL;

$webhook = fopen('php://input', 'rb');
while (!feof($webhook)) {
    $webhook_content .= fread($webhook, 4096);
}
fclose($webhook);
$customer = json_decode($webhook_content, true);
//Shopify Data

$customer_id    = $customer['id'];
$email 			= $customer['email'];
$first_name		= $customer['first_name'];
$last_name		= $customer['last_name'];
$phone_num		= $customer['phone'];
$addresses		= $customer['addresses'][0];
$currency       = $customer['currency'];
$tags           = $customer['tags'];
// Cheddarup creation
$tags = explode(', ', $tags);
if (!in_array("wholesale", $tags)) {
    die("Not wholesale user");
} else {   
    $api_key = "eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJjOTg1ZWI3Mi0xMjRkLTQxMWYtYThlYi03NDRlODIxZGU3MGUiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJjZDBhMTkxMy04ZGUwLTRjMmQtYTMzZC03ZDc0YTJhYzM5NjciLCJleHAiOjQ3Mjg5NTAxMzgsImRhdGEiOnsiY2xpZW50X2lkIjoiYzk4NWViNzItMTI0ZC00MTFmLWE4ZWItNzQ0ZTgyMWRlNzBlIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImM5ODVlYjcyLTEyNGQtNDExZi1hOGViLTc0NGU4MjFkZTcwZSJ9fX19.JCfFmFrGychiv1aWJ6WmLzVR0CB0bIXTFL6o7Dge4vs";
    
    $authorization = "Authorization: Bearer " . $api_key;
    $uuid = "";
	$conn = new mysqli($servername, $username, $password, $dbname);
	    
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	$query = "SELECT uuid FROM cheddarup_users where shopify_id like '%{$customer_id}%'";
	if ($result = $conn->query($query)) {
	    while ($row = $result->fetch_array(MYSQLI_ASSOC)){
		  $uuid = $row["uuid"];
		}
	    $result->close();
	}
	if($uuid){
	    $country_code  = "1";
	    if (strlen($phone_num) > 10) {
	        $country_code = substr($phone_num, 1, 1);
	        $phone_num    = substr($phone_num, 2, strlen($phone_num) - 2);
	    }
	    if ($currency == "USD")
	        $country = "United States";
	    else
	        $country = "Canada";
	    $city = ($addresses['city'])?$addresses['city']:"";
	    $state = ($addresses['province'])?$addresses['province']:"";
	    $addr1 = ($addresses['address1'])?$addresses['address1']:"";
	    $zip = ($addresses['zip'])?$addresses['zip']:"";
	    $addr    = array(
	        'city' => $city,
	        'state' => $state,
	        'line1' => $addr1,
	        'postal_code' => $zip
	    );
	    $data    = array(
	        'email' => $email,
	        'first_name' => $first_name,
	        'last_name' => $last_name,
	        'country' => $country,
	        'business_address' => $addr,
	        'personal_address' => $addr,
	        'profile' => array(
	            'phone' => array(
	                'phone_number' => $phone_num,
	                'country_code' => $country_code
	            )
	        )
	    );
	    $payload = json_encode($data);
	    $ch      = curl_init();
	    
	    curl_setopt($ch, CURLOPT_URL, 'https://api.cheddarup.com/api/user');
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
	    
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'User-Id: ' . $uuid,
		    $authorization
		));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	    $result = curl_exec($ch);
	    $err    = curl_error($ch);
	    curl_close($ch);
	    if ($err)
	        die("cURL Error #: " . $err);
	    file_put_contents("user.txt", $result);
	    $conn->close();
	}
}

?>