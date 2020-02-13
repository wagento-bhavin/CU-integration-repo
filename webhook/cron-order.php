<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$servername = "localhost";
$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";
/*$username = "ycfssmjzrs";
$password = 'BPc98qqeVA';
$dbname = "ycfssmjzrs";*/

$api_key = 'eyJ0eXAiOiJDbGllbnQiLCJzdWIiOiJlMmRhNjgxOC1mMTFlLTQzYTUtYjhlMS00MmY0YzQ1OWViNTMiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiIwMGVlODg0My1mOWI2LTRmZjMtYmFjMC1jYzVkZWJjZTA3NTciLCJleHAiOjE4OTQ5MTMxNTQsImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.QA77p82AicXUxpIt-TOvEtopx4ikExctv8RvyDe8iPc';

$sizes = array();
$sizes['Girls'] = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Boys'] = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Babies'] = ['Size_3/6', 'Size_6/12', 'Size_12/18', 'Size_18/24'];
$sizes['Women'] = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];
$sizes['Men'] = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];

$errorLog = "inventory.log";


$conn = new mysqli($servername, $username, $password, $dbname);

$selectQuery = "SELECT *, uuid FROM order_request left join cheddarup_users on order_request.customer_id = cheddarup_users.shopify_id where cron_status = 'pending'";
$result = $conn->query($selectQuery);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    foreach ($posts as $post) {
        $line_items = unserialize($post['items']);

        echo "\nFor customer Id: '{$post['customer_id']}' \n";
        //exit;
        $user_uuid = $post["uuid"];
        $url = 'https://api-dev.cheddarup.com/api/users/tabs';
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
        curl_close($ch);
        $catalog_id = "";
        foreach ($result as $collections) {
            if (strpos($collections['name'], 'PixieLane Online Store') !== false) {
                $catalog_id = $collections['id'];
            }
        }
        $itemsCount = 1;
        foreach ($line_items as $key => $line_item) {
            if ($post['customer_id'] == $line_item['customer_id']) {
                $product_id = $line_item['product_id'];
                $variant_id = $line_item['variant_id'];
                $quantity = $line_item['quantity'];
                $sku = $line_item['sku'];
                $orderId = $line_item['order_id'];
                $variant_option = '';
                echo "\nItems count {$itemsCount} for order '{$orderId}' for product '{$product_id}'\n";
                $seletVariantSql = "select * from shopify_variant where variant_id = '{$variant_id}' and order_id = '{$orderId}'";
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
                    echo "\n No item(s) with product id '{$product_id}' for the order '{$orderId}'\n";
                    continue;
                }
                $url = 'https://api-dev.cheddarup.com/api/users/tabs/' . $catalog_id . '/catalog/add';
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
                if ($err) {
                    file_put_contents($errorLog, 'Add catalog ' . $orderId, FILE_APPEND);
                    file_put_contents($errorLog, print_r($err, true), FILE_APPEND);
                    // continue;
                }

                $url = 'https://api-dev.cheddarup.com/api/users/tabs/' . $catalog_id . '/items';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $err = curl_error($ch);
                if ($err) {
                    continue;
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

                            $url = 'https://api-dev.cheddarup.com/api/users/tabs/' . $catalog_id . '/items/' . $new_id . '/adjust_quantity';

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
                            if ($err) {
                                file_put_contents($errorLog, 'Error on update quantity for the' . $orderId . ' for product ' . $product_id, FILE_APPEND);
                                file_put_contents($errorLog, print_r($err, true), FILE_APPEND);
                                // continue;
                            }
                            curl_close($ch);
                        }
                    }
                }
                $itemsCount++;
                $updateSql = "Update `order_request` set `cron_status` = 'success' WHERE `order_id` = " . $orderId;
                if ($conn->query($updateSql) == true) {
                    print_r("\n Record Updated Successfully\n");
                } else {
                    print_r("\nError on Update order: " . $conn->error . " \n");
                }
            }
        }
    }
    echo "\nInventory Updated Successfully\n";
}
$conn->close();
exit;
?>
