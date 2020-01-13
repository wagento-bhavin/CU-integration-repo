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
$email          = $customer['email'];
$first_name     = $customer['first_name'];
$last_name      = $customer['last_name'];
$orders_count   = $customer['orders_count'];
$state          = $customer['state'];
$total_spent    = $customer['total_spent'];
$note           = $customer['note'];
$phone_num      = $customer['phone'];
$tags           = $customer['tags'];
$addresses      = $customer['addresses'];
$verified_email = $customer['verified_email'];
$currency       = $customer['currency'];
// Cheddarup creation
$tags           = explode(', ', $tags);
if (!in_array("wholesale", $tags)) {
    die("Not wholesale user");
} else {
    
    $api_key = "eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJjOTg1ZWI3Mi0xMjRkLTQxMWYtYThlYi03NDRlODIxZGU3MGUiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJjZDBhMTkxMy04ZGUwLTRjMmQtYTMzZC03ZDc0YTJhYzM5NjciLCJleHAiOjQ3Mjg5NTAxMzgsImRhdGEiOnsiY2xpZW50X2lkIjoiYzk4NWViNzItMTI0ZC00MTFmLWE4ZWItNzQ0ZTgyMWRlNzBlIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImM5ODVlYjcyLTEyNGQtNDExZi1hOGViLTc0NGU4MjFkZTcwZSJ9fX19.JCfFmFrGychiv1aWJ6WmLzVR0CB0bIXTFL6o7Dge4vs";
    
    $authorization = "Authorization: Bearer " . $api_key;
    $country_code  = "1";
    if (strlen($phone_num) > 10) {
        $country_code = substr($phone_num, 1, 1);
        $phone_num    = substr($phone_num, 2, strlen($phone_num) - 2);
    }
    if ($currency == "USD")
        $country = "United States";
    else
        $country = "Canada";
    $data    = array(
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'country' => $country,
        'profile' => array(
            'phone' => array(
                'phone_number' => $phone_num,
                'country_code' => $country_code
            )
        )
    );
    $payload = json_encode($data);
    $ch      = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.cheddarup.com/api/clients/users');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        $authorization
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = json_decode(curl_exec($ch));
    $err    = curl_error($ch);
    curl_close($ch);
    if ($err)
        die("cURL Error #: " . $err);
    if (!property_exists($result, "created") || !property_exists($result->created, "user"))
        die("Empty response");
    
    //Save to Database
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $result  = $result->created->user;
    $token   = $result->token;
    $id      = $result->id;
    $uuid    = $result->uuid;
    $created = $result->created_at;
    $updated = $result->updated_at;
    $fp      = fopen($_SERVER['DOCUMENT_ROOT'] . "/data.txt", "wb");
    fwrite($fp, $token);
    fclose($fp);
    $sql = "INSERT INTO cheddarup_users (shopify_id, email, cheddarup_id, uuid, created_at, updated_at, token)
VALUES ('{$customer_id}','{$email}','{$id}','{$uuid}','{$created}','{$updated}','{$token}')";
    
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $collection_name = $first_name . "’s PixieLane Online Store";
    $data            = array(
        'name' => $collection_name
    );
    $payload         = json_encode($data);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.cheddarup.com/api/users/tabs');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Id: ' . $uuid,
        $authorization
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = json_decode(curl_exec($ch));
    $err    = curl_error($ch);
    curl_close($ch);
    echo "<p><pre>";
    print_r($result);
    echo "</pre></p>";
    $conn->close();
}
?>