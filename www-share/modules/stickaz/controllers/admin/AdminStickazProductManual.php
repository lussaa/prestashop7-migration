<?php


class AdminStickazProductManualController extends ModuleAdminController
{

    public function display()
    {
            $this->context->smarty->display(_PS_MODULE_DIR_ . 'stickaz/templates/admin/product_manual.tpl');
    }


    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $productId = Tools::getValue('id_product'); // Retrieved from GET vars
        $product = new Product($productId);
        $langId = $this->context->language->id;
        $productName = $product->name[$langId];

        $db = Db::getInstance();
        if (!is_numeric($productId)) {
            echo "Expected parameter (numeric) id_product\n";
            exit;
        }
        $res = $db->executeS('SELECT `json` FROM `'._DB_PREFIX_.'product_stickaz` WHERE `id_product` = '. $productId);
        if ($res) {
            $modelJson = $res[0]['json'];
            $model = json_decode($modelJson, true);
        } else {
            echo "Product " . $productId . " not found\n";
            exit;
        }

        $colors = self::getAvailableColors();
        $availableColorsJson = json_encode(array_values($colors));
        $usedColors = self::getUsedColors($colors, $model);

        $this->context->smarty->assign(
            array(
                'jsonVars' => array(
                    'availableColors' => $availableColorsJson,
                    'model' => $modelJson,
                ),
                'availableColors' => $colors,
                'usedColors' => $usedColors,
                'shippingColors' => self::getShippingInfo($usedColors),
                'productId' => $productId,
                'product' => $product,
                'productName' => $productName,
            ));

        $this->context->smarty->assign('my_css', $this->my_css);
        $this->context->smarty->assign('my_js', $this->my_js);
    }

    private $my_css = [
        __PS_BASE_URI__ . 'modules/stickaz/assets/css/product-manual.css',
    ];

    private $my_js = [
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery-1.4.4.min.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/js/studio_simple.js',
    ];

    public static function getAvailableColors($idLang=null)
    {
        $colors = array();
        $idLangEnglish = 1;
        $idAttributeGroupColor = 1;
        $attributes = AttributeGroup::getAttributes($idLangEnglish, $idAttributeGroupColor);
        foreach($attributes as $id => $attr)
        {
            $name_items = explode('-', $attr['name']);
            $code = trim($name_items[1]);
            $name = trim($name_items[2]);
            $colors[$code] = array('code' => $code, 'name' => $name, 'color' => $attr['color'], 'id_attribute' => $attr['id_attribute']);
        }
        return $colors;
    }


    private static function getUsedColors($colors, $model) {
        $usedColors = [];
        foreach($colors as $color) {
            $quantityUsed = self::getQuantity($color, $model);
            if ($quantityUsed > 0) {
                $usedColors[] = [
                    'code' => $color['code'],
                    'name' => $color['name'],
                    'color' => $color['color'],
                    'quantity' => $quantityUsed,
                ];
            }
        }
        return $usedColors;
    }

    private static function getQuantity($color, $model) {
        $count = 0;
        foreach ($model['canvas'] as $row) {
            foreach ($row as $cell) {
                if ($cell === 'c' . $color['code']) {
                    $count += 1;
                }
            }
        }
        return $count;
    }

    private static function getShippingInfo($usedColors) {
        $info = [];
        foreach ($usedColors as $usedColor) {
            $info[] = [
                'code' => $usedColor['code'],
                'name' => $usedColor['name'],
                'color' => $usedColor['color'],
                'counts' => self::getShippingCounts($usedColor['quantity']),
            ];
        }
        return $info;
    }

    private static function getShippingCounts($usedQuantity) {
        $packagescount9 = ($usedQuantity*1.1/9);         //1.1 is to get plus extra 10%
        $extrakaz9=fmod($packagescount9,1)*9;
        $packagescount4 = ($usedQuantity*1.1/4);
        $extrakaz4=fmod($packagescount4,1)*4;

        return [
            'bez10' => $usedQuantity,
            'pak9sa10' => round($packagescount9),
            'dodatnokaz9' => round($extrakaz9),
            'pak4sa10' => round($packagescount4),
            'dodatnokaz4' => $extrakaz4,
        ];
    }

}
