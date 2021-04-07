<?php

require_once ('/www-share/scripts/importing_db_tables.php');
require_once('./config/config.inc.php');

const IMG_PATH = "/www-share/data/img/p/";


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

class MyAdminImagesController extends AdminImagesControllerCore
{
  public function __construct()
  {
  }

	public function regenerateThumbnails($type = 'all', $deleteOldImages = false)
	{
    $res = parent::_regenerateThumbnails($type, $deleteOldImages);
    if (!$res) {
      foreach($this->errors as $err) {
        echo $err . "\n";
      }
    }
  }

  protected function trans($id, array $parameters = [], $domain = null, $locale = null) {
    return strtr($id, $parameters);
  }

}

function import_images($ps_image, $ps_category){
    echo "Importing product images\n";
    import_product_images($ps_image);
    echo "Importing category images\n";
    import_category_images($ps_category);
    //regenerate_thumbnails();
}

function regenerate_thumbnails() {
    $ic = new MyAdminImagesController;
    $ic->regenerateThumbnails();
}

function import_category_images($ps_category) {
    $columns = $ps_category['columns'];
    $rows = $ps_category['rows'];
    foreach ($rows as $row) {
        $row_reverted = zip($row, $columns);
        $id_category = $row_reverted['id_category'];
        $image_source = "/www-share/data/img/c/" . $id_category .".jpg";
        //$image_dest = "./img/c/" . $id_category . ".jpg";
        //@copy($image_source, $image_dest);
        if (MyAdminImportController::copyImg($id_category, null, $image_source, 'categories', false)) {
                //echo "Ok for cat id -> " .$id_category .".\n";
        } else {
            echo " # copy image failed for cat id -> " .$id_category .".\n";
        }
    }
}

function import_product_images($ps_image){
    $columns = $ps_image['columns'];
    $rows = $ps_image['rows'];
    foreach ($rows as $row) {

        $row_reverted = zip($row, $columns);
        $id_product = $row_reverted['id_product'];
        $image_id = $row_reverted['id_image'];
        $url = IMG_PATH  .$id_product ."-" .$image_id .".png";
        if (MyAdminImportController::copyImg($id_product, $image_id, $url, 'products', true)) {
                //echo "Ok for img id -> " .$image_id .".\n";
        } else {
            echo " # copy image failed for img id -> " .$image_id .".\n";
        }
    }
}
?>

