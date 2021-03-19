<?php

chdir('/var/www/html/');
require_once './config/config.inc.php';

$db = Db::getInstance();
$oid = $_GET['id_order'];
if (!is_numeric($oid)) {
    echo "Expected parameter (numeric) id_order\n";
    exit;
}

$o = new Order($oid);

$a = new Address($o->id_address_delivery);

echo "<h1>Order " . $oid . "</h1>\n";

echo "<h2>Products</h2> <table id=\"products\"><tr><th>Name</th><th>Quantity</th></tr>\n";
foreach ($o->getProductsDetail() as $pd) {
    echo "<tr><th><a href=\"product-manual.php?id_product=" . $pd['product_id'] . "\">" . $pd['product_name'] . "</a></th><th>" . $pd['product_quantity'] . "</th></tr>\n";
}
echo "</table>\n";

$address_delivery = $a;
$deliveryState = null;
?>

<h2>Address</h2>
<ul class="address alternate_item">
	<li class="address_title">Delivery</li>
	<?php if ($address_delivery->company) {?>
	    <li class="address_company"><?php echo $address_delivery->company ?></li>
    <?php } ?>
	<li class="address_name">
	    <?php echo $address_delivery->firstname ?> <?php echo $address_delivery->lastname ?>
    </li>
	<li class="address_address1"><?php echo $address_delivery->address1 ?></li>
	<?php if ($address_delivery->address2) {?>
	    <li class="address_address2"><?php echo $address_delivery->address2 ?></li>
    <?php } ?>
	<li class="address_city"><?php echo $address_delivery->postcode ?> <?php echo $address_delivery->city?></li>
	<li class="address_country">
	    <?php echo $address_delivery->country ?>
	    <?php if ($deliveryState) { echo " - " . $deliveryState->name; }?>
    </li>
	<?php if ($address_delivery->phone) {?>
	    <li class="address_phone"><?php echo $address_delivery->phone ?></li>
    <?php } ?>
	<?php if ($address_delivery->phone_mobile) {?>
	    <li class="address_phone_mobile"><?php echo $address_delivery->phone_mobile ?></li>
    <?php } ?>
</ul>
