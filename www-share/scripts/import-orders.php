<?php

require_once('./config/config.inc.php');
require_once ('/www-share/scripts/importing_db_tables.php');

$db = Db::getInstance();

echo "Importing\n";

$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];

function convert_currency($from) {
  $converted = $from;
  $converted['numeric_iso_code'] = $from['iso_code_num'];
  unset($converted['iso_code_num']);
  unset($converted['sign']);
  unset($converted['blank']);
  unset($converted['format']);
  unset($converted['decimals']);
  return $converted;
}

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

function import_currency_symbols($currencies, $langs) {
  empty_table('ps_currency_lang');
  foreach($langs as $lang) {
    foreach($currencies as $currency) {
      $data = [
        'id_currency' => $currency['id_currency'],
        'id_lang' => $lang['id_lang'],
        'name' => $currency['name'],
        'symbol' => $currency['sign']
      ];
      insert('ps_currency_lang', $data);
    }
  }
}

function identity($x) {
  return $x;
}

$converters = [
  'ps_currency' => 'convert_currency',
  'ps_orders' => 'convert_order',
  'ps_employee' => 'convert_employee'
];

function insert($table, $data) {
  global $db;
  $res = $db->insert($table, $data, false, false, Db::INSERT, false);
  if (!$res) {
    print_r($data);
    die("Insert into " . $table . " failed: " . $db->getMsgError() . "\n");
  }
}

function empty_table($table) {
  global $db;
  $res = $db->delete($table, '', 0, false, false);
  if (!$res) {
    die("Delete of " . $table . " failed: " . $db->getMsgError() . "\n");
  }
}

function escape($row) {
  return array_map(
    function($value) { return pSQL($value); },
    $row
  );
}

function import_table($table, $rows) {
  global $converters;
  empty_table($table);
  if (array_key_exists($table, $converters)) {
    $converter = $converters[$table];
  } else {
    $converter = 'identity';
  }
  $count = 0;
  foreach($rows as $row) {
    $converted = $converter($row);
    $escaped = escape($converted);
    insert($table, $escaped);
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
  'ps_order_detail',
  'ps_order_history',
  'ps_order_state',
  'ps_order_state_lang',
  'ps_employee',
  'ps_profile',
  'ps_profile_lang',
];

foreach($to_import as $t) {
  import_table($t, $tables[$t]);
}

import_currency_symbols($tables['ps_currency'], $tables['ps_lang']);

echo "Done\n";


?>

