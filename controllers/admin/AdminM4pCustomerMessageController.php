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
 * Posts and removes messages from the customer page. Being an admin controller,
 * it runs behind the employee session and the back office token.
 */
class AdminM4pCustomerMessageController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        $idCustomer = (int) Tools::getValue('id_customer');

        if (!Tools::isSubmit('submitM4pCustomerMessage') && !Tools::isSubmit('deleteM4pCustomerMessage')) {
            return parent::postProcess();
        }

        if (!$this->access('edit')) {
            $this->errors[] = $this->trans('You do not have permission to edit this.', [], 'Admin.Notifications.Error');

            return false;
        }

        if (!Validate::isLoadedObject(new Customer($idCustomer))) {
            $this->errors[] = $this->trans('Unknown customer.', [], 'Modules.M4pcustomermanager.Admin');

            return false;
        }

        if (Tools::isSubmit('deleteM4pCustomerMessage')) {
            $this->module->deleteMessage((int) Tools::getValue('id_message'), $idCustomer);
        } else {
            $message = (string) Tools::getValue('m4p_message');
            if (trim($message) === '') {
                $this->errors[] = $this->trans('Write something before sending.', [], 'Modules.M4pcustomermanager.Admin');

                return false;
            }

            $this->module->addMessage($idCustomer, (int) $this->context->employee->id, $message, false);
        }

        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminCustomers', true, [], ['id_customer' => $idCustomer, 'viewcustomer' => 1, 'conf' => 4])
        );
    }
}
