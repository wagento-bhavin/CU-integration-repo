<?php
//DB Credentials
$servername = "localhost";
$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_CU_PRD";

$api_key = 'eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJjOTg1ZWI3Mi0xMjRkLTQxMWYtYThlYi03NDRlODIxZGU3MGUiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJlZGQ5MTFmYi01YjRmLTQyMjctODAzYi0xZWJkMzUwYTQyNmMiLCJleHAiOjQ3MzIzNzc5NjksImRhdGEiOnsiY2xpZW50X2lkIjoiYzk4NWViNzItMTI0ZC00MTFmLWE4ZWItNzQ0ZTgyMWRlNzBlIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImM5ODVlYjcyLTEyNGQtNDExZi1hOGViLTc0NGU4MjFkZTcwZSJ9fX19.7Gp2PhtdxmP43WynSLJyQy7IOUx1gRkK8s0VppAaDc4';

$sizes = array();
$sizes['Girls'] = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Boys'] = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Babies'] = ['Size_3/6', 'Size_6/12', 'Size_12/18', 'Size_18/24'];
$sizes['Women'] = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];
$sizes['Men'] = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];

$file = 'Order_Cron.md';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$customer_id = '';
$tags = '';
$line_item = '';
$order_id = '';
$selectQuery = "SELECT * FROM order_request where cron_status = 'pending'";
$result = $conn->query($selectQuery);

// If table contains data for the orders to be processed

/**
 * @param $text_to_write
 * @param $url
 * @param $file
 * @param $result
 */
function writeToFile($text_to_write, $url, $file, $result)
{
    $text_to_write .= "\n";
    $text_to_write .= $url."\n";
    $text_to_write .= "\nResult\n";
    $text_to_write .= "```javascript\n";
    $text_to_write .= $result;
    $text_to_write .= "\n```\n";
    file_put_contents($file, $text_to_write, FILE_APPEND);
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $line_item = $row['items'];
        $customer_id = $row['customer_id'];
        $tags = $row['tags'];
        $order_id = $row['order_id'];
    }
    $line_items = array();
    $line_items = unserialize($line_item);
    $tags = explode(', ', $tags);

    if (!in_array("wholesale", $tags)) {
         die("Not wholesale user");
    } else {
        $user_uuid = "";
        $sql = "SELECT uuid from cheddarup_users where shopify_id = '{$customer_id}'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $user_uuid = $row["uuid"];
            }
        } else {
            die('no user');
        }
        $url = 'https://api.cheddarup.com/api/users/tabs';
        $header = array(
            'Content-Type: application/json',
            'User-Id: ' . $user_uuid,
            'Authorization: Bearer ' . $api_key
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $err = curl_error($ch);
        $result = json_decode(curl_exec($ch), true);
        writeToFile('# Get Collection', $url, $file, curl_exec($ch));
        curl_close($ch);

        $catalog_id = "";
        foreach ($result as $collections) {
            if (strpos($collections['name'], 'PixieLane Online Store') !== false) {
                $catalog_id = $collections['id'];
            } else {
                die("No Collection");
            }
        }

        foreach ($line_items as $line_item) {

            $product_id = $line_item['item_id'];
            $variant_id = $line_item['variant_id'];
            $quantity = $line_item['quantity'];
            $sku = $line_item['sku'];
            $variant_option = '';
            $url = 'https://api.cheddarup.com/api/users/tabs/' . $catalog_id . '/items';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $err = curl_error($ch);
            if ($err) {
                die("Curl error-1");
            }
            $result = json_decode(curl_exec($ch), true);
            writeToFile('# Create Items', $url, $file, curl_exec($ch));
            curl_close($ch);
            $seletVariantSql = "select * from shopify_variant where variant_id = '{$variant_id}'";
            $variantResult = mysqli_query($conn, $seletVariantSql);
            // If variant data exist
            if (mysqli_num_rows($variantResult) > 0) {
                while ($raw = mysqli_fetch_assoc($variantResult)) {
                    $variant_option = 'Size_' . $raw['option1'];
                }
            }
            // End if
            $item_id = "";
            $tab_id = "";
            $sql = "SELECT tab_id, category, item_id from cheddarup_items where product_id = '{$product_id}'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if (in_array($variant_option, $sizes[$row["category"]])) {
                        $tab_id = $row["tab_id"];
                        $item_id = $row["item_id"];
                    }
                }
            } else {
                die("no item(s)");
            }
            $url = 'https://api.cheddarup.com/api/users/tabs/' . $catalog_id . '/catalog/add';
            $data = array(
                'catalog' => array(
                    'id' => $tab_id,
                    'items' => array($item_id)
                )
            );
            $payload = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $err = curl_error($ch);
            $result = json_decode(curl_exec($ch));
            writeToFile('# Add From a Catalog onto a Collection', $url, $file, curl_exec($ch));
            if ($err) {
                die("Curl error");
            }

            $url = 'https://api.cheddarup.com/api/users/tabs/' . $catalog_id . '/items';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $err = curl_error($ch);
            if ($err) {
                die("Curl error-1");
            }
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (sizeof($result) > 0) {
                foreach ($result as $result_item) {
                    if ($result_item['catalog_object_id'] == $item_id) {
                        $new_id = $result_item['id'];

                        $data = array(
                            'change' => $quantity,
                            'sku' => $sku
                        );
                        $payload = json_encode($data);

                        $url = 'https://api.cheddarup.com/api/users/tabs/' . $catalog_id . '/items/' . $new_id . '/adjust_quantity';

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                        $err = curl_error($ch);
                        $result = json_decode(curl_exec($ch), true);
                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        writeToFile('# Adjust Variant Quantity', $url, $file, curl_exec($ch));
                        curl_close($ch);
                        break;
                    }
                }

            }
        }
    }
    echo "Success\n";
} else {
    echo "Error: " . $selectQuery . "<br>" . $conn->error;
}
$conn->close();
