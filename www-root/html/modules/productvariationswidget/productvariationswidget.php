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

//    public function setMedia() {
//        parent::setMedia();
//        //$this->context->controller->addJS($this->_path . 'view/js/productvariationswidget.js');
//
//        $this->context->controller->registerJavascript(
//            'productvariationswidget-js',
//            'modules/productvariationswidget/views/js/productvariationswidget.js',
//            [
//                'priority' => 200,
//            ]
//        );
//    }

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

       // $this->registerJavascript($this->_path.'views/js/productvariationswidget.js', ['position' => 'bottom', 'priority' => 0]);

        return $this->display(__FILE__, 'views/templates/widget/variationtable.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration){
        $notifications = false;
         return [
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

//    /**
//     *  * “Configure” link appears with addition of the getContent() method to your main class. This is a standard PrestaShop method:
//     */
//    public function getContent()
//    {
//
//        $output = null;
//
//        $output .= $this->displayConfirmation($this->l('Settings updated'));
//
//        return $output.$this->renderForm();
//    }
//
//
//    public function displayForm()
//    {
//        // Get default language
//        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
//
//        // Init Fields form array
//        $fieldsForm[0]['form'] = [
//            'legend' => [
//                'title' => $this->l('Settings'),
//            ],
//            'input' => [
//                [
//                    'type' => 'text',
//                    'label' => $this->l('Configuration value'),
//                    'name' => 'MYMODULE_NAME',
//                    'size' => 20,
//                    'required' => true
//                ]
//            ],
//            'submit' => [
//                'title' => $this->l('Save'),
//                'class' => 'btn btn-default pull-right'
//            ]
//        ];
//
//        $helper = new HelperForm();
//
//        // Module, token and currentIndex
//        $helper->module = $this;
//        $helper->name_controller = $this->name;
//        $helper->token = Tools::getAdminTokenLite('AdminModules');
//        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
//
//        // Language
//        $helper->default_form_language = $defaultLang;
//        $helper->allow_employee_form_lang = $defaultLang;
//
//        // Title and toolbar
//        $helper->title = $this->displayName;
//        $helper->show_toolbar = true;        // false -> remove toolbar
//        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
//        $helper->submit_action = 'submit'.$this->name;
//        $helper->toolbar_btn = [
//            'save' => [
//                'desc' => $this->l('Save'),
//                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
//                    '&token='.Tools::getAdminTokenLite('AdminModules'),
//            ],
//            'back' => [
//                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
//                'desc' => $this->l('Back to list')
//            ]
//        ];
//
//        // Load current value
//        $helper->fields_value['MYMODULE_NAME'] = Tools::getValue('MYMODULE_NAME', Configuration::get('MYMODULE_NAME'));
//
//        return $helper->generateForm($fieldsForm);
//    }
//
//
//    /**
//     * Save form data.
//     */
//    protected function postProcess()
//    {
//        $form_values = $this->getConfigFormValues();
//
//        foreach (array_keys($form_values) as $key) {
//            Configuration::updateValue($key, Tools::getValue($key));
//        }
//    }
//
//    public function hookDisplayLeftColumnProduct()
//    {
//        $this->context->smarty->assign([
//            'my_module_name' => Configuration::get('MYMODULE_NAME'),
//            'my_module_link' => $this->context->link->getModuleLink('myProductVariations', 'display')
//        ]);
//
//        return $this->display(__FILE__, 'myProductVariations.tpl');
//    }
//
//    public function hookActionFrontControllerSetMedia()
//    {
//        $this->context->controller->registerStylesheet(
//            'myProductVariations-style',
//            $this->_path.'views/css/front.css',
//            [
//                'media' => 'all',
//                'priority' => 1000,
//            ]
//        );
//
//        $this->context->controller->registerJavascript(
//            'myProductVariations-javascript',
//            $this->_path.'views/js/front.js',
//            [
//                'position' => 'bottom',
//                'priority' => 1000,
//            ]
//        );
//    }
//
//
//    public function hookDisplayProductActions($params)
//    {
//        $this->context->smarty->assign(
//            [
//                'my_module_name' => Configuration::get('MYMODULE_NAME'),
//                'my_module_link' => $this->context->link->getModuleLink('myProductVariations', 'display'),
//                'my_module_message' => $this->l('This is a simple text message') // Do not forget to enclose your strings in the l() translation method
//            ]
//        );
//
//        return $this->display(__FILE__, 'myProductVariations.tpl');
//    }
//
//    public function hookDisplayProductButtons($params)
//    {
//        $this->context->smarty->assign(
//            [
//                'my_module_name' => Configuration::get('MYMODULE_NAME'),
//                'my_module_link' => $this->context->link->getModuleLink('myProductVariations', 'display'),
//                'my_module_message' => $this->l('This is a simple text message') // Do not forget to enclose your strings in the l() translation method
//            ]
//        );
//
//        return $this->display(__FILE__, 'myProductVariations.tpl');
//    }
//
//    /**
//     * @return $this
//     */
//    protected function createNewToken()
//    {
//        $this->context->cookie->contactFormToken = md5(uniqid());
//        $this->context->cookie->contactFormTokenTTL = time()+600;
//
//        return $this;
//    }
}
