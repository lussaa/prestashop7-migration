<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;


class ProductVariationsWidget extends Module implements WidgetInterface
{
    protected $config_form = false;


    public function __construct()
    {
        $this->name = 'productvariationswidget';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Creative Glass';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('productvariationswidget');
        $this->description = $this->l('Displaying product variations');


    }




    public function isUsingNewTranslationSystem()
    {
        return true;
    }


    public function hookActionFrontControllerSetMedia()
    {

        $this->context->controller->registerJavascript(
            'productvariationswidget-js',
            'modules/productvariationswidget/views/js/productvariationswidget.js',
            [
                'priority' => 200,
                'position' => 'bottom',

            ]
        );
        $this->context->controller->registerStylesheet(
            'productvariationswidget-css',
            'modules/productvariationswidget/views/css/productvariationswidget.css',
            [
                'priority' => 201,
                'media' => 'all',

            ]
        );
    }

    public function renderWidget($hookName, array $configuration){
        if (!$this->active) {
            return;
        }
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        $this->context->controller->addJs($this->_path.'views/js/productvariationswidget.js');


        function console_log( $data ){
            echo '<script>';
            echo 'console.log('. json_encode( $data ) .')';
            echo '</script>';
        }

//        console_log ("Groups: ");
//        console_log ("Groups: ");
//        console_log ("Groups: ");
//        console_log ("Groups: ");
//        console_log ("Groups: ");
//        console_log ("Groups: ");
//        console_log( get_defined_vars());

       // $this->registerJavascript($this->_path.'views/js/productvariationswidget.js', ['position' => 'bottom', 'priority' => 0]);

        return $this->display(__FILE__, 'views/templates/widget/variationtable.tpl');
    }



    public function getWidgetVariables($hookName, array $configuration){
        $notifications = false;
        $array_size_ids = [];
        $product = $this->context->controller->getProduct(); // TODO check and fail gracefully if not a product controller
        foreach ($product->getAttributesGroups($this->context->language->id) as &$group){
            $id_attribute_group = $group['id_attribute_group'];
            if ($id_attribute_group == 2) {  //2==size
                if (!in_array($group['attribute_name'], $array_size_ids))
                    $array_size_ids[$group['id_attribute']] = $group['attribute_name'];
            }

        }

        $product_dimensions = [];
        foreach ($array_size_ids as $size_id => $kazSize){
            $default_kaz_size = 1.5;
            $width = ($product->width/$default_kaz_size) * $kazSize;
            $height = ($product->height/$default_kaz_size) * $kazSize;
            $product_dimensions[$size_id] =  $width ." x " .$height ." cm" ;
        };
        return [
            'product_width' => $product->width + 100,
            'product_height' => $product->height + 100,
            'product_dimensions' => $product_dimensions,
        ];
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        Configuration::updateValue('MYMODULE_NAME', 'productvariationswidget');

        return parent::install()  &&  $this->registerHook('actionFrontControllerSetMedia') ;

    }

    public function uninstall()
    {
        Configuration::deleteByName('MYMODULE_NAME');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }


}
