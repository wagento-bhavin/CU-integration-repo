<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('memory_limit', '-1');
ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0)');
require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use League\HTMLToMarkdown\HtmlConverter;
function parseHeaders( $headers )
{
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}

//API Data
$api_key 		= 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkNsaWVudCIsInN1YiI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9.eyJqdGkiOiJkNTM4NjI4NS05NDliLTQ5NTEtYjg2Ni1mNmUzNGEwY2ZhOTIiLCJleHAiOjQ3MTYwNDA5OTksImRhdGEiOnsiY2xpZW50X2lkIjoiZTJkYTY4MTgtZjExZS00M2E1LWI4ZTEtNDJmNGM0NTllYjUzIiwicGF5bG9hZCI6eyJjbGllbnQiOnsidXVpZCI6ImUyZGE2ODE4LWYxMWUtNDNhNS1iOGUxLTQyZjRjNDU5ZWI1MyJ9fX19.2Q1_PybkK684egs6stcVGmxkdetDAhSpbWFXFBEE0KY';

$partner_uuid 	= '6ea10786-ea8f-46cf-bbdd-02f9ff9a87e5';

$header 		= array(
    'Content-Type: application/json' ,
    'User-Id: '.$partner_uuid,
    'Authorization: Bearer ' . $api_key
);
//Collection and Category data
$collections = ['Girls', 'Boys', 'Babies', 'Women', 'Men'];

$categories  = array();
$categories['Girls']  = ['Tops', 'Dresses', 'Bottoms', 'Jumpsuits & Rompers', 'Swimwear'];
$categories['Boys']   = ['Tops', 'Bottoms'];
$categories['Babies'] = ['Tops', 'Dresses', 'Bottoms', 'Jumpsuits & Rompers', 'Sets'];
$categories['Women']  = ['Tops', 'Bottoms', 'Jumpsuits & Rompers', 'Sweatshirts & Cardigans'];
$categories['Men']    = ['Tops'];
$shopify_tags  = array();
$shopify_tags['Girls']  = ['Tops', 'dresses', 'Bottoms', 'Jumpsuits', 'swimwear'];
$shopify_tags['Boys']   = ['Tops', 'Bottoms'];
$shopify_tags['Babies'] = ['Tops', 'dresses', 'Bottoms', 'Romper', 'Sets'];
$shopify_tags['Women']  = ['Tops', 'Bottoms', 'Jumpsuits', 'Sweatshirts & Cardigans'];
$shopify_tags['Men']    = ['Tops'];
$sizes  = array();
$sizes['Girls']  = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Boys']   = ['Size_2', 'Size_2/3', 'Size_3', 'Size_3/4', 'Size_4', 'Size_4/5', 'Size_5', 'Size_5/6', 'Size_6', 'Size_6/7', 'Size_6P', 'Size_7', 'Size_8', 'Size_9/10', 'Size_11/12', 'Size_14'];
$sizes['Babies'] = ['Size_3/6', 'Size_6/12', 'Size_12/18', 'Size_18/24'];
$sizes['Women']  = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];
$sizes['Men']    = ['Size_XXS', 'Size_XS', 'Size_S', 'Size_M', 'Size_L', 'Size_XL'];

//Get Retail Price list from File
$price_data= [];
if (($handle = fopen("retail_price.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if($data[0]){
            $price_data["{$data[0]}"] = ($data[1]) ? $data[1] : "";
        }
    }
    fclose($handle);
}
// print_r($price_data);

//DB Credentials
$servername = "localhost";
$username = "cemmlxmy_admin";
$password = 'tKR+uEee?7RS';
$dbname = "cemmlxmy_users";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Get collection ID and subcollectino UUID Array
//Get collections
$col_ids = array();
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
if(!$err){
    $existing_cols = array();
    foreach ($result as $collection) {
        array_push($existing_cols, $collection['name']);
        if(in_array($collection['name'], $collections))
            $col_ids[$collection['name']] = $collection['id'];
    }
}
// print_r($col_ids);

