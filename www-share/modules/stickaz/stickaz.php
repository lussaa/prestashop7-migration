<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

//use \AdminStickazOrderManualController;

class Stickaz extends Module
{
    public function __construct()
    {
        $this->name = 'stickaz';
        $this->tab = 'stickaz';
        $this->version = '0.0.1';
        $this->author = 'Fabulous Team';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Stickaz module');
        $this->description = $this->l('The Stickaz Module');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    public function install()
    {
        return parent::install() &&
            $this->registerHooks() &&
            $this->makeControllersAvailable();
    }

    private function registerHooks() {
        return
            $this->registerHook('actionGetAdminOrderButtons');// &&
//             $this->registerHook('moduleRoutes');
    }

    public function uninstall()
    {
        return $this->removeTabs() && parent::uninstall();
    }

    public function hookActionGetAdminOrderButtons(array $params)
    {
        //$order = new Order($params['id_order']);
        //$router = $this->get('router');
        $url_params = ['id_order' => $params['id_order']];
        $viewManualUrl = $this->context->link->getAdminLink('AdminStickazOrderManual', true, [], $url_params);
        $bar = $params['actions_bar_buttons_collection'];
        $bar->add(
            new \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton(
                'btn-info', ['href' => $viewManualUrl], 'View manual'
            )
        );
    }

    private function makeControllersAvailable()
    {
        // Custom controllers are not available if not implemented as a "tab" (even an invisible one)
        return $this->addHiddenTab('StickazAdminTab', 'AdminStickazOrderManual') &&
            $this->addHiddenTab('StickazAdminTab2', 'AdminStickazProductManual');
    }


    private function addHiddenTab($tabName, $controllerClassName)
    {
        $tab = new Tab();
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']]  = $tabName;
        }
        $tab->class_name = $controllerClassName;
        $tab->id_parent = -1; // tab will not be visible in menu
        $tab->module = $this->name;
        $tab->active = 1;
        return $tab->add();
    }

    private function removeTabs() {
        return $this->removeTab('AdminStickazOrderManual') &&
            $this->removeTab('AdminStickazProductManual');
    }

    private function removeTab($controllerClassName) {
        $tabId = (int) Tab::getIdFromClassName($controllerClassName);
        if (!$tabId) {
            return true;
        }
        $tab = new Tab($tabId);
        return $tab->delete();
    }


}