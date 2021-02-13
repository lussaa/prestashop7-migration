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

$input = "/www-share/data/categories.json";
$json = file_get_contents($input);
$obj = json_decode($json);
$raw_categories = $obj->categories;

echo "Downloading images\n";

foreach($raw_categories as $raw_category) {
  $legacy_id = $raw_category->id_category;
  $new_id = ((int) $legacy_id) + 1;
  $image_source = "/www-share/data/img/c/" . $legacy_id .".jpg";
  $image_dest = "./img/c/" . $new_id . ".jpg";
  @copy($image_source, $image_dest);
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
  $legacy_id = $raw_category->id_category;
  $new_id = ((int) $legacy_id) + 1;
  $c = new Category($new_id);
  $c->force_id = true;
  $c->id_category = $new_id;
  $c->id = $new_id;
  $c->id_image = (int) $new_id;
  $c->id_parent = ((int) $raw_category->id_parent) + 1;
  $c->name = [ EN => $raw_category->name_en, FR => $raw_category->name_fr ];
  $c->description = [ EN => $raw_category->description_en, FR => $raw_category->description_fr ];
  $c->link_rewrite = [ EN => $raw_category->link_rewrite_en, FR => $raw_category->link_rewrite_fr ];
  $c->meta_keywords = [ EN => $raw_category->meta_keywords_en, FR => $raw_category->meta_keywords_fr ];
  $c->add();

  echo "Inserted category: " . $new_id . " - " . $raw_category->name_en . "\n"; 
}

echo "Done\n";


?>