//Get Categories
$cat_ids = array();
$subcat_uuids = array();
foreach ($col_ids as $col_name => $col_id) {

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
    $cat_ids[$col_name] = $result[0]['id'];
    //Get subcategory uuid array
    if($result[0]['options']['subcategories'] && sizeof($result[0]['options']['subcategories']) > 0){
        foreach ($result[0]['options']['subcategories'] as $subcategory) {
            $subcat_uuids[$col_name]["{$subcategory['name']}"] = $subcategory['uuid'];
        }
    }

}
// print_r($cat_ids);
print_r($subcat_uuids);
print_r("\n");
$converter = new HtmlConverter();
$converter->getConfig()->setOption('remove_nodes', 'meta');
$converter->getConfig()->setOption('strip_tags', true);
//Fetch Shopify Product Data
$count = 0;
$total = 0;
$url = "https://098f76e3e822c1d8871ff6c0ddc6b915:6b78d8f5583b68fe1ffbebbb2466358c@pixielane-internal-dev.myshopify.com/admin/api/2019-07/products.json?limit=1&published_status=published";
$url = "https://4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz.myshopify.com/admin/api/2019-07/products.json?limit=250&published_status=published";
do {
    $contents = file_get_contents($url);
    if($contents === FALSE) {
        echo "1";
        break;
    }
    $products = json_decode($contents,true);
    // print_r(sizeof($products['products']));
    // print_r("\n");
    if(sizeof($products['products']) > 0){
        foreach ($products['products'] as $product) {
            // print_r($product['image']['src']);
            $sql = "SELECT product_id from cheddarup_items where product_id = '{$product['id']}'";

            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                continue;
            }
            // Iterate products and get category and subcategory

            $tags = $product['tags'];
            $tags = explode(', ', $tags);
            foreach ($collections as $cat) {
                $product_cat = "";
                $product_subcat = "";

                if(count(preg_grep('/\b'.$cat.'\b/i', $tags)) > 0 && count(array_intersect($tags, $sizes[$cat])) > 0){
                    $product_cat = $cat;
                    foreach ($shopify_tags[$product_cat] as $key=>$subcat) {
                        if(count(preg_grep('/\b'.$subcat.'\b/i', $tags)) > 0){
                            $product_subcat = $categories[$product_cat][$key];
                            //Data for Cheddarup item submission
                            print_r($product['title']."----".$col_ids[$product_cat]."----".$product_cat."----".$product_subcat);
                            print_r("\n");
                            $values = $product['options'];
                            $variants_data = $product['variants'];
                            $variants = array();
                            if(sizeof($values)>0){
                                foreach ($values as $value) {
                                    if($value['name'] == "Size")
                                        $values = $value['values'];
                                }
                                $listings = array();

                                $image_id = null;

                                foreach ($variants_data as $variant) {
                                    $uuid = "";
                                    try {
                                        $uuid = Uuid::uuid4();
                                        $uuid = $uuid->toString();
                                    }catch (UnsatisfiedDependencyException $e) {
                                        echo 'Caught exception: ' . $e->getMessage() . "\n";
                                    }

                                    $amount = (array_key_exists($variant['sku'], $price_data)) ? floatval($price_data[$variant['sku']]) : 100;
                                    $temp = array(
                                        'sku' => $variant['sku'],
                                        'amount' => $amount,
                                        'uuid' => $uuid,
                                        'imageId' => $image_id,
                                        'description' => $variant['title'],
                                        // 'retailPrice' => ($price_data[$variant['sku']]) ? floatval($price_data[$variant['sku']]) : 10,
                                        'optionValues' => array(
                                            'Size' => $variant['option1']
                                        ),
                                        'available_quantity' => 0
                                    );
                                    array_push($listings, $temp);

                                }
                                $variants = array(
                                    'enabled'	=> true,
                                    'options'	=> array( array(
                                        'key' => 'Size',
                                        'values' => $values
                                    )
                                    ),
                                    'listings' => $listings
                                );
                            }

                            $item_subcategory_id = $subcat_uuids[$product_cat][$product_subcat];
                            $markdown = $converter->convert($product['body_html']);
                            $data     = array(
                                'tab_id'		=> $col_ids[$product_cat],
                                'name'			=> $product['title'],
                                'amount'		=> 0,
                                'amount_type'	=> 'fixed',
                                'parent_id'		=> $cat_ids[$product_cat],
                                'description'	=> $markdown,
                                'options' 		=> array(
                                    'variants' 		=> $variants,
                                    'subcategoryId' => $item_subcategory_id
                                )
                            );
                            // print_r($item_subcategory_id);
                            // print_r("\n");
                            $payload = json_encode($data);

                            $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_ids[$product_cat].'/items';

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                            curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS,$payload);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                            $err    = curl_error($ch);
                            $result = json_decode(curl_exec($ch));
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            print_r("-1--".$httpcode);
                            // print_r($result);
                            curl_close($ch);
                            $shopify_id = $product['id'];
                            if($httpcode == 200){
                                $sql = "INSERT INTO cheddarup_items (product_id, tab_id, category_id, category, subcat_id, subcategory, item_id, created_at) VALUES ('{$shopify_id}','{$result->tab_id}','{$result->parent_id}','{$product_cat}','{$item_subcategory_id}','{$product_subcat}','{$result->id}','{$result->created_at}')";
                                if ($conn->query($sql) === TRUE) {
                                    echo "New record";
                                } else {
                                    echo "Error: " . $sql . "<br>" . $conn->error;
                                }
                            }else{
                                print_r($url);
                                print_r("\n");
                                print_r($payload);
                                print_r("\n");
                                continue;
                            }

                            //Image part

                            $variant_image_url = ($product['image']['src']) ? $product['image']['src'] : '';
                            $image_width = ($product['image']['width']) ? $product['image']['width'] : 100;
                            $image_height = ($product['image']['height']) ? $product['image']['height'] : 100;
                            $image_name = explode('/',$variant_image_url);
                            $image_name = end($image_name);
                            $image_name = explode('?',$image_name);
                            $image_ext = explode('.', $image_name[0]);
                            $item_id = $result->id;
                            // $item_id = 20860;
                            $signedurl = "";
                            $uploadPath = "";
                            $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_ids[$product_cat].'/items/'.$item_id.'/signed_upload_url';
                            // print_r($variant_image_url."------".$image_width."------".$image_height);
                            // print_r("\n");
                            // print_r($url);
                            // print_r("\n");
                            $data     = array(
                                'objectName'		=> $image_name[0],
                                'metadata' 			=> array(
                                    'contentType' 		=> 'image/'.end($image_ext)
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
                            $result = json_decode(curl_exec($ch),true);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            print_r("-2--".$httpcode);
                            curl_close($ch);
                            if($httpcode == 200){
                                $signedurl = $result['signedUrl'];
                                $uploadPath = $result['uploadPath'];
                            }
                            // $signedurl = 'https://s3-us-west-2.amazonaws.com/cheddar-up-review/uploads/image/signed/1430/TabObject/20860/baby_pant_Orchid_Ivory_Elephant_492c5773-7b66-450d-bb2d-1fc38bdf4a06.jpg?X-Amz-Expires=600&X-Amz-Date=20191004T093301Z&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAICUG63NJUCO4RYCA/20191004/us-west-2/s3/aws4_request&X-Amz-SignedHeaders=content-type%3Bhost%3Bx-amz-acl&X-Amz-Signature=91d11c053e804211ff92fd20757c687b98200a131ea5baddad9d14a076dfe2b2';
                            // $uploadPath = 'uploads/image/signed/1430/TabObject/20860/baby_pant_Orchid_Ivory_Elephant_492c5773-7b66-450d-bb2d-1fc38bdf4a06.jpg';
                            // $b64image = base64_encode(file_get_contents($variant_image_url));
                            $image_data = file_get_contents($variant_image_url);
                            // print_r($b64image);
                            $image_header 		= array(
                                'Content-Type: image/jpg',
                                'x-amz-acl: public-read'
                            );
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $signedurl);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS,$image_data);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $image_header);
                            $err    = curl_error($ch);
                            $result = curl_exec($ch);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            print_r("-3--".$httpcode);
                            // print_r("-3*--".$variant_image_url);
                            // print_r("-3**--".$signedurl);
                            //Create Database Record for image
                            $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_ids[$product_cat].'/items/'.$item_id.'/create_image_record';

                            $data     = array(
                                'objectName'		=> $image_name[0],
                                'upload_path'		=> $uploadPath,
                                'metadata' 			=> array(
                                    'contentType' 		=> 'image/'.end($image_ext)
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
                            $result = json_decode(curl_exec($ch),true);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            if($httpcode == 200){
                                $image_id = $result['id'];
                            }
                            print_r("-4--".$httpcode);
                            print_r("-imageID=".$image_id);
                            //Update variants for image id
                            $url = 'https://api-dev.cheddarup.com/api/users/tabs/'.$col_ids[$product_cat].'/items/'.$item_id;
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                            $result = json_decode(curl_exec($ch), true);
                            curl_close($ch);
                            $new_options = $result['options'];
                            foreach ($new_options['variants']['listings'] as $key => $value) {
                                $new_options['variants']['listings'][$key]['imageId'] = $image_id;
                            }
                            array_pop($new_options);
                            $data     = array(
                                'options'        => $new_options
                            );

                            $payload = json_encode($data);
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
                            // print_r($result);
                            // print_r("\n");
                            print_r("-5--".$httpcode);
                            print_r("\n");
                            print_r("\n");
                            curl_close($ch);
                            // break;
                        }
                    }
                }
                //Display mis tagged Products
                if($product_cat == "" || $product_subcat ==""){
                    print_r($product['title']);
                    print_r("\n");
                    continue;
                }
            }
            $total++;
            print_r("-----------------------------\n");
        }
        $http_header = parseHeaders($http_response_header);
        $http_header = explode(',', $http_header['Link']);

        if(sizeof($http_header) > 1)
            $http_header = $http_header[1];
        else
            $http_header = $http_header[0];
        $url = explode(';', $http_header);
        $rel = $url[1];
        $url = $url[0];

        // print_r($http_header);
        // print_r("\n");
        if (strpos($rel, 'next') !== false) {
            $url = trim($url);
            $url = str_replace('<', '', $url);
            $url = str_replace('>', '', $url);
            // $url = str_replace('pixielane-internal', '098f76e3e822c1d8871ff6c0ddc6b915:6b78d8f5583b68fe1ffbebbb2466358c@pixielane-internal', $url);
            $url = str_replace('pixielanekidz', '4d87206512af68fc951ce70348bb8105:10ee2c6ba02eef12fdda08912d318c49@pixielanekidz', $url);
        }else{
            echo "2";
            break;
        }
        $count = sizeof($products['products']);
    }else{
        echo "3";
        break;
    }
    // break;

} while ($count > 0);
$conn->close();
print_r("---".$total."---");
?>