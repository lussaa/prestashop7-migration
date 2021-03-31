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

        $this->context->smarty->assign(
            array(
              'orderId' => $orderId,
              'order' => $order,
              'orderLang' => $order->id_lang,
              'address_invoice' => $addressInvoice,
              'invoiceState' => $invoiceState,
              'address_delivery' => $addressDelivery,
              'deliveryState' => $deliveryState,
              'productDetails' => $order->getProductsDetail(),
              'link' => $this->context->link,
            ));
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
    }

}
