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


$home = new Category();
$home->force_id = true;
$home->id_category = 2;
$home->id = 2;
$home->id_parent = 1;
$home->name = [ EN => "Home" ];
$home->link_rewrite = [ EN => "home" ];
$home->add();



echo "Importing\n";


$row = 1;
  while (($data = fgetcsv(STDIN, 1000, ";")) !== FALSE) {
    $num = count($data);
  //echo "$num fields in line $row:\n";
  $row++;
  for ($c=0; $c < $num; $c++) {
      //echo "    " . $data[$c] . "\n";
  }
  $c = new Category();
  $c->force_id = true;
  $c->id_category = $data[0];
  $c->id = $data[0];
  $c->id_image = (int) $data[0];
  $c->id_parent = (int) $data[1];
  $c->name = [ EN => $data[2], FR => $data[6] ];
  $c->description = [ EN => $data[3], FR => $data[7] ];
  $c->link_rewrite = [ EN => $data[4], FR => $data[8] ];
  $c->meta_keywords = [ EN => $data[5], FR => $data[9] ];
  $c->add();

  echo "Inserted category: " . $c->id . " - " . $data[2] . " (parent " . $data[1] . ")\n"; 
}

echo "Done\n";


?>

