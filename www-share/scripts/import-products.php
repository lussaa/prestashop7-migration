<?php


require_once('./config/config.inc.php');

echo "Deleting all products\n";

$res = Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` ');

if ($res) {
    foreach ($res as $row) {
        $x = new Product($row['id_product']);
        $x->delete();
    }
}

const EN = 1;
const FR = 2;
const IMG_PATH = "/www-share/data/img/p/";

echo "Importing\n";


$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json);
$raw_products = $obj-> products;

class MyAdminImportController extends AdminImportControllerCore
{
    public function __construct()
    {
    }

    public static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        return parent::copyImg($id_entity, $id_image, $url, $entity, $regenerate);


    }
}


foreach ($raw_products as $raw_product) {

    // Product creation
    $p = rawproductToProduct($raw_product);
    $id_product = $raw_product->id_product;

    $p->add();
    $p->addToCategories($raw_product->id_category_default);

    echo "Inserted product: " . $p->id_product . "\n";

    // Image addition

    $imageid_list = $raw_product->image_ids;

    foreach ($imageid_list as $image_id) {
        $index = array_search($image_id, $imageid_list);
        $image = new Image();

        if ($index === 1) {
            $image->cover = true;

        } else {
            $image->cover = false;
        }

        $url = IMG_PATH  .$id_product ."-" .$image_id .".png";
        $image->id_product = $id_product;
        $image->position = $index;


        if (($image->validateFields(true, true)) === true &&
            ($image->validateFieldsLang(true, true)) === true && $image->add()) {
            $icontroller = new MyAdminImportController;

            if ($icontroller::copyImg($id_product, $image->id, $url, 'products', true)) {
                echo "Ok for img id -> " .$image_id .".\n";
            } else {
                echo " #################### copy image failed for img id -> " .$image_id .".\n";
                $image->delete();
            }
        } else {
            echo " #################### Image check failed for img id -> " .$image_id .".\n";

        }

    }



}

echo "Done\n";


function rawproductToProduct($raw_product){

    $name = (array)($raw_product->name);
    $name_en = $name[EN];

    $p = new Product($raw_product->id_product);
    $p->force_id = true;
    $p->id = $raw_product->id_product;
    $p->id_product = $raw_product->id_product;
    $p->id_category_default = $raw_product->id_category_default;
    $p->name = (array)($raw_product->name);
    $p->meta_description = (array)($raw_product->meta_description);
    $p->link_rewrite = (array)($raw_product->link_rewrite);
    $p->meta_keywords = (array)($raw_product->meta_keywords);
    $p->price_tin = $raw_product->price;
    $p->price = $raw_product->price;
    
    return $p;

}


?>

