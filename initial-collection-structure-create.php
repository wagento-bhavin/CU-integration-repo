<?php
require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

//$api_key 		= 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkNsaWVudCIsInN1YiI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9.eyJqdGkiOiJkNTM4NjI4NS05NDliLTQ5NTEtYjg2Ni1mNmUzNGEwY2ZhOTIiLCJleHAiOjQ3MTYwNDA5OTksImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.2Q1_PybkK684egs6stcVGmxkdetDAhSpbWFXFBEE0KY';
$api_key 		= 'eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJlMmRhNjgxOC1mMTFlLTQzYTUtYjhlMS00MmY0YzQ1OWViNTMiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiIwMGVlODg0My1mOWI2LTRmZjMtYmFjMC1jYzVkZWJjZTA3NTciLCJleHAiOjE4OTQ5MTMxNTQsImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.QA77p82AicXUxpIt-TOvEtopx4ikExctv8RvyDe8iPc';

$partner_uuid 	= '6ea10786-ea8f-46cf-bbdd-02f9ff9a87e5';

$header 		= array(
	'Content-Type: application/json' ,
	'User-Id: '.$partner_uuid, 
	'Authorization: Bearer ' . $api_key
);
/****************************************
	Move Collections and Categories
****************************************/
$collections = ['Girls', 'Boys', 'Babies', 'Women', 'Men'];

$categories  = array();
$categories['Girls']  = ['Tops', 'Dresses', 'Bottoms', 'Jumpsuits & Rompers', 'Swimwear', 'Loungewear', 'Sets'];
$categories['Boys']   = ['Tops', 'Bottoms', 'Sets'];
$categories['Babies'] = ['Tops', 'Dresses', 'Bottoms', 'Jumpsuits & Rompers', 'Sets'];
$categories['Women']  = ['Tops', 'Bottoms', 'Jumpsuits & Rompers', 'Sweatshirts & Cardigans', 'Sets'];
$categories['Men']    = ['Tops'];
// $uuid_array = array();
$col_ids = array();
//Get Current collections
$url = 'https://api-dev.cheddarup.com/api/users/tabs';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
$err    = curl_error($ch);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);
if(!$err) {
	$existing_cols = array();
	foreach ($result as $collection) {
		array_push($existing_cols, $collection['name']);
		if(in_array($collection['name'], $collections))
			$col_ids[$collection['name']] = $collection['id'];
	}
	// print_r($col_ids);
	//Create New Collections
	foreach ($collections as $collection_name) {
		if(!in_array($collection_name, $existing_cols)){
			$data    = array(
			    'name'	=> $collection_name
			);
			$payload = json_encode($data);

			$url = 'https://api-dev.cheddarup.com/api/users/tabs';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			$err    = curl_error($ch);
			$result = json_decode(curl_exec($ch), true);
			if($result['id'])
				$col_ids[$collection_name] = $result['id'];
			curl_close($ch);
			// print_r($result);
		}
	}
	//Create Categories
	foreach ($col_ids as $col_name => $col_id) {
		
		//Get current categories
		$url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_id.'/categories';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$err    = curl_error($ch);
		$result = json_decode(curl_exec($ch), true);
		curl_close($ch);

		//Create or Update categories
		$type = "POST";
		if(sizeof($result) > 0){
			$url  = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_id.'/categories/'.$result[0]['id'];
			$type = "PATCH";
		}
		$subcat_data = array();
		foreach ($categories[$col_name] as $subcategory) {
			$uuid = "";
			try {
				$uuid = Uuid::uuid4();
	    		$uuid = $uuid->toString();
	    	}catch (UnsatisfiedDependencyException $e) {
	    		echo 'Caught exception: ' . $e->getMessage() . "\n";
	    	}
	    	if($uuid == "")
	    		die("No UUID");

			$temp = array(
				'name' => $subcategory,
				'uuid' => $uuid
			);
			// $uuid_array[$col_name][$subcategory] = $uuid;
			array_push($subcat_data, $temp);
		}
	    $data     = array(
	        'name'		=> $col_name,
	        'anchor'	=> true,
	        'options' 	=> array(
	            'subcategories' => $subcat_data
	        )
	    );
		$payload = json_encode($data);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$err    = curl_error($ch);
		$result = json_decode(curl_exec($ch), true);
		curl_close($ch);
		// print_r($result);	
		// print_r($data);
	}
	// print_r($uuid_array);

}

?>