<?php
/**
 * Created by IntelliJ IDEA.
 * User: lucia
 * Date: 2/21/21
 * Time: 12:26 PM
 */


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

    function convert_default_on_zero($row){

         if ($row['id_product'] === 991 && $row['id_product_attribute']>4792){
             return NULL;
         }
         if ($row['default_on'] === 0 ){
            $row['default_on'] = NULL;
         }
         return $row;

    }

     function delete_non_existing_column($row){
         unset($row['stickaz_qty']);
         return $row;
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


    function insert($table, $data, $null_values = false) {
        global $db;
        if ($table === 'ps_attribute'){
             $null_values = false;
             $res = $db->insert($table, $data, $null_values, false, Db::INSERT, false);
        } else {
            $res = insert_as_is($table, [$data]);
        }
        if (!$res) {
            print_r($data);
            die("Insert into " . $table . " failed: " . $db->getMsgError() . "\n");
        }
    }

    function insert_as_is($table, $data) {
        $keys = [];
        $values_stringified = [];
        $first_loop = true;
        foreach ($data as $row_data) {
            $values = [];
            foreach ($row_data as $key => $value) {
                if ($first_loop) {
                    $keys[] = '`' . bqSQL($key) . '`';
                }
                $values[] = (null === $value) ? 'NULL' : "'{$value}'";
            }
            $first_loop = false;
            $values_stringified[] = '(' . implode(', ', $values) . ')';
        }
        $keys_stringified = implode(', ', $keys);

        $sql = 'INSERT INTO `' . $table . '` (' . $keys_stringified . ') VALUES ' . implode(', ', $values_stringified);

        global $db;
        $res = $db->query($sql);
        return $res;
    }

    function empty_table($table) {
        global $db;
        echo "Deletion of contents of " .$table ."\n";
        $res = $db->delete($table, '', 0, false, false);
        if (!$res) {
            die("Delete of " . $table . " failed: " . $db->getMsgError() . "\n");
        }
    }

     function escape($row) {
        return array_map(
            function($value) {
                if (is_array($value)) {
                    return $value;
                } else {
                    return pSQL($value);
                }
            },
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
            if ($converted != NULL){

                $escaped = escape($converted);
                insert($table, $escaped, true);
                $count++;
            }


        }
        echo "Inserted " . $count . " rows into " . $table . "\n";
    }

