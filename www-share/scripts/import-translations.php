<?php

require_once('./config/config.inc.php');

$db = Db::getInstance();

echo "Adding missing translations...\n";

require_once ('/www-share/scripts/missing_translations.php');

foreach($missing_translations as $t) {
    # Hardcoding 1 for english
    $sql = "REPLACE INTO `ps_translation`(id_lang, `key`, translation, domain) VALUES(1, '" . $t['id'] . "', '" . $t['id'] . "', '" . $t['domain'] . "');";
    $res = $db->query($sql);
    if (!$res) {
        die("Update translation failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
    }
}


echo "Done\n";

?>

