<?php

require_once('./config/config.inc.php');
require_once ('/www-share/scripts/importing_db_tables.php');
require_once ('/www-share/scripts/import-products.php');

$db = Db::getInstance();
$input = "/www-share/data/model_converted.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];


echo "Deleting all products\n";

delete_products();

echo "Importing...\n";



echo "Importing cookie key (user passwords salt)\n";
$cookiki = $obj['config']['cookie_key'];
$config_file = './app/config/parameters.php';
$config = file_get_contents($config_file);
$current_cookie_key = _COOKIE_KEY_;
$config = str_replace($current_cookie_key, $cookiki, $config);
$res = file_put_contents($config_file, $config);


function convert_order($from) {
  $converted = $from;
  $converted['reference'] = 'M' . $from['id_order'];
  return $converted;
}

function convert_employee($from) {
  $converted = $from;
  unset($converted['bo_uimode']);
  return $converted;
}

$converters = [
  'ps_currency' => 'convert_currency',
  'ps_orders' => 'convert_order',
  'ps_employee' => 'convert_employee',
   'ps_product_attribute' => 'convert_default_on_zero',
   'ps_product_attribute_combination' => 'delete_non_existing_column',
   'ps_attribute_group' => 'add_group_type',
    'ps_attribute' => 'color_not_null_because_it_is_size'
];


// TODO use DB_PREFIX
$to_import = [
  'ps_lang',
  'ps_orders',
  'ps_cart',
  'ps_address',
  'ps_currency',
  'ps_customer',
  'ps_cart_product',
  'ps_country',
  'ps_country_lang',
  'ps_state',
  'ps_zone',
  'ps_order_detail',
  'ps_order_history',
  'ps_order_state',
  'ps_order_state_lang',
  'ps_employee',
  'ps_profile',
  'ps_profile_lang',
  'ps_category',
  'ps_category_group',
  'ps_category_lang',
  'ps_attribute_group',
  'ps_attribute_group_lang',
  'ps_attribute',
  'ps_attribute_lang',
  'ps_product_attribute',
  'ps_product_attribute_combination',
  'ps_product',
  'ps_product_lang',
  'ps_tag',
  'ps_product_tag',
  'ps_product_stickaz',
  'ps_product_sale'
];

foreach($to_import as $t) {
  import_table($t, $tables[$t]);
}

import_currency_symbols($tables['ps_currency'], $tables['ps_lang']);

add_special_presta7_shop_tables($tables,'ps_attribute_shop', 'id_attribute', 'ps_attribute' );
add_special_presta7_shop_tables($tables,'ps_attribute_group_shop','id_attribute_group', 'ps_attribute_group');
add_special_presta7_shop_tables($tables,'ps_product_attribute_shop','id_product_attribute', 'ps_product_attribute',
    array("id_product", "price", "ecotax", "wholesale_price", "weight", "unit_price_impact", "minimal_quantity", "default_on"));


echo "Done with attributes and combinations. \n";
echo "Importing products.\n";



echo "Importing products. \n";
$raw_products = $obj['products'];

import_products($raw_products);

echo "Done\n";

?>

