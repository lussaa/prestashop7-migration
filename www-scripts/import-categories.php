<?php

require_once('./config/config.inc.php');

echo "Deleting all categories\n";

$res = Db::getInstance()->executeS('SELECT `id_category` FROM `'._DB_PREFIX_.'category` ');

if ($res) {
	foreach ($res as $row) {
		$x = new Category($row['id_category']);
		$x->delete();
	}
}

const EN = 1;
const FR = 2;

// Recreate category 1/Home. Only 0/Root was kept after deleting "all"
$home = new Category();
$home->force_id = true;
$home->id_category = 2;
$home->id = 2;
$home->id_parent = 1;
$home->name = [ EN => "Home" ];
$home->link_rewrite = [ EN => "home" ];
$home->add();



echo "Importing\n";

$raw_categories = array();
while (($raw_category = fgetcsv(STDIN, 1000, ";")) !== FALSE) {
    $raw_categories[] = $raw_category;
}


echo "Downloading images\n";

foreach($raw_categories as $raw_category) {
  $id = $raw_category[0];
  $legacy_id = ((int) $id) - 1;
  $image_url = "https://www.stickaz.com/img/c/" . $legacy_id .".jpg";
  $image_path = "./img/c/" . $id . ".jpg";
  $image = @file_get_contents($image_url);
  if ($image !== FALSE) {
    file_put_contents($image_path, $image, LOCK_EX);
  }
}


echo "Regenerating thumbnails\n";

class MyAdminImagesController extends AdminImagesControllerCore
{
  public function __construct()
  {}

	public function regenerateThumbnails($type = 'all', $deleteOldImages = false)
	{
			return parent::_regenerateThumbnails($type, $deleteOldImages);
	}
}
$ic = new MyAdminImagesController;
$ic->regenerateThumbnails();


echo "Creating categories\n";
foreach($raw_categories as $raw_category) {
  $id = $raw_category[0];
  $c = new Category($id);
  $c->force_id = true;
  $c->id_category = $id;
  $c->id = $id;
  $c->id_image = (int) $id;
  $c->id_parent = (int) $raw_category[1];
  $c->name = [ EN => $raw_category[2], FR => $raw_category[6] ];
  $c->description = [ EN => $raw_category[3], FR => $raw_category[7] ];
  $c->link_rewrite = [ EN => $raw_category[4], FR => $raw_category[8] ];
  $c->meta_keywords = [ EN => $raw_category[5], FR => $raw_category[9] ];
  $c->add();

  echo "Inserted category: " . $c->id . " - " . $raw_category[2] . " (parent " . $raw_category[1] . ")\n"; 
}

echo "Done\n";


?>

