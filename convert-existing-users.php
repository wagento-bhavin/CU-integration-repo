<?php

//DB Credentials
$servername = "localhost";
/*$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";*/
$username = "ycfssmjzrs";
$password = 'BPc98qqeVA';
$dbname = "ycfssmjzrs";

$api_key = "eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJlMmRhNjgxOC1mMTFlLTQzYTUtYjhlMS00MmY0YzQ1OWViNTMiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiIwMGVlODg0My1mOWI2LTRmZjMtYmFjMC1jYzVkZWJjZTA3NTciLCJleHAiOjE4OTQ5MTMxNTQsImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.QA77p82AicXUxpIt-TOvEtopx4ikExctv8RvyDe8iPc";
$partner_uuid = '6ea10786-ea8f-46cf-bbdd-02f9ff9a87e5';
$authorization = 'Authorization: Bearer ' . $api_key;
$header = array(
    'Content-Type: application/json',
    'User-Id: ' . $partner_uuid,
    'Authorization: Bearer ' . $api_key
);

//Save to Database
$conn = new mysqli($servername, $username, $password, $dbname);

$file = 'customer_migration_error.log';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$count_url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2019-04/customers/count.json";
$total = file_get_contents($count_url);
$total = json_decode($total, true);
$total = $total['count'];
$page = ceil($total / 250);
$count = 0;
$wholesale_count = 0;
for ($i=1; $i <= $page ; $i++) {
    $url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2019-04/customers.json?limit=250&page=".$i;
    $contents = file_get_contents($url);
    $customers = json_decode($contents,true);
    $customers = $customers["customers"];
    $count+= sizeof($customers);
    foreach ($customers as $customer) {
        $tags = explode(', ', $customer['tags']);

        if ($customer['state'] == "disabled" || $customer['state'] == "declined") {
            continue;
        }
        if (!in_array("wholesale", $tags)) {
            continue;
        }
        $email = $customer['email'];
        $customer_id = $customer['id'];
        $first_name = $customer['first_name'];
        $last_name = $customer['last_name'];
        $phone_num = $customer['phone'];
        $currency = $customer['currency'];

        // Cheddarup creation
        if (strlen($phone_num) > 10) {
            $phone_num = substr($phone_num, 2, strlen($phone_num) - 2);
        }
        if ($currency == "USD")
            $country = "United States";
        else
            $country = "Canada";
        $data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'country' => $country,
            'profile' => array(
                'phone' => array(
                    'phone_number' => $phone_num,
                    'country_code' => "1"
                )
            )
        );

        $payload = json_encode($data);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api-dev.cheddarup.com/api/clients/users');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = json_decode(curl_exec($ch));
        $err = curl_error($ch);
        if ($err) {
            $text_to_write = "\n Curl Error for the Email: " . $email . "\n";
            $text_to_write .= curl_exec($ch);
            $text_to_write .= "\n\n";
            file_put_contents($file, $text_to_write, FILE_APPEND);
            continue;
            //die("cURL Error #: " . $err);
        }
        if (!property_exists($result, "created") || !property_exists($result->created, "user")) {
            print_r("Empty response\n");
            $text_to_write = "\n Response property for the : " . $email . "\n";
            $text_to_write .= curl_exec($ch);
            $text_to_write .= "\n\n";
            file_put_contents($file, $text_to_write, FILE_APPEND);
            continue;
        }

        $token = $result->token;
        $result = $result->created->user;
        $id = $result->id;
        $uuid = $result->uuid;
        $created = $result->created_at;
        $updated = $result->updated_at;
        $tags = implode(';', $tags);

        $selectQuery = "SELECT * FROM cheddarup_users WHERE shopify_id= '$customer_id'";
        $fetchResult = $conn->query($selectQuery);
        if ($fetchResult->num_rows == 0) {

            $sql = "INSERT INTO cheddarup_users (shopify_id, email, cheddarup_id, uuid, created_at, updated_at, token)
	VALUES ('{$customer_id}','{$email}','{$id}','{$uuid}','{$created}','{$updated}', '{$token}')";
            if ($conn->query($sql) === TRUE) {
                echo "\nNew record created successfully\n";
                //Create Collection
                $collection_name = $first_name . "’s PixieLane Online Store";
                $data = array('name' => $collection_name);
                $payload = json_encode($data);

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api-dev.cheddarup.com/api/users/tabs');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'User-Id: ' . $uuid, $authorization));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $result = json_decode(curl_exec($ch));
                $err = curl_error($ch);
                curl_close($ch);
                $wholesale_count++;
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
                continue;
            }
        } else {
            continue;
        }
    }
} // End For loop
print_r("Total ". $total . "--- Wholesale Customers ". $wholesale_count);
print_r("\n");
$conn->close();
?>

