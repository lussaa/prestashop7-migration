<?php

chdir('/var/www/html/');
require_once './config/config.inc.php';

$db = Db::getInstance();
$pid = $_GET['id_product'];
if (!is_numeric($pid)) {
    $pid = "0";
}

$res = $db->executeS('SELECT `json` FROM `'._DB_PREFIX_.'product_stickaz` WHERE `id_product` = '. $pid);
if ($res) {
    $model_json = $res[0]['json'];
    $model = json_decode($model_json, true);
} else {
    echo "Product " . $pid . " not found\n";
}

$res = $db->executeS('SELECT * FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '. $pid . ' AND id_lang=1');
if ($res) {
    $product_lang = $res[0];
    $product_name = $product_lang['name'];
} else {
    echo "Product " . $pid . " not found\n";
}


function getAvailableColors($idLang=1)
{
    $colors = array();
    $attributes = AttributeGroup::getAttributes($idLang, 1);
    foreach($attributes as $id => $attr)
    {
        $name = explode('-', $attr['name']);
        $colors[trim($name[1])] = array('code' => trim($name[1]), 'name' => trim($name[2]), 'color' => $attr['color'], 'id_attribute' => $attr['id_attribute']);
    }
    return $colors;
}

$availableColors = getAvailableColors();
$availableColorsJson = json_encode(array_values($availableColors));

?>

<html>
<head>
  <link rel="stylesheet" href="css/product-manual.css">
  <link rel="stylesheet" href="css/ext/jquery.qtip.css">
  <script src="js/ext/jquery-1.4.4.min.js"></script>
  <script src="js/ext/jquery.qtip.js"></script>
  <script src="js/ext/jquery.fileupload-ui.js"></script>
  <script src="js/ext/jquery.json-2.2.min.js"></script>
  <script>
    var Modernizr = { canvas: true };
    <?php
        echo "var availableColors = " . $availableColorsJson . ";\n";
    ?>
  </script>
  <script src="js/studiov2.js"></script>
  <script src="js/product-manual.js"></script>
  <script>
      $(document).ready(function() {

          <?php
        echo "currentData = '" . $model_json . "';\n";
        echo "isUserLogged = false;\n";
    ?>


        //Manual2.size = 2;
        Manual2.orderSize = 1.5;
        Manual2.init(availableColors);
        Manual2.adjustGrid();
        Manual2.autoCrop();
        Manual2.findCenterPixel();
        Manual2.setInfo();
        Manual2.shippingHelpInfo();
    });
  </script>
</head>
<body>
<h1><?php echo $product_name; ?></h1>

<div id="border">
    <div id="border-marge">
        <div id="studio-draw">

            <div id="studio-wrapper">
                <div id="canvas_wrapper"></div>
            </div>

        </div>

        <div id="studio-header">
            <div id="palette-colors">
                <ul id="color-list">
                    <?php
                        foreach ($model['palette'] as $key => $color) {
                            if ($color['q'] > 1) {
                                $c = $color['c'];
                                $bg = $c == '#F8F9FB' ? '#999' : $c;
                                echo sprintf(
                                    '<li id="%s">
                                        <div class="color" id="%s" style="background-color: %s; border: 1px solid %s"></div>
                                        <span style="color: #3A3A3A">%d</span>
                                    </li>',
                                    $key, $c, $bg, $c, $color['q']);
                            }
                        }
                    ?>
                </ul>
            </div>
        </div>


        <h1>Kaz quantities</h1>

        <div id="studio-header3"> <!--shippingHelpInfo-->
            <div id="palette-colors">
                <table id="color-list">
                    <tr id="header">
                        <th class="color" id="{$color.color}">Color name</th>
                        <th class="colorcode">&nbsp;&nbsp;&nbsp;Color code</th>
                        <th class="colorcount">&nbsp;&nbsp;&nbsp;Broj bez 10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Paketa od 9 sa10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Dodatno kaz[9]</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Paketa od 4sa10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Dodatno kaz[4]</th>
                    </tr>

                    <?php
                        foreach ($availableColors as $color) {
                            echo '
                        <tr id="c'. $color['code'] .'" class="colorline">
                            <td class="color" id="'. $color['color'] .'">'. $color['name'] .'</td>
                            <td class="colorcode"></td>
                            <td class="colorcount"></td>
                            <td class="packagescount9"></td>
                            <td class="extrakaz9"></td>
                            <td class="packagescount4"></td>
                            <td class="extrakaz4"></td>
                        </tr>
                        ';
                        }
                    ?>
                </table>

            </div>
        </div   >


    </div>
</div>

</body>
</html>
