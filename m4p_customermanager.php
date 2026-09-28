<?php

declare(strict_types=1);

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

use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class M4p_CustomerManager extends Module
{
    public const FORM_FIELD = 'm4p_manager_id';
    public const FORM_NOTE = 'm4p_manager_note';

    public function __construct()
    {
        $this->name = 'm4p_customermanager';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Account manager', [], 'Modules.M4pcustomermanager.Admin');
        $this->description = $this->trans('Assigns an employee to a customer, shows their contact details on the account and lets the two write to each other.', [], 'Modules.M4pcustomermanager.Admin');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('actionCustomerFormBuilderModifier')
            && $this->registerHook('actionAfterCreateCustomerFormHandler')
            && $this->registerHook('actionAfterUpdateCustomerFormHandler')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayNav2')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionCustomerGridDefinitionModifier')
            && $this->registerHook('actionCustomerGridQueryBuilderModifier')
            && $this->registerHook('displayAdminCustomers')
            && $this->installTab();
    }

    public function uninstall(): bool
    {
        $this->uninstallTab();

        return $this->uninstallDb() && parent::uninstall();
    }

    protected function installTab(): bool
    {
        $tab = new Tab();
        $tab->class_name = 'AdminM4pCustomerMessage';
        $tab->module = $this->name;
        $tab->id_parent = -1;
        $tab->active = true;
        $tab->name = [];

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'Account manager messages';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab(): bool
    {
        $idTab = (int) Tab::getIdFromClassName('AdminM4pCustomerMessage');
        if (!$idTab) {
            return true;
        }

        return (bool) (new Tab($idTab))->delete();
    }

    protected function installDb(): bool
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4p_customer_manager` (
                `id_customer` INT UNSIGNED NOT NULL,
                `id_employee` INT UNSIGNED NOT NULL,
                `note` TEXT NULL DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_customer`),
                KEY `id_employee` (`id_employee`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4p_employee_phone` (
                `id_employee` INT UNSIGNED NOT NULL,
                `phone` VARCHAR(64) NOT NULL DEFAULT \'\',
                PRIMARY KEY (`id_employee`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4p_customer_message` (
                `id_message` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_customer` INT UNSIGNED NOT NULL,
                `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,
                `from_customer` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `message` TEXT NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_message`),
                KEY `conversation` (`id_customer`, `date_add`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;',
        ];

        foreach ($queries as $sql) {
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallDb(): bool
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4p_customer_manager`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4p_employee_phone`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4p_customer_message`');

        return true;
    }

    /* ---------------------------------------------------------------------
     * Admin: "Account manager" field in the customer edit form (Symfony)
     * ------------------------------------------------------------------- */

    public function hookActionCustomerFormBuilderModifier(array $params): void
    {
        /** @var \Symfony\Component\Form\FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        $idCustomer = (int) ($params['id'] ?? 0);

        $choices = ['— ' . $this->trans('no account manager', [], 'Modules.M4pcustomermanager.Admin') . ' —' => 0];
        foreach ($this->getActiveEmployees() as $emp) {
            $label = trim($emp['firstname'] . ' ' . $emp['lastname']) . ' (' . $emp['email'] . ')';
            $choices[$label] = (int) $emp['id_employee'];
        }

        $formBuilder->add(self::FORM_FIELD, ChoiceType::class, [
            'label' => $this->trans('Account manager', [], 'Modules.M4pcustomermanager.Admin'),
            'required' => false,
            'choices' => $choices,
            'placeholder' => false,
            'attr' => ['class' => 'm4p-manager-select'],
            'help' => $this->trans('The employee looking after this customer. Their phone number is set in the module configuration.', [], 'Modules.M4pcustomermanager.Admin'),
        ]);

        $formBuilder->add(self::FORM_NOTE, TextareaType::class, [
            'label' => $this->trans('Note for the customer', [], 'Modules.M4pcustomermanager.Admin'),
            'required' => false,
            'attr' => ['rows' => 3],
            'help' => $this->trans('Shown to the customer on their account, under the account manager.', [], 'Modules.M4pcustomermanager.Admin'),
        ]);

        $params['data'][self::FORM_FIELD] = $idCustomer ? $this->getEmployeeIdForCustomer($idCustomer) : 0;
        $params['data'][self::FORM_NOTE] = $idCustomer ? $this->getNoteForCustomer($idCustomer) : '';
        $formBuilder->setData($params['data']);
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params): void
    {
        $this->persistManager($params);
    }

    public function hookActionAfterUpdateCustomerFormHandler(array $params): void
    {
        $this->persistManager($params);
    }

    private function persistManager(array $params): void
    {
        $idCustomer = (int) ($params['id'] ?? 0);
        if (!$idCustomer) {
            return;
        }

        $formData = $params['form_data'] ?? [];
        $idEmployee = isset($formData[self::FORM_FIELD]) ? (int) $formData[self::FORM_FIELD] : 0;
        $note = trim((string) ($formData[self::FORM_NOTE] ?? ''));

        if ($idEmployee <= 0 && $note === '') {
            Db::getInstance()->delete('m4p_customer_manager', 'id_customer = ' . $idCustomer);

            return;
        }

        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'm4p_customer_manager` (`id_customer`, `id_employee`, `note`, `date_add`, `date_upd`)
            VALUES (' . $idCustomer . ', ' . $idEmployee . ', "' . pSQL($note) . '", NOW(), NOW())
            ON DUPLICATE KEY UPDATE `id_employee` = ' . $idEmployee . ', `note` = "' . pSQL($note) . '", `date_upd` = NOW()'
        );
    }

    /* ---------------------------------------------------------------------
     * Customer list (grid): "Account manager" column + name filter
     * ------------------------------------------------------------------- */

    public function hookActionCustomerGridDefinitionModifier(array $params): void
    {
        if (empty($params['definition'])) {
            return;
        }

        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition $definition */
        $definition = $params['definition'];

        $definition->getColumns()->addAfter(
            'email',
            (new DataColumn('m4p_manager'))
                ->setName($this->trans('Account manager', [], 'Modules.M4pcustomermanager.Admin'))
                ->setOptions(['field' => 'm4p_manager_name', 'sortable' => true])
        );

        $definition->getFilters()->add(
            (new Filter('m4p_manager', TextType::class))
                ->setAssociatedColumn('m4p_manager')
                ->setTypeOptions([
                    'required' => false,
                    'attr' => ['placeholder' => $this->trans('Search by manager', [], 'Modules.M4pcustomermanager.Admin')],
                ])
        );
    }

    public function hookActionCustomerGridQueryBuilderModifier(array $params): void
    {
        if (empty($params['search_query_builder']) || empty($params['search_criteria'])) {
            return;
        }

        /** @var \Doctrine\DBAL\Query\QueryBuilder $searchQb */
        $searchQb = $params['search_query_builder'];
        $criteria = $params['search_criteria'];
        $prefix = _DB_PREFIX_;
        $nameExpr = "CONCAT(me.firstname, ' ', me.lastname)";

        // Manager name column (1:1 relation, no row duplication).
        $searchQb->addSelect($nameExpr . ' AS m4p_manager_name');
        $this->joinManager($searchQb, $prefix);

        // Filter by manager name (on both search + count, so pagination stays correct).
        $filters = $criteria->getFilters();
        if (!empty($filters['m4p_manager'])) {
            $searchQb->andWhere($nameExpr . ' LIKE :m4p_manager');
            $searchQb->setParameter('m4p_manager', '%' . $filters['m4p_manager'] . '%');

            if (!empty($params['count_query_builder'])) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $countQb */
                $countQb = $params['count_query_builder'];
                $this->joinManager($countQb, $prefix);
                $countQb->andWhere($nameExpr . ' LIKE :m4p_manager');
                $countQb->setParameter('m4p_manager', '%' . $filters['m4p_manager'] . '%');
            }
        }

        // Sort by the manager column.
        if (in_array($criteria->getOrderBy(), ['m4p_manager', 'm4p_manager_name'], true)) {
            $searchQb->orderBy('m4p_manager_name', $criteria->getOrderWay() ?: 'ASC');
        }
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $qb
     */
    private function joinManager($qb, string $prefix): void
    {
        $qb->leftJoin('c', $prefix . 'm4p_customer_manager', 'mcm', 'mcm.id_customer = c.id_customer');
        $qb->leftJoin('mcm', $prefix . 'employee', 'me', 'me.id_employee = mcm.id_employee');
    }

    /* ---------------------------------------------------------------------
     * Front: account manager info block on the customer account page
     * ------------------------------------------------------------------- */

    public function hookActionFrontControllerSetMedia(): void
    {
        $this->context->controller->registerStylesheet(
            'm4p-customermanager',
            'modules/' . $this->name . '/views/css/front.css'
        );
    }

    public function hookDisplayCustomerAccount(array $params): string
    {
        $idCustomer = (int) ($this->context->customer->id ?? 0);
        if (!$idCustomer) {
            return '';
        }

        $manager = $this->getManagerForCustomer($idCustomer);
        if (!$manager) {
            return '';
        }

        $this->context->smarty->assign([
            'm4p_manager' => $manager,
            'm4p_note' => $this->getNoteForCustomer($idCustomer),
            'm4p_messages' => $this->getMessages($idCustomer),
            'm4p_message_action' => $this->context->link->getModuleLink($this->name, 'message', [], true),
            'm4p_token' => Tools::getToken(false),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/account_manager.tpl');
    }

    /**
     * Compact account-manager bar in the top menu (logged-in customers only).
     */
    public function hookDisplayNav2(array $params): string
    {
        $idCustomer = (int) ($this->context->customer->id ?? 0);
        if (!$idCustomer) {
            return '';
        }

        $manager = $this->getManagerForCustomer($idCustomer);
        if (!$manager) {
            return '';
        }

        $this->context->smarty->assign(['m4p_manager' => $manager]);

        return $this->display(__FILE__, 'views/templates/hook/account_manager_nav.tpl');
    }

    public function getNoteForCustomer(int $idCustomer): string
    {
        return (string) Db::getInstance()->getValue(
            'SELECT `note` FROM `' . _DB_PREFIX_ . 'm4p_customer_manager` WHERE `id_customer` = ' . $idCustomer
        );
    }

    /* ---------------------------------------------------------------------
     * Messages between the account manager and the customer
     * ------------------------------------------------------------------- */

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(int $idCustomer): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT m.*, e.`firstname`, e.`lastname`
            FROM `' . _DB_PREFIX_ . 'm4p_customer_message` m
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON e.`id_employee` = m.`id_employee`
            WHERE m.`id_customer` = ' . $idCustomer . '
            ORDER BY m.`date_add` ASC, m.`id_message` ASC'
        );

        $messages = [];
        foreach ($rows ?: [] as $row) {
            $messages[] = [
                'id_message' => (int) $row['id_message'],
                'from_customer' => (bool) $row['from_customer'],
                'author' => $row['from_customer']
                    ? ''
                    : trim((string) $row['firstname'] . ' ' . (string) $row['lastname']),
                'message' => $row['message'],
                'date_add' => $row['date_add'],
            ];
        }

        return $messages;
    }

    public function addMessage(int $idCustomer, int $idEmployee, string $message, bool $fromCustomer): bool
    {
        $message = trim($message);
        if ($idCustomer <= 0 || $message === '') {
            return false;
        }

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'm4p_customer_message`
            (`id_customer`, `id_employee`, `from_customer`, `message`, `date_add`)
            VALUES (' . $idCustomer . ', ' . $idEmployee . ', ' . (int) $fromCustomer . ', "'
            . pSQL(Tools::substr($message, 0, 5000)) . '", NOW())'
        );
    }

    public function deleteMessage(int $idMessage, int $idCustomer): bool
    {
        return (bool) Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'm4p_customer_message`
            WHERE `id_message` = ' . $idMessage . ' AND `id_customer` = ' . $idCustomer
        );
    }

    /**
     * The conversation on the customer page in the back office. Posting goes
     * through the module's admin controller, behind the employee session.
     */
    public function hookDisplayAdminCustomers(array $params): string
    {
        $idCustomer = (int) ($params['id_customer'] ?? 0);
        if (!$idCustomer) {
            return '';
        }

        $this->context->smarty->assign([
            'm4p_id_customer' => $idCustomer,
            'm4p_messages' => $this->getMessages($idCustomer),
            'm4p_action' => $this->context->link->getAdminLink('AdminM4pCustomerMessage'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/customer_messages.tpl');
    }

    /* ---------------------------------------------------------------------
     * Konfiguracja: telefony opiekunow (pracownikow)
     * ------------------------------------------------------------------- */

    public function getContent(): string
    {
        $output = '';
        $employees = $this->getActiveEmployees();

        if (Tools::isSubmit('submitM4pManagerPhones')) {
            foreach ($employees as $emp) {
                $id = (int) $emp['id_employee'];
                $phone = trim((string) Tools::getValue('m4p_phone_' . $id));
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'm4p_employee_phone` (`id_employee`, `phone`)
                    VALUES (' . $id . ', \'' . pSQL($phone) . '\')
                    ON DUPLICATE KEY UPDATE `phone` = \'' . pSQL($phone) . '\''
                );
            }
            $output .= $this->displayConfirmation($this->trans('Phone numbers saved.', [], 'Modules.M4pcustomermanager.Admin'));
        }

        return $output . $this->renderPhonesForm($employees);
    }

    private function renderPhonesForm(array $employees): string
    {
        $phones = $this->getEmployeePhones();

        $inputs = [];
        $values = [];
        foreach ($employees as $emp) {
            $id = (int) $emp['id_employee'];
            $name = 'm4p_phone_' . $id;
            $inputs[] = [
                'type' => 'text',
                'label' => trim($emp['firstname'] . ' ' . $emp['lastname']) . ' (' . $emp['email'] . ')',
                'name' => $name,
                'class' => 'fixed-width-xl',
            ];
            $values[$name] = $phones[$id] ?? '';
        }

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->trans('Account manager phone numbers', [], 'Modules.M4pcustomermanager.Admin'), 'icon' => 'icon-phone'],
                'description' => $this->trans('PrestaShop has no phone field for employees, so it is set here and shown on the account of the customer they look after.', [], 'Modules.M4pcustomermanager.Admin'),
                'input' => $inputs,
                'submit' => ['title' => $this->trans('Save', [], 'Modules.M4pcustomermanager.Admin'), 'name' => 'submitM4pManagerPhones'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitM4pManagerPhones';
        $helper->fields_value = $values;

        return $helper->generateForm([$fields_form]);
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------- */

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveEmployees(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_employee`, `firstname`, `lastname`, `email`
            FROM `' . _DB_PREFIX_ . 'employee`
            WHERE `active` = 1
            ORDER BY `lastname` ASC, `firstname` ASC'
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, string> [id_employee => phone]
     */
    private function getEmployeePhones(): array
    {
        $rows = Db::getInstance()->executeS('SELECT `id_employee`, `phone` FROM `' . _DB_PREFIX_ . 'm4p_employee_phone`');
        $map = [];
        foreach ((array) $rows as $r) {
            $map[(int) $r['id_employee']] = (string) $r['phone'];
        }

        return $map;
    }

    private function getEmployeeIdForCustomer(int $idCustomer): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_employee` FROM `' . _DB_PREFIX_ . 'm4p_customer_manager` WHERE `id_customer` = ' . $idCustomer
        );
    }

    /**
     * @return array<string, string>|null
     */
    private function getManagerForCustomer(int $idCustomer): ?array
    {
        $row = Db::getInstance()->getRow(
            'SELECT e.`firstname`, e.`lastname`, e.`email`, COALESCE(p.`phone`, \'\') AS phone
            FROM `' . _DB_PREFIX_ . 'm4p_customer_manager` cm
            INNER JOIN `' . _DB_PREFIX_ . 'employee` e ON e.`id_employee` = cm.`id_employee`
            LEFT JOIN `' . _DB_PREFIX_ . 'm4p_employee_phone` p ON p.`id_employee` = cm.`id_employee`
            WHERE cm.`id_customer` = ' . $idCustomer . ' AND e.`active` = 1'
        );

        if (!$row || empty($row['email'])) {
            return null;
        }

        return [
            'firstname' => (string) $row['firstname'],
            'lastname' => (string) $row['lastname'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
        ];
    }
}
