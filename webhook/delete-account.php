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
// Shopify Data

$customer_id    = $customer['id'];
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
	$url = "https://api.cheddarup.com/api/user";
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