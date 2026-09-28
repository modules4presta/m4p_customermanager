<?php

/**
 * m4p_customermanager
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Lets a signed-in customer reply to their account manager. The customer id is
 * taken from the session, never from the request, so one account cannot write
 * in another's conversation.
 */
class M4p_CustomerManagerMessageModuleFrontController extends ModuleFrontController
{
    public $auth = true;

    public function postProcess()
    {
        if (!Tools::isSubmit('submitM4pCustomerMessage')) {
            return;
        }

        if (!hash_equals(Tools::getToken(false), (string) Tools::getValue('token'))) {
            $this->errors[] = $this->trans('Invalid security token.', [], 'Modules.M4pcustomermanager.Shop');

            return;
        }

        $idCustomer = (int) $this->context->customer->id;
        $message = (string) Tools::getValue('m4p_message');

        if (trim($message) === '') {
            $this->errors[] = $this->trans('Write something before sending.', [], 'Modules.M4pcustomermanager.Shop');

            return;
        }

        $idEmployee = (int) Db::getInstance()->getValue(
            'SELECT `id_employee` FROM `' . _DB_PREFIX_ . 'm4p_customer_manager` WHERE `id_customer` = ' . $idCustomer
        );

        $this->module->addMessage($idCustomer, $idEmployee, $message, true);

        Tools::redirect($this->context->link->getPageLink('my-account'));
    }

    public function initContent()
    {
        parent::initContent();

        Tools::redirect($this->context->link->getPageLink('my-account'));
    }
}
