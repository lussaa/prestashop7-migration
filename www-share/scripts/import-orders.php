<?php

require_once('./config/config.inc.php');
require_once ('/www-share/scripts/importing_db_tables.php');

$db = Db::getInstance();

echo "Importing\n";

$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];


$converters = [
  'ps_currency' => 'convert_currency'

];


$to_import = [
  'ps_orders',
  'ps_cart',
  'ps_address',
  'ps_currency',
  'ps_cart_product',
  'ps_country',
  'ps_country_lang',
  'ps_state',
  'ps_zone',
];

foreach($to_import as $t) {
  import_table($t, $tables[$t]);
}

import_currency_symbols($tables['ps_currency'], $tables['ps_lang']);

echo "Done\n";


?>

