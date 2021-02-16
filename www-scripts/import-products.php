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


echo "Importing\n";

$map_images_urls = array();
$raw_products = array();

while (($raw_product = fgetcsv(STDIN, 1000, ";")) !== FALSE) {

    if (!array_key_exists($raw_product[0],$map_images_urls)){
        $raw_products[] = $raw_product;
        $image_urls = array();
        $id_product = $raw_product[0];
        echo "################################################ ID PRODUCT IS $id_product \n";
        array_push ($image_urls, $raw_product[12]);
        $map_images_urls[$id_product] = $image_urls;
    }
    else {
        $image_urls = $map_images_urls[$id_product];
        array_push ($image_urls, $raw_product[12]);
        $map_images_urls[$id_product] = $image_urls;
    }

}

echo "Downloading images\n";

foreach($raw_products as $raw_product) {
    $id = $raw_product[0];
    $id_image = $raw_product[12];
    //$legacy_id = ((int) $id) - 1;
    $image_url = "https://www.stickaz.com/img/p/" . $id ."-" .$id_image .".png";
    $image_path = "./img/p/" . $id ."-" .$id_image .".png";

    $image = @file_get_contents($image_url);
    echo "downloaded: " . $id ."-" .$id_image .".png" ."\n";

    if ($image !== FALSE) {
        echo "putting file" .$id_image ."to : " .$image_path ."\n";
        file_put_contents($image_path, $image, LOCK_EX);
        echo "done.";
    }
}


echo "Creating products\n";


foreach($raw_products as $raw_product) {

    $p->add();

    echo "Inserted product: " . $p->id . " - " . $raw_product[0] . " (name " . $raw_product[2] . ")\n";
}

echo "Done\n";


function rawproductToProduct($raw_product){
    $id = $raw_product[0];
    $p = new Product($id);
    $p->force_id = true;
    $p->id_product = $id;
    //$p->id = $id;
    $p->id_image = (int) $raw_product[12];
    $p->id_parent = (int) $raw_product[11];
    $p->id_category_default = $raw_product[10];
    $p->name = [ EN => $raw_product[2], FR => $raw_product[3] ];
    $p->description = [ EN => $raw_product[4], FR => $raw_product[5] ];
    $p->link_rewrite = [ EN => $raw_product[8], FR => $raw_product[9] ];
    $p->meta_keywords = [ EN => $raw_product[6], FR => $raw_product[7] ];
    return $p;

}


?>

