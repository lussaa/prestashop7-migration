<?php

require_once('./config/config.inc.php');
require_once ('/www-share/scripts/importing_db_tables.php');

$db = Db::getInstance();

echo "Importing\n";

$input = "/www-share/data/model_converted.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];


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
  'ps_employee' => 'convert_employee'
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
];

foreach($to_import as $t) {
  import_table($t, $tables[$t]);
}

import_currency_symbols($tables['ps_currency'], $tables['ps_lang']);

echo "Done\n";


?>

