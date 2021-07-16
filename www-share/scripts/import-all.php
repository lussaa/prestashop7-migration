<?php

require_once('./config/config.inc.php');
require_once ('/www-share/scripts/importing_db_tables.php');
require_once ('/www-share/scripts/import-images.php');
require_once ('/www-share/scripts/configure_stuff_on_site.php');

$db = Db::getInstance();
$input = "/www-share/data/model_converted.json";
$json = file_get_contents($input);
$obj = json_decode($json, true);
$tables = $obj['tables'];

echo "Importing...\n";



echo "Importing cookie key (user passwords salt)\n";
$cookiki = $obj['config']['cookie_key'];
$config_file = './app/config/parameters.php';
$config = file_get_contents($config_file);
$current_cookie_key = _COOKIE_KEY_;
$config = str_replace($current_cookie_key, $cookiki, $config);
$res = file_put_contents($config_file, $config);


echo "Setting DB configuration\n";
$stripe_private_key = $obj['config']['stripe_private_key'];
$stripe_public_key = $obj['config']['stripe_public_key'];
$config_items = [
    'MOD_BLOCKTOPMENU_ITEMS' => 'CAT2,CAT5,CAT14,CMS4,CAT13,CAT20',
    'PS_ORDER_OUT_OF_STOCK' => '1',
    'PS_LAST_QTIES' => '0',
    'PS_STOCK_MANAGEMENT' => '0',
    'PS_DISPLAY_QTIES' => '0',
    'PS_DISP_UNAVAILABLE_ATTR' => '1',
    'HOWITWORKS_VIDEO_URl' => '36914205',
    'PS_LOGO' => 'logo-stickaz.png',
    'PS_LOGO_MAIL' => 'logo-stickaz.png',
    'PS_SHOP_NAME' => 'Stickaz',
    'STRIPE_KEY' => $stripe_private_key,
    'STRIPE_PUBLISHABLE' => $stripe_public_key,
];
foreach($config_items as $config_item_name => $config_item_value) {
    $sql = "REPLACE INTO `ps_configuration`(name, value) VALUES('" .$config_item_name ."', '" . $config_item_value . "');";
    $res = $db->query($sql);
    if (!$res) {
        die("Update DB config failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
    }
}

$sql = "UPDATE `ps_image_type` SET width = 367, height = 79  WHERE name = 'category_default';";
$res = $db->query($sql);
if (!$res) {
    die("Update DB image_type failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
}


create_infos_page($db);


// TODO use DB_PREFIX
$to_import = [
  'ps_lang',
  'ps_orders',
  'ps_cart',
  'ps_address',
  'ps_currency',
  'ps_currency_lang',
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
  'ps_category_shop',
  'ps_category_group',
  'ps_category_lang',
  'ps_category_product',
  'ps_attribute_group',
  'ps_attribute_group_lang',
  'ps_attribute',
  'ps_attribute_lang',
  'ps_attribute_impact',
  'ps_product',
  'ps_product_shop',
  'ps_product_lang',
  'ps_product_attribute',
  'ps_product_attribute_combination',
  'ps_product_lang',
  'ps_tag',
  'ps_product_tag',
  'ps_product_stickaz',
  'ps_product_sale',
  'ps_image',
  'ps_image_shop',
  'ps_image_lang',
  'ps_group',
  'ps_group_lang',
  'ps_group_shop',
  'ps_category_group',
  'ps_customer_group',
  'ps_shop',
  'ps_carrier',
  'ps_carrier_zone',
  'ps_carrier_group',
  'ps_carrier_lang',
  'ps_delivery',
  'ps_product_carrier',
  'ps_range_weight',
  'ps_range_price'

];

echo "Creating table ps_product_stickaz....\n";
create_table_prod_stickaz();


import_images($tables['ps_image'], $tables['ps_category']);



foreach($to_import as $t) {
  import_table($t, $tables[$t]);
}

add_special_presta7_shop_tables($tables,'ps_attribute_shop', 'id_attribute', 'ps_attribute' );
add_special_presta7_shop_tables($tables,'ps_attribute_group_shop','id_attribute_group', 'ps_attribute_group');
add_special_presta7_shop_tables($tables,'ps_product_attribute_shop','id_product_attribute', 'ps_product_attribute',
    array("id_product", "price", "ecotax", "wholesale_price", "weight", "unit_price_impact", "minimal_quantity", "default_on"));
add_special_presta7_shop_tables($tables, 'ps_carrier_shop', 'id_carrier', 'ps_carrier');
add_special_presta7_shop_tabl_carrier_tax($tables, 'ps_carrier_tax_rules_group_shop', 'ps_carrier');

insert_croatia_carrier_zone($db);

echo "Done with attributes and combinations. Starting with images. \n";


delete_from_table('ps_module_shop', 'id_module', 25);
// Banner and text block on the home page
delete_from_table('ps_module_shop', 'id_module', 11);
delete_from_table('ps_module_shop', 'id_module', 19);
//

echo "Done\n";

/**
 * @param $db
 */
function create_infos_page($db): void
{
//Info page instead About us:
    $sql = "UPDATE stickaz.ps_cms_lang
SET meta_title='Infos', head_seo_title='Infos', meta_description='About Stickaz', meta_keywords='about us, informations', content='<h1 class=\"page-heading bottom-indent\">About </h1>
<div class=\"row\">
<div class=\"col-xs-12 col-sm-4\">
<div class=\"cms-block\">
<h3 class=\"page-subheading\"></h3>
</div>
</div>
</div>', link_rewrite='infos'
WHERE id_cms=4 AND id_shop=1 AND id_lang=1;
";
    $res = $db->query($sql);
    if (!$res) {
        die("Update DB 'about us' to 'info' failed: " . $db->getMsgError() . "number error:  " . $db->getNumberError() . "\n");
    }
}

function insert_croatia_carrier_zone($db): void{
    $sql = "INSERT INTO stickaz.ps_zone (id_zone, name, active) VALUES(12, 'Croatia', 1);";
    $res = $db->query($sql);
    if (!$res) {
        error_log("Insert CROATIA carrier_zone failed: " . $db->getMsgError() ."number error:  " .$db->getNumberError(). "\n");
    }
}

function fix_carrier_data(): void{

    // remove pick up at stickay-store

    //
}

?>

