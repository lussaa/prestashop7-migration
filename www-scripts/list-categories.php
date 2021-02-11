<?php

require_once('./config/config.inc.php');
$res = Db::getInstance()->executeS('SELECT `id_category`,name FROM `'._DB_PREFIX_.'category_lang` ');

print_r($res);

