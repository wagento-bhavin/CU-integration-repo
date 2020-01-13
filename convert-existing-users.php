<?php

//DB Credentials
$servername = "localhost";
$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";

$api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkNsaWVudCIsInN1YiI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9.eyJqdGkiOiJkNTM4NjI4NS05NDliLTQ5NTEtYjg2Ni1mNmUzNGEwY2ZhOTIiLCJleHAiOjQ3MTYwNDA5OTksImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.2Q1_PybkK684egs6stcVGmxkdetDAhSpbWFXFBEE0KY";
$partner_uuid 	= '6ea10786-ea8f-46cf-bbdd-02f9ff9a87e5';
$authorization = 'Authorization: Bearer ' . $api_key;
$header 		= array(
	'Content-Type: application/json' ,
	'User-Id: '.$partner_uuid, 
	'Authorization: Bearer ' . $api_key
);

//Fetch Shopify Customer Data
$url = "https://098f76e3e822c1d8871ff6c0ddc6b915:6b78d8f5583b68fe1ffbebbb2466358c@pixielane-internal-dev.myshopify.com/admin/api/2019-07/customers.json";
$contents = file_get_contents($url);


$customers = json_decode($contents,true);
$customers = $customers["customers"];

// echo "<p><pre>";
// print_r($customers);
// echo "</pre></p>";

if(sizeof($customers) < 1)
	die("No Customers on Shopify");

foreach ($customers as $customer) {
	$tags 		= explode(', ', $customer['tags']);
	if(!in_array("wholesale", $tags))
		continue;
	$email 		= $customer['email'];
	$customer_id= $customer['id'];
	$first_name	= $customer['first_name'];
	$last_name	= $customer['last_name'];
	$phone_num	= $customer['phone'];
	$currency 	= $customer['currency'];

	// Cheddarup creation
	if(strlen($phone_num) > 10){	
		$phone_num 		= substr($phone_num, 2, strlen($phone_num)-2);		
	}
	if($currency == "USD")
		$country = "United States";
	else
		$country = "Canada";
	$data = array(
	    'email' 	=> $email,
	    'first_name'=> $first_name,
	    'last_name' => $last_name,
	    'country' 	=> $country,
	    'profile' 	=> array(
	    	'phone' => array(
	    		'phone_number' => $phone_num,
				'country_code' => "1"
			)
	    )
	);
	echo "<p><pre>";
	print_r($data);
	echo "</pre></p>";
	$payload = json_encode($data);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api-dev.cheddarup.com/api/clients/users');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
	$result = json_decode(curl_exec($ch));
	$err = curl_error($ch);
	curl_close($ch);
	if ($err) 
	  die("cURL Error #: " . $err);
	if(!property_exists($result, "created") || !property_exists($result->created, "user"))
	  die("Empty response");
	echo "<p><pre>";
	print_r($result);
	echo "</pre></p>";

	//Save to Database
	$conn = new mysqli($servername, $username, $password, $dbname);

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 

	$result = $result->created->user;
	$token  = $result->token;
	$id 	= $result->id;
	$uuid 	= $result->uuid;
	$created= $result->created_at;
	$updated= $result->updated_at;
	// $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/data.txt","wb");
	// fwrite($fp,$token);
	// fclose($fp);
	$tags 	= implode(';', $tags);

	$sql = "INSERT INTO cheddarup_users (shopify_id, email, cheddarup_id, uuid, created_at, updated_at, token)
	VALUES ('{$customer_id}','{$email}','{$id}','{$uuid}','{$created}','{$updated}','{$tags}')";
	if ($conn->query($sql) === TRUE) {
	    echo "New record created successfully";
	} else {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}

	//Create Collection
	$collection_name = $first_name . "’s PixieLane Online Store";
	$data = array('name' => $collection_name);
	$payload = json_encode($data);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api-dev.cheddarup.com/api/users/tabs');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'User-Id: '.$uuid ,$authorization ));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
	$result = json_decode(curl_exec($ch));
	$err = curl_error($ch);
	curl_close($ch);
	echo "<p><pre>";
	print_r($result);
	echo "</pre></p>";
	$conn->close();

}

?>