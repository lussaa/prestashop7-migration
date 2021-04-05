<?php
/**
 * Created by IntelliJ IDEA.
 * User: lucia
 * Date: 2/21/21
 * Time: 12:26 PM
 */

    CONST duplicate_key_error = 1062;

    
    function add_special_presta7_shop_tables($tables, $table_to_insert = 'ps_attribute_group_shop', $id_name = 'id_attribute_group', $source_table = 'ps_attribute_group',
                                             $additional_columns_to_copy = NULL){
        empty_table($table_to_insert);

        $source_columns = $tables[$source_table]['columns'];
        $rows_source = $tables[$source_table]['rows'];
        $id_shop = 1;
        $count = 0;
        foreach ($rows_source as $row_source_array){ //foreach id_attribute_group
            $row_source = zip($row_source_array, $source_columns);
            $source_id_value = $row_source[$id_name];
            if ($additional_columns_to_copy === NULL){
                $escaped = escape(array($id_name => $source_id_value, "id_shop" => $id_shop));
            }else{
                $row_to_insert= array($id_name => $source_id_value, "id_shop" => $id_shop, );
                foreach ($additional_columns_to_copy as $column){
                    $row_to_insert[$column] = $row_source[$column];
                }
                $escaped = escape($row_to_insert);
            }

            insert($table_to_insert, $escaped, true);
            $count++;
        }
        echo "Inserted " .$count ." rows in " .$table_to_insert ."\n";
    }

     function identity($x) {
        return $x;
    }


    function insert($table, $data, $null_values = false) {
        global $db;
        $res = insert_as_is($table, [$data]);
        if (!$res) {
            print_r($data);
            if($db->getNumberError() === duplicate_key_error){
                echo "Duplicate key error for " .$table ."\n" .$db->getMsgError() ."\n";
            }else {
                die("Insert into " . $table . " failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
            }
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
        echo "Deletion of contents of " .$table ." finished. \n";
    }

     function escape($row) {
        return array_map(
            function($value) {
                if (is_array($value)) {
                    return $value;
                } elseif (is_null($value)){
                    return $value;
                }
                else {
                    return pSQL($value);
                }
            },
            $row
        );
    }

     function import_table($table_name, $table_data) {
        global $converters;
        empty_table($table_name);
        $converter = 'identity';

        $count = 0;
        $columns = $table_data['columns'];
        foreach($table_data['rows'] as $row) {
            $converted = convert($converter, $row, $columns);
            if ($converted != NULL) {
                $escaped = escape($converted);
                $rows = [$escaped]; // TODO dont insert 1 by 1
                insert_compact($table_name, $columns, $rows);
                $count++;
            }


        }
        echo "Inserted " . $count . " rows into " . $table_name . "\n";
    }

    function convert($converter, $row, $columns) {
        // TODO rewrite converters to avoid zip/unzip, or remove conversions in import
        $converted = $converter(zip($row, $columns));
        if ($converted != NULL) {
            return unzip($converted, $columns);
        } else {
            return $converted;
        }
    }

    function zip($row, $columns) {
        // zip([1, 2, 3], ["a", "b", "c"]) -----> ["a" => 1, "b" => 2, "c" => 3]
        $res = [];
        foreach ($row as $index => $value) {
            $key = $columns[$index];
            $res[$key] = $value;
        }
        return $res;
    }

    function unzip($obj, $columns) {
        // unzip(["a" => 1, "b" => 2, "c" => 3], ["a", "b", "c"]) -----> [1, 2, 3]
        $res = [];
        foreach ($columns as $index => $key) {
            $value = $obj[$key];
            $res[] = $value;
        }
        return $res;
    }

    function create_table_prod_stickaz(){
        $sql = 'CREATE TABLE IF NOT EXISTS `ps_product_stickaz` (
          `id_product` int(10) NOT NULL,
          `id_customer` int(10) NOT NULL,
          `json` longtext NOT NULL,
          `kaz_number` mediumint(9) DEFAULT NULL,
          `category` int(4) DEFAULT NULL,
          `type` varchar(32) DEFAULT NULL,
          PRIMARY KEY (`id_product`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        global $db;
        try {
            $res = $db->query($sql);
        } catch (exception $e) {
            echo $e->getMessage();
            $res = false;
        }
        if (!$res) {
            if($db->getNumberError() === 1050){ //already exists
                echo "ps_product_stickaz exists " ."\n";
            }else {
                die("CREATE TABLE ps_product_stickaz failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
            }
        }
    }

    function insert_compact($table_name, $columns, $rows) {
        $keys = [];
        foreach ($columns as $column) {
            $keys[] = '`' . bqSQL($column) . '`';
        }
        $values_stringified = [];
        foreach ($rows as $row_data) {
            $values = [];
            foreach ($row_data as $value) {
                $values[] = (null === $value) ? 'NULL' : "'{$value}'";
            }
            $values_stringified[] = '(' . implode(', ', $values) . ')';
        }
        $keys_stringified = implode(', ', $keys);
        $sql = 'INSERT INTO `' . $table_name . '` (' . $keys_stringified . ') VALUES ' . implode(', ', $values_stringified);
        global $db;
        $res = $db->query($sql);
        if (!$res) {
            print_r($rows);
            if($db->getNumberError() === duplicate_key_error){
                echo "Duplicate key error for " .$table_name ."\n" .$db->getMsgError() ."\n";
            }else {
                die("Insert into " . $table_name . " failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
            }
        }
    }


