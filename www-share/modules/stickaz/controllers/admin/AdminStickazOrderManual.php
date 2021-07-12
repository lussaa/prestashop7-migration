<?php


class AdminStickazOrderManualController extends ModuleAdminController
{

    public function display()
    {
        $this->context->smarty->display(_PS_MODULE_DIR_ . 'stickaz/templates/admin/order_manual.tpl');
    }


    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $orderId = Tools::getValue('id_order'); // Retrieved from GET vars
        $order = new Order($orderId);

        $addressInvoice = new Address((int)($order->id_address_invoice));
        $addressDelivery = new Address((int)($order->id_address_delivery));
        $invoiceState = (Validate::isLoadedObject($addressInvoice) AND $addressInvoice->id_state) ? new State((int)($addressInvoice->id_state)) : false;
        $deliveryState = (Validate::isLoadedObject($addressDelivery) AND $addressDelivery->id_state) ? new State((int)($addressDelivery->id_state)) : false;

        $pds = $order->getProductsDetail();
        $new_pds = [];
        foreach ($pds as $pd) {
            $new_pds[] = [
                'product_id' => $pd['product_id'],
                'product_name' => $pd['product_name'],
                'product_quantity' => $pd['product_quantity'],
                'shippingColors' => self::getShippingColors($pd['product_id']),
            ];
        }

        $this->context->smarty->assign(
            array(
              'orderId' => $orderId,
              'order' => $order,
              'orderLang' => $order->id_lang,
              'address_invoice' => $addressInvoice,
              'invoiceState' => $invoiceState,
              'address_delivery' => $addressDelivery,
              'deliveryState' => $deliveryState,
              'productDetails' => $new_pds,
              'link' => $this->context->link,

            ));

        $this->context->smarty->assign('my_css', $this->my_css);
        $this->context->smarty->assign('my_js', $this->my_js);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
    }

    private $my_css = [
        __PS_BASE_URI__ . 'modules/stickaz/assets/css/order-manual.css',
    ];

    private $my_js = [
    ];

    private function getShippingColors($productId) {
        $db = Db::getInstance();
        $res = $db->executeS('SELECT `json` FROM `'._DB_PREFIX_.'product_stickaz` WHERE `id_product` = '. $productId);
        if ($res) {
            $modelJson = $res[0]['json'];
            $model = json_decode($modelJson, true);
        } else {
            echo "Product " . $productId . " not found\n";
            exit;
        }
        $colors = self::getAvailableColors();
        $usedColors = self::getUsedColors($colors, $model);
        return self::getShippingColors2($usedColors);
    }


    private static function getShippingColors2($usedColors) {
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


}
