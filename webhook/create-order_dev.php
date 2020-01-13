<?php
require '../vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
//DB Credentials
$servername = "localhost";
$username   = "cemmlxmy_admin";
$password   = 'tKR+uEee?7RS';
$dbname     = "cemmlxmy_users";
//API Data
$api_key        = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkNsaWVudCIsInN1YiI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9.eyJqdGkiOiJkNTM4NjI4NS05NDliLTQ5NTEtYjg2Ni1mNmUzNGEwY2ZhOTIiLCJleHAiOjQ3MTYwNDA5OTksImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.2Q1_PybkK684egs6stcVGmxkdetDAhSpbWFXFBEE0KY';
$sizes  = array();
$sizes['Girls']  = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Boys']   = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Babies'] = ['Size_3/6', 'Size_6/12', 'Size_12/18', 'Size_18/24'];
$sizes['Women']  = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];
$sizes['Men']    = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];
$webhook_content = NULL;

$webhook = fopen('php://input', 'rb');
while (!feof($webhook)) {
    $webhook_content .= fread($webhook, 4096);
}
fclose($webhook);
$file = 'order_webhook.txt';
$text_to_write = "";
$order = json_decode($webhook_content, true);
// Shopify Data

$line_items     = $order['line_items'];
$customer_id    = $order['customer']['id'];
$tags           = $order['customer']['tags'];
$text_to_write .= "-*Shopify Order-".json_encode($line_items)."---";
// $customer_id = 2435448406097;
// $tags         = "wholesale";
// $line_items = array(array(
//     "product_id"    => 1576947482687, 
//     "variant_id"    => 13790157242431,
//     "quantity"      => 5,
//     'sku'           => 'KP5017W-3'
//       ));
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $text_to_write = "Connection failed: " . $conn->connect_error;
    file_put_contents($file, $text_to_write);
    // die("Connection failed: " . $conn->connect_error);
    echo "connection failed";
}
$tags           = explode(', ', $tags);

if (!in_array("wholesale", $tags)) {
    $text_to_write = "Not wholesale user";
    file_put_contents($file, $text_to_write);
    // die("Not wholesale user");
    echo "Not wholesale user";
} else {
    //Get CU Uuid from Shopify userid
    $user_uuid = "";
    $sql = "SELECT uuid from cheddarup_users where shopify_id = '{$customer_id}'";
        
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
          $user_uuid = $row["uuid"];
          $text_to_write .= "1-".$user_uuid."---";
        }
     } else {
        $text_to_write = "no user";
        file_put_contents($file, $text_to_write);
        die('no user');
     }

    $url = 'https://api-dev.cheddarup.com/api/users/tabs';
    $header         = array(
        'Content-Type: application/json' ,
        'User-Id: '.$user_uuid, 
        'Authorization: Bearer ' . $api_key
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $err    = curl_error($ch);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);   
    // print_r($result);
    $catalog_id = "";
    foreach ($result as $collections) {
        if(strpos($collections['name'], 'PixieLane Online Store') !== false){
            $catalog_id = $collections['id'];
            $text_to_write .= "2-".$catalog_id."---";
        }else{
            $text_to_write .= "No Collection";
            file_put_contents($file, $text_to_write);
            die("No Collection");
        }
    }
    foreach ($line_items as $line_item) {
        $product_id = $line_item['product_id'];
        $variant_id = $line_item['variant_id'];
        $quantity   = $line_item['quantity'];
        $sku        = $line_item['sku'];
        //Check if already in collection

        $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$catalog_id.'/items';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $err    = curl_error($ch);
        if($err){
            die("Curl error-1");
        }
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch); 
        
        //Get Variant data

        $variant_url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2019-10/variants/".$variant_id.".json";
        $variant_data = file_get_contents($variant_url);
        $variant_data = json_decode($variant_data, true);
        $variant_option = 'Size_'.$variant_data['variant']['option1'];
        $text_to_write .= "2*-".$variant_option."---";      

        $item_id = "";
        $tab_id = "";
        $sql = "SELECT tab_id, category, item_id from cheddarup_items where product_id = '{$product_id}'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                
                if(in_array($variant_option,$sizes[$row["category"]])){
                    $text_to_write .= "2**-".$row["category"]."---";   
                    $tab_id = $row["tab_id"];
                    $item_id = $row["item_id"];                    
                }
            }
        } else {
            $text_to_write .= "no item";
            file_put_contents($file, $text_to_write);
            die('no item');
        }

        // print_r($item_id);
        $text_to_write .= "3-".$item_id."---";
        //Add From a Catalog onto a Collection
        $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$catalog_id.'/catalog/add';
        $data     = array(
                'catalog'       => array(
                    'id'      => $tab_id,
                    'items' => array($item_id)
                )
            );

        $payload = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $err    = curl_error($ch);
        $result = json_decode(curl_exec($ch));
        if($err){
            die("Curl error");
        }

        $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$catalog_id.'/items';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $err    = curl_error($ch);
        if($err){
            die("Curl error-1");
        }
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch); 

        if(sizeof($result) > 0){
            foreach ($result as $result_item) {
                if($result_item['catalog_object_id'] == $item_id){
                    $new_id = $result_item['id'];

                    $data     = array(
                        'change'        => $quantity,
                        'sku'        => $sku
                    );
                    $payload = json_encode($data);
                    
                    $text_to_write .= "5*-".$payload."---";
                    
                    $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$catalog_id.'/items/'.$new_id.'/adjust_quantity';
                    $text_to_write .= "6*-".$url."---";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    $err    = curl_error($ch);
                    $result = json_decode(curl_exec($ch), true);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $text_to_write .= "7-".$httpcode."---";
                    $text_to_write .= "8-".json_encode($result)."---";
                    curl_close($ch); 
                    break;                   
                }
            }

        }

    }

}
$conn->close();
file_put_contents($file, $text_to_write);
echo "Success";
?>