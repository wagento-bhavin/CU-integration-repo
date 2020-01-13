<?php

//DB Credentials
$servername = "localhost";
$username   = "cemmlxmy_admin";
$password   = 'tKR+uEee?7RS';
$dbname     = "cemmlxmy_users";

$webhook_content = NULL;

$webhook = fopen('php://input', 'rb');
while (!feof($webhook)) {
    $webhook_content .= fread($webhook, 4096);
}
fclose($webhook);

$customer = json_decode($webhook_content, true);
// Shopify Data

$customer_id    = $customer['id'];
$api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkNsaWVudCIsInN1YiI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9.eyJqdGkiOiJkNTM4NjI4NS05NDliLTQ5NTEtYjg2Ni1mNmUzNGEwY2ZhOTIiLCJleHAiOjQ3MTYwNDA5OTksImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.2Q1_PybkK684egs6stcVGmxkdetDAhSpbWFXFBEE0KY";

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
	$url = "https://api-dev.cheddarup.com/api/user";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'User-Id: ' . $uuid,
	    $authorization
	));
	$result = curl_exec($ch);
	$result = json_decode($result);
	curl_close($ch);
	$query = "DELETE FROM cheddarup_users WHERE uuid= '{$uuid}'";
	if ($result = $conn->query($query)) {
	echo "removed from DB";
    $result->close();
}
}
?>