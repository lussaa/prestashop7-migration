<?php

require_once('./config/config.inc.php');


echo "Deleting all users\n";

$res = Db::getInstance()->executeS('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` ');

if ($res) {
	foreach ($res as $row) {
		$x = new Customer($row['id_customer']);
		$x->delete();
	}
}

echo "Importing\n";

$input = "/www-share/data/model.json";
$json = file_get_contents($input);
$obj = json_decode($json);
$users = $obj->users;
$cookiki = $obj->config->cookie_key;

const EN = 1;

echo "Importing cookie key (user passwords salt)\n";

$config_file = './app/config/parameters.php';
$config = file_get_contents($config_file);
$current_cookie_key = _COOKIE_KEY_;
$config = str_replace($current_cookie_key, $cookiki, $config);
file_put_contents($config, $config_file);


echo "Creating users\n";
$count = 0;
foreach($users as $user) {
  $c = new Customer($user->id_customer);
  $c->force_id = true;
  $c->id_customer = $user->id_customer;
  $c->id = $user->id_customer;
  $c->email = $user->email;
  $c->firstname = $user->firstname;
  $c->lastname = $user->lastname;
  $c->passwd = $user->passwd;
  $c->username = $user->username;
  $c->id_lang = EN;
  $c->newsletter = $user->newsletter;
  $c->id_gender = $user->id_gender;
  $c->add();

  $count++;
}
echo "Inserted " . $count . " users\n";


echo "Done\n";


?>

