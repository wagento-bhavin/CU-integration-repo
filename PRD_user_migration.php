<?php

//DB Credentials
$servername = "localhost";
/*$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";*/
$username = "ycfssmjzrs";
$password = 'BPc98qqeVA';
$dbname = "ycfssmjzrs";

$api_key = "eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJjOTg1ZWI3Mi0xMjRkLTQxMWYtYThlYi03NDRlODIxZGU3MGUiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJjZDBhMTkxMy04ZGUwLTRjMmQtYTMzZC03ZDc0YTJhYzM5NjciLCJleHAiOjQ3Mjg5NTAxMzgsImRhdGEiOnsiY2xpZW50X2lkIjoiYzk4NWViNzItMTI0ZC00MTFmLWE4ZWItNzQ0ZTgyMWRlNzBlIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImM5ODVlYjcyLTEyNGQtNDExZi1hOGViLTc0NGU4MjFkZTcwZSJ9fX19.JCfFmFrGychiv1aWJ6WmLzVR0CB0bIXTFL6o7Dge4vs";
$partner_uuid 	= 'f9dceb72-3460-40d3-b6e8-beca443642cf';
$authorization = 'Authorization: Bearer ' . $api_key;
$header 		= array(
	'Content-Type: application/json' ,
	'User-Id: '.$partner_uuid, 
	'Authorization: Bearer ' . $api_key
);

//Save to Database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
//Fetch Shopify Customer Data
$count_url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2020-01/customers/count.json";
$total = file_get_contents($count_url);
$total = json_decode($total,true);
$total = $total['count'];
$page = ceil($total / 250);
$count = 0;
$wholesale_count = 0;
for ($i=1; $i <= $page ; $i++) { 
	$url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2020-01/customers.json?limit=250&page=".$i;
	$contents = file_get_contents($url);
	$customers = json_decode($contents,true);
	$customers = $customers["customers"];
	$count+= sizeof($customers);
	foreach ($customers as $customer) {
		$tags 		= explode(', ', $customer['tags']);
		if($customer['state'] == "disabled" || $customer['state'] == "declined")
			continue;
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

		$payload = json_encode($data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.cheddarup.com/api/clients/users');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
		$result = json_decode(curl_exec($ch));
		$err = curl_error($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		print_r($httpcode);
		curl_close($ch);
		if ($err) 
		  die("cURL Error #: " . $err);
		if(!property_exists($result, "created") || !property_exists($result->created, "user")){
		  print_r("Empty response\n");
		  continue;
		}

		$result = $result->created->user;
		$token  = $result->token;
		$id 	= $result->id;
		$uuid 	= $result->uuid;
		$created= $result->created_at;
		$updated= $result->updated_at;

		$tags 	= implode(';', $tags);

		$sql = "INSERT INTO cheddarup_users (shopify_id, email, cheddarup_id, uuid, created_at, updated_at, tags, token)
		VALUES ('{$customer_id}','{$email}','{$id}','{$uuid}','{$created}','{$updated}','{$tags}', '{$token}')";
		if ($conn->query($sql) === TRUE) {
		    echo "New record created successfully";
		} else {
		    echo "Error: " . $sql . "<br>" . $conn->error;
		    continue;
		}

		//Create Collection
		$collection_name = $first_name . "’s PixieLane Online Store";
		$data = array('name' => $collection_name);
		$payload = json_encode($data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.cheddarup.com/api/users/tabs');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'User-Id: '.$uuid ,$authorization ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
		$result = json_decode(curl_exec($ch));
		$err = curl_error($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		print_r($httpcode);
        print_r("\n");
		curl_close($ch);
		$wholesale_count++;

	}
}
print_r($total."---".$count."---".$wholesale_count);
print_r("\n");
$conn->close();

?>