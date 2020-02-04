<?php
error_reporting(E_ALL);

require '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use PHPShopify\ShopifySDK;

//DB Credentials
$servername = "localhost";
/*$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";*/
$username = "ycfssmjzrs";
$password = 'BPc98qqeVA';
$dbname = "ycfssmjzrs";

$config = array(
    'ShopUrl' => 'pixielanekidz.myshopify.com',
    'ApiKey' => '4d87206512af68fc951ce70348bb8105',
    'Password' => '10ee2c6ba02eef12fdda08912d318c49',
    'ApiVersion' => '2019-04'
);

//Create the shopify client object
$shopify = ShopifySDK::config($config);
$webhook_content = NULL;
$webhook = fopen('php://input', 'rb');

while (!feof($webhook)) {
    $webhook_content .= fread($webhook, 4096);
}

fclose($webhook);

$file = 'order_webhook.txt';
$text_to_write = "";

$order = json_decode($webhook_content, true);
$orders[] = json_decode($webhook_content, true);
$conn = new mysqli($servername, $username, $password, $dbname);

foreach ($orders as $_order):
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $order_id = $_order['id'];
    $order_number = $_order['order_number'];
    $customer_id = $order['customer']['id'];
    $tags = $order['customer']['tags'];
    $f_name = $_order['shipping_address']['name'];
    $payment_gateway = $_order['gateway'];
    $financial_status = $_order['financial_status'];
    $order_value = $_order['total_price'];
    $order_status = $_order['fulfillment_status'];
    $shipping_province = $_order['shipping_address']['province'];
    $created_at = $_order['created_at'];
    $updated_at = $_order['updated_at'];
    if (!empty($_order['shipping_lines'])) {
        $shipping_method = $_order['shipping_lines'][0]['title'];
    } else {
        $shipping_method = '';
    }

    $items = [];
    $tagsArray = explode(', ', $tags);
    if (!in_array('wholesale', $tagsArray)) {
        continue;
    }
    foreach ($_order["line_items"] as $item) {
        $items[$item["id"]]['item_id'] = $item["id"];
        $items[$item["id"]]['variant_id'] = $item["variant_id"];
        $items[$item["id"]]['quantity'] = $item["quantity"];
        $items[$item["id"]]['sku'] = $item["sku"];
        $items[$item["id"]]['customer_id'] = $customer_id;
        $items[$item["id"]]['order_id'] = $order_id;
        $items[$item["id"]]['product_id'] = $item["product_id"];
    }
    $items = serialize($items);

    $selectQuery = "SELECT * FROM order_request WHERE order_id='$order_id'";
    $result = $conn->query($selectQuery);
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO order_request(order_id, order_number, customer_id, tags, cust_fname, payment_gateway,
financial_status, items, order_value, order_status, ship_to, created_at, updated_at, shipping_method)
VALUES ('{$order_id}', '{$order_number}', '{$customer_id}', '{$tags}', '{$f_name}', '{$payment_gateway}', 
'{$financial_status}', '{$items}', '{$order_value}', '{$order_status}', '{$shipping_province}', '{$created_at}',
 '{$updated_at}', '{$shipping_method}')";

        if ($conn->query($sql) === TRUE) {
            echo "\nSuccess\n";
        }
    }
endforeach;
$selectQuery = "SELECT * FROM order_request WHERE order_id='{$order_id}'";

$result = $conn->query($selectQuery);
$customer_id = '';
$line_item = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $line_item = $row['items'];
        $customer_id = $row['customer_id'];
    }
}

$line_items = array();
$line_items = unserialize($line_item);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("\nConnection failed: " . $conn->connect_error);
}
//Get CU Uuid from Shopify userid
foreach ($line_items as $line_item) {
    $product_id = $line_item['item_id'];
    $variant_id = $line_item['variant_id'];
    $orderId = $line_item['order_id'];
    $quantity = $line_item['quantity'];
    $sku = $line_item['sku'];
    $variant_url = $shopify->getApiUrl() . "variants/" . $variant_id . ".json";
    $variant_data = file_get_contents($variant_url);
    $variant_data = json_decode($variant_data, true);
    // Data for insert in table
    $id = $variant_data['variant']['id'];
    $vProductId = $variant_data['variant']['product_id'];
    $vTitle = $variant_data['variant']['title'];
    $vPrice = $variant_data['variant']['price'];
    $vSku = $variant_data['variant']['sku'];
    $vInvPolicy = $variant_data['variant']['inventory_policy'];
    $vFulFillServ = $variant_data['variant']['fulfillment_service'];
    $option1 = $variant_data['variant']['option1'];
    $option2 = $variant_data['variant']['option2'];
    $option3 = $variant_data['variant']['option3'];
    $vInvMgmt = $variant_data['variant']['inventory_management'];
    $createdAt = $variant_data['variant']['created_at'];
    $updatedAt = $variant_data['variant']['updated_at'];
    $taxable = $variant_data['variant']['taxable'];
    $weight = $variant_data['variant']['weight'];
    $weightUnit = $variant_data['variant']['weight_unit'];
    $invItemId = $variant_data['variant']['inventory_item_id'];
    $invQty = $variant_data['variant']['inventory_quantity'];
    $oldInvQty = $variant_data['variant']['old_inventory_quantity'];
    $requireShip = $variant_data['variant']['requires_shipping'];
    // End

    $sql = "INSERT INTO shopify_variant(variant_id, product_id, title, price, sku, inventory_policy,
fulfillment_service, option1, option2, option3, inventory_management, created_at, updated_at, taxable, weight, weight_unit, inventory_item_id,
inventory_quantity, old_inventory_quantity, requires_shipping, order_id)
VALUES ('{$id}', '{$vProductId}', '{$vTitle}', '{$vPrice}', '{$vSku}', '{$vInvPolicy}', '{$vFulFillServ}', '{$option1}',
 '{$option2}', '{$option3}', '{$vInvMgmt}', '{$createdAt}', '{$updatedAt}', '{$taxable}', '{$weight}', '{$weightUnit}', '{$invItemId}',
'{$invQty}', '{$oldInvQty}', '{$requireShip}', '{$orderId}')";

    if ($conn->query($sql) === TRUE) {
        echo "Variant data added successfully!\n";
    } else {
        $text_to_write .= "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
file_put_contents($file, $text_to_write);
echo "\nSuccess\n";
?>