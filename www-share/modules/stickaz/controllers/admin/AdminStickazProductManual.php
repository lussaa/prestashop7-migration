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
        $availableColorsForClient = json_encode(array_values($colors));

        $this->context->smarty->assign(
            array(
                'hasUsername' =>false,
                'jsonVars' => array('availableColors' => $availableColorsForClient),
                'availableColors' => $colors,

              'productId' => $productId,
              'product' => $product,
              'currentDesign' => ['name' => $productName, 'json' => $modelJson],
              'productName' => $productName,
              'model' => $model,
              'modelJson' => $modelJson,

            ));

        $this->context->smarty->assign('my_css', $this->my_css);
        $this->context->smarty->assign('my_js', $this->my_js);
    }

    private $my_css = [
        __PS_BASE_URI__ . 'modules/stickaz/assets/css/product-manual.css',
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery.qtip.css',
    ];

    private $my_js = [
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery-1.4.4.min.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/modernizr-1.7.min.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery.qtip.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery.fileupload-ui.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/ext/jquery.json-2.2.min.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/js/studiov2.js',
        __PS_BASE_URI__ . 'modules/stickaz/assets/js/product-manual.js',
    ];

    public static function getAvailableColors($idLang=null)
    {
        $colors = array();
        $idLang = 1;
        $attributes = AttributeGroup::getAttributes($idLang, 1);
        foreach($attributes as $id => $attr)
        {
            $name = explode('-', $attr['name']);
            $colors[trim($name[1])] = array('code' => trim($name[1]), 'name' => trim($name[2]), 'color' => $attr['color'], 'id_attribute' => $attr['id_attribute']);

        }
        return $colors;
    }



}
