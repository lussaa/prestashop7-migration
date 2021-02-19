<?php
require_once('./config/config.inc.php');

echo "testing products\n";


$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json);
$raw_products = $obj-> products;

//foreach ($raw_products as $raw_product){
//    $id_product = $raw_product->id_product;
//    $image_source = "/www-share/data/img/c/" . $legacy_id .".jpg";
//    $image_dest = "./img/c/" . $new_id . ".jpg";
//    @copy($image_source, $image_dest);
//}


$id_product = 1514;
$url="/www-share/data/img/c/11.jpg";
$image = new Image();
$image->id_product = $id_product;
$image->position = 1;
$image->cover = false; // or false;

class MyAdminImportController extends AdminImportControllerCore
{
    public function __construct()
    {}

    public static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        return parent::copyImg($id_entity, $id_image , $url, $entity, $regenerate );


    }
}




if (($image->validateFields(true, true)) === true &&
($image->validateFieldsLang(true, true)) === true && $image->add())
{
$icontroller = new MyAdminImportController;

if ($icontroller::copyImg($id_product, $image->id, $url, 'products', true))
{
echo "Ok";
}else{
echo "fallido";
$image->delete();
}
}else {
echo "falled";

}

?>