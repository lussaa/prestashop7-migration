<?php

require_once('./config/config.inc.php');

$db = Db::getInstance();

echo "Importing\n";

$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];

function convert_currency($from) {
  $converted = $from;
  return $converted;
}

function identity($x) {
  return $x;
}

$converters = [
  'ps_currency' => 'convert_currency'
];

function insert($table, $data) {
  global $db;
  $res = $db->insert($table, $data, false, false, Db::INSERT, false);
  if (!$res) {
    print_r($data);
    die("Insert into " . $table . " failed: " . $db->getMsgError() . "\n");
  }
}

function import_table($table, $rows) {
  global $converters, $db;
  $res = $db->delete($table, '', 0, false, false);
  if (!$res) {
    die("Delete of " . $table . " failed: " . $db->getMsgError() . "\n");
  }
  if (array_key_exists($table, $converters)) {
    $converter = $converters[$table];
  } else {
    $converter = 'identity';
  }
  $count = 0;
  foreach($rows as $row) {
    $converted = $converter($row);
    insert($table, $converted);
    $count++;
  }
  echo "Inserted " . $count . " rows into " . $table . "\n";
}

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

echo "Done\n";


?>

