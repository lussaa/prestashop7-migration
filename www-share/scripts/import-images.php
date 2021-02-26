<?php

require_once('./config/config.inc.php');


echo "Importing\n";

$input = "/www-share/data/model_converted.json";
$json = file_get_contents($input);
$obj = json_decode($json);

echo "Importing category images\n";

$raw_categories = $obj->tables->ps_category;
foreach($raw_categories as $raw_category) {
  $id_category = $raw_category->id_category;
  $image_source = "/www-share/data/img/c/" . $id_category .".jpg";
  $image_dest = "./img/c/" . $id_category . ".jpg";
  @copy($image_source, $image_dest);
}

echo "Importing product images\n";

class MyAdminImportController extends AdminImportControllerCore
{
    public function __construct()
    {
    }

    public static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        $res = parent::copyImg($id_entity, $id_image, $url, $entity, $regenerate);
        if (!$res) {
          echo "Copy image failed: " . $id_entity . " / " . $id_image . "\n";
        }

    }
}

const IMG_PATH = "/www-share/data/img/p/";

$raw_images = $obj->tables->ps_image;
foreach($raw_images as $raw_image) {
  $iid = $raw_image->id_image;
  $pid = $raw_image->id_product;
  $url = IMG_PATH . $pid . "-" . $iid . ".png";
  MyAdminImportController::copyImg($pid, $iid, $url, 'products', true);
}

echo "Regenerating thumbnails\n";

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

$ic = new MyAdminImagesController;
$ic->regenerateThumbnails();


echo "Done\n";


?>

