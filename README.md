# CU-integration-repo

Following tables need to be created in the database.

1. order_request

```javascript
CREATE TABLE `order_request` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Request Id',
 `order_id` varchar(255) NOT NULL,
 `order_number` int(11) NOT NULL,
 `customer_id` varchar(255) NOT NULL,
 `tags` varchar(255) NOT NULL COMMENT 'customer tags',
 `cust_fname` varchar(255) NOT NULL,
 `payment_gateway` varchar(255) NOT NULL,
 `financial_status` varchar(255) NOT NULL,
 `items` longtext NOT NULL,
 `order_value` varchar(255) NOT NULL,
 `order_status` varchar(255) NOT NULL,
 `ship_to` varchar(255) NOT NULL,
 `created_at` varchar(255) NOT NULL,
 `updated_at` varchar(255) NOT NULL,
 `shipping_method` varchar(255) NOT NULL,
 `cron_status` varchar(11) NOT NULL DEFAULT 'pending' COMMENT 'Status for the Cron status from shopify to cheddarup',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1
```
2. shopify_variant

```javascript
CREATE TABLE `shopify_variant` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `variant_id` varchar(255) NOT NULL,
 `product_id` varchar(255) NOT NULL,
 `title` varchar(255) NOT NULL,
 `price` varchar(255) NOT NULL,
 `sku` varchar(255) NOT NULL,
 `inventory_policy` varchar(255) NOT NULL,
 `fulfillment_service` varchar(255) NOT NULL,
 `inventory_management` varchar(255) NOT NULL,
 `option1` varchar(50) DEFAULT NULL,
 `option2` varchar(50) DEFAULT NULL,
 `option3` varchar(50) DEFAULT NULL,
 `created_at` varchar(255) NOT NULL,
 `updated_at` varchar(255) NOT NULL,
 `taxable` text NOT NULL,
 `weight` varchar(10) NOT NULL,
 `weight_unit` varchar(255) NOT NULL,
 `inventory_item_id` varchar(255) NOT NULL,
 `inventory_quantity` varchar(255) NOT NULL,
 `old_inventory_quantity` varchar(255) NOT NULL,
 `requires_shipping` text NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1
```

Also need to run **composer install** command to install dependency.
