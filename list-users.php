<?php

$api_key = "eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJjOTg1ZWI3Mi0xMjRkLTQxMWYtYThlYi03NDRlODIxZGU3MGUiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJjZDBhMTkxMy04ZGUwLTRjMmQtYTMzZC03ZDc0YTJhYzM5NjciLCJleHAiOjQ3Mjg5NTAxMzgsImRhdGEiOnsiY2xpZW50X2lkIjoiYzk4NWViNzItMTI0ZC00MTFmLWE4ZWItNzQ0ZTgyMWRlNzBlIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImM5ODVlYjcyLTEyNGQtNDExZi1hOGViLTc0NGU4MjFkZTcwZSJ9fX19.JCfFmFrGychiv1aWJ6WmLzVR0CB0bIXTFL6o7Dge4vs";

$authorization = "Authorization: Bearer " . $api_key;

$url = "https://api.cheddarup.com/api/clients/users?page=1&per_page=50";
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 80);

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));

$result = json_decode(curl_exec($ch), true);
curl_close($ch);
print_r($result);
?>

