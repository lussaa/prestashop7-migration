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


class HowItWorks extends Module implements WidgetInterface
{
    protected $config_form = false;


    public function __construct()
    {
        $this->name = 'howitworks';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Creative Glass';
        $this->need_instance = 0;
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('HowItWorks_Stickaz');
        $this->description = $this->l('Displaying info of stickaz');

    }


    public function isUsingNewTranslationSystem()
    {
        return true;
    }


    public function hookActionFrontControllerSetMedia()
    {

        $this->context->controller->registerJavascript(
            'howitworks-js',
            'modules/howitworks/views/js/howitworks.js',
            [
                'priority' => 200,
                'position' => 'bottom',

            ]
        );
        $this->context->controller->registerStylesheet(
            'howitworks-css',
            'modules/howitworks/views/css/howitworks.css',
            [
                'priority' => 201,
                'media' => 'all',

            ]
        );
    }

    public function renderWidget($hookName, array $configuration){
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        $this->context->controller->addJs($this->_path.'views/js/howitworks.js');


        return $this->display(__FILE__, 'views/templates/widget/howitworks_container.tpl');
    }



    public function getWidgetVariables($hookName, array $configuration){
        $notifications = false;
        $videoId = Configuration::get('HOWITWORKS_VIDEO_URL');
        $videoCode = '<iframe src="//player.vimeo.com/video/'.$videoId.'?portrait=0&amp;color=00b3cb" width="393" height="221" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

        return [
            'video_code' => $videoCode,
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
        Configuration::updateValue('MYMODULE_NAME', 'howitworks');

        return parent::install()  &&  $this->registerHook('actionFrontControllerSetMedia') ;

    }

    public function uninstall()
    {
        Configuration::deleteByName('MYMODULE_NAME');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }


}
