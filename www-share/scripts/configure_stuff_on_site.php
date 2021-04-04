<?php


function delete_from_table($table, $attribute_name, $id_attribute) {


    $sql = 'DELETE from `' . $table . '` WHERE ' .$attribute_name .'=' .$id_attribute .';' ;

    global $db;
    $res = $db->query($sql);
    if (!$res) {
        print_r("For key:" .$id_attribute);
        die("Insert into " . $table . " failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");

    }
}

