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


echo "Importing\n";

$raw_categories = array();
$first_skip = true; // Skip header line with column names
while (($raw_category = fgetcsv(STDIN, 1000, ";")) !== FALSE) {
  if ($first_skip) {
    $first_skip = false;
  } else {
    $raw_categories[] = $raw_category;
  }
}


echo "Downloading images\n";

foreach($raw_categories as $raw_category) {
  $legacy_id = $raw_category[0];
  $new_id = ((int) $legacy_id) + 1;
  $image_url = "https://www.stickaz.com/img/c/" . $legacy_id .".jpg";
  $image_path = "./img/c/" . $new_id . ".jpg";
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
  $legacy_id = $raw_category[0];
  $new_id = ((int) $legacy_id) + 1;
  $c = new Category($new_id);
  $c->force_id = true;
  $c->id_category = $new_id;
  $c->id = $new_id;
  $c->id_image = (int) $new_id;
  $c->id_parent = ((int) $raw_category[1]) + 1;
  $c->name = [ EN => $raw_category[2], FR => $raw_category[6] ];
  $c->description = [ EN => $raw_category[3], FR => $raw_category[7] ];
  $c->link_rewrite = [ EN => $raw_category[4], FR => $raw_category[8] ];
  $c->meta_keywords = [ EN => $raw_category[5], FR => $raw_category[9] ];
  $c->add();

  echo "Inserted category: " . new_id . " - " . $raw_category[2] . "\n"; 
}

echo "Done\n";


?>

