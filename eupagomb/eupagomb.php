<?php
/**
 * 2007-2015 PrestaShop
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
 * @author    euPago, Instituição de Pagamento Lda <suporte@eupago.pt>
 * @copyright 2016 euPago, Instituição de Pagamento Lda
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EupagoMB extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->module_key = 'bcfcaaf905b30348b5f3a66365b59e78';
        $this->name = 'eupagomb';
        $this->tab = 'payments_gateways';
        $this->version = '1.8.8';
        $this->author = 'euPago';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        $this->currencies = true;

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('euPago - Multibanco');
        $this->description = $this->l('Allows your customers to pay with Multibanco entity and reference.');

        $this->confirmUninstall = $this->l('Are you sure about removing the euPago Multibanco module?');

        $this->limited_currencies = array('EUR');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->context->link->getModuleLink($this->name, 'display');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $this->createStates();

        $this->copyFilesEmails();

        include dirname(__FILE__) . '/sql/install.php';

        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('paymentOptions') &&
        $this->registerHook('paymentReturn') &&
        $this->registerHook('displayOrderDetail') &&
        $this->registerHook('displayAdminOrder') &&
        $this->registerHook('displayPayment') &&
        $this->registerHook('displayPaymentReturn');
    }

    public function createStates()
    {
        $this->order_state = array(
            array('ffff00', '10110', 'euPago - A aguardar pagamento por multibanco', '', 0),
            array('00ffff', '01110', 'euPago - Confirmado pagamento por multibanco', 'payment', 1),
        );
        /** OBTER LISTA DOS IDIOMAS  * */
        $languages = Db::getInstance()->ExecuteS(
            '
        SELECT `id_lang`, `iso_code`
        FROM `' . _DB_PREFIX_ . 'lang`
        '
        );
        foreach ($this->order_state as $key => $value) {
            /** CRIANDO OS STATUS NA TABELA order_state * */
            Db::getInstance()->Execute(
                '
                INSERT INTO `' . _DB_PREFIX_ . 'order_state`
            ( `invoice`, `send_email`, `color`, `unremovable`, `logable`, `delivery`, `module_name`)
                VALUES
            (0, ' . $value[4] . ', \'#' . $value[0] . '\', 0, 1, 0,\'eupagomb\');
            '
            );
            /** /CRIANDO OS STATUS NA TABELA order_state * */
            $this->figura = Db::getInstance()->Insert_ID();

            foreach ($languages as $language_atual) {
                Db::getInstance()->Execute(
                    '
                    INSERT INTO `' . _DB_PREFIX_ . 'order_state_lang`
                (`id_order_state`,`id_lang`,`name`,`template`)
                    VALUES
                (' . $this->figura . ', ' .
                    $language_atual['id_lang'] .
                    ', \'' . $value[2] . '\', \'' .
                    $value[3] . '\');
                '
                );
            }
            Configuration::updateValue("EUPAGO_MULTIBANCO_ESTADO_$key", $this->figura);
        }
        return true;
    }

    /**
     * Copy files emails templates
     */
    public function copyFilesEmails()
    {
        copy(
            dirname(__FILE__) . '/mails/en/payment_data.txt',
            dirname(__FILE__) . '/../../mails/en/payment_data.txt'
        ) && copy(
            dirname(__FILE__) . '/mails/en/payment_data.html',
            dirname(__FILE__) . '/../../mails/en/payment_data.html'
        ) && copy(
            dirname(__FILE__) . '/mails/en/payment_data_limite.txt',
            dirname(__FILE__) . '/../../mails/en/payment_data_limite.txt'
        ) && copy(
            dirname(__FILE__) . '/mails/en/payment_data_limite.html',
            dirname(__FILE__) . '/../../mails/en/payment_data_limite.html'
        ) && copy(
            dirname(__FILE__) . '/mails/pt/payment_data.txt',
            dirname(__FILE__) . '/../../mails/pt/payment_data.txt'
        ) && copy(
            dirname(__FILE__) . '/mails/pt/payment_data.html',
            dirname(__FILE__) . '/../../mails/pt/payment_data.html'
        ) && copy(
            dirname(__FILE__) . '/mails/pt/payment_data_limite.txt',
            dirname(__FILE__) . '/../../mails/pt/payment_data_limite.txt'
        ) && copy(
            dirname(__FILE__) . '/mails/pt/payment_data_limite.html',
            dirname(__FILE__) . '/../../mails/pt/payment_data_limite.html'
        );
    }

    public function uninstall()
    {
        Configuration::deleteByName('eupago_multibanco');
        //Configuration::deleteByName('EUPAGO_MULTIBANCO_LIVE_MODE');

        $this->deleteFilesEmails();

        include dirname(__FILE__) . '/sql/uninstall.php';

        return parent::uninstall();
    }

    /**
     * Delete files emails templates when uninstall module
     */
    public function deleteFilesEmails()
    {
        unlink(dirname(__FILE__) . '/../../mails/en/payment_data.txt') &&
        unlink(dirname(__FILE__) . '/../../mails/en/payment_data.html') &&
        unlink(dirname(__FILE__) . '/../../mails/en/payment_data_limite.txt') &&
        unlink(dirname(__FILE__) . '/../../mails/en/payment_data_limite.html') &&
        unlink(dirname(__FILE__) . '/../../mails/pt/payment_data.txt') &&
        unlink(dirname(__FILE__) . '/../../mails/pt/payment_data.html') &&
        unlink(dirname(__FILE__) . '/../../mails/pt/payment_data_limite.txt') &&
        unlink(dirname(__FILE__) . '/../../mails/pt/payment_data_limite.html');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $notification = null;
        if (((bool) Tools::isSubmit('submitEupago_multibancoModule')) == true) {
            $chave = Tools::getValue('EUPAGO_MULTIBANCO_CHAVEAPI');

            if (!$chave ||
                empty($chave) ||
                !Validate::isGenericName($chave)
            ) {
                $notification = false;
            } else {
                $notification = true;
                $this->postProcess();
            }
        }
        $this->context->smarty->assign('module_dir', $this->_path);

        if (isset($notification)) {
            $output = $notification ?
            $this->displayConfirmation(
                $this->l('Settings updated')
            ) :
            $this->displayError(
                $this->l('Invalid Configuration value')
            );
            return $output . $this->renderForm();
        } else {
            return $this->renderForm();
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        $form_api = $this->getApi();
        foreach (array_keys($form_api) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        // HACK PARA VALIDAR NUMERO DE DIAS
        foreach (array_keys($form_values) as $key) {
            if ($key == 'EUPAGO_MULTIBANCO_DL_DIAS') {
                if ((filter_var(Tools::getValue($key), FILTER_VALIDATE_INT) ||
                    Tools::getValue($key) == '0') && Tools::getValue($key) >= 0) {
                    Configuration::updateValue($key, Tools::getValue($key));
                } else {
                    return false;
                }
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EUPAGO_MULTIBANCO_ESTADO_0' => Configuration::get('EUPAGO_MULTIBANCO_ESTADO_0', null),
            'EUPAGO_MULTIBANCO_ESTADO_1' => Configuration::get('EUPAGO_MULTIBANCO_ESTADO_1', null),

            'EUPAGO_MULTIBANCO_CHAVEAPI' => Configuration::get('EUPAGO_MULTIBANCO_CHAVEAPI', null),
            'EUPAGO_MULTIBANCO_TIPO_DL' => Configuration::get('EUPAGO_MULTIBANCO_TIPO_DL', null),
            'EUPAGO_MULTIBANCO_DL_DIAS' => Configuration::get('EUPAGO_MULTIBANCO_DL_DIAS', null),
            'EUPAGO_MULTIBANCO_SHOW_DL' => Configuration::get('EUPAGO_MULTIBANCO_SHOW_DL', null),
            'EUPAGO_MULTIBANCO_TIPO_SD' => Configuration::get('EUPAGO_MULTIBANCO_TIPO_SD', null),

        );
    }

    /**
     * Create the structure of your form.
     */
    protected function getApi()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l(' euPago API Key'),
                    'icon' => 'icon-key',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l(
                            'This key is provided by euPago if you don´t have it please contact us - www.eupago.pt'
                        ),
                        'name' => 'EUPAGO_MULTIBANCO_CHAVEAPI',
                        'label' => $this->l('Api key'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEupago_multibancoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getApi(), $this->getConfigForm())) .
        $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $lang = Configuration::get('PS_LANG_DEFAULT', null);
        $sql = 'SELECT * FROM ' .
        _DB_PREFIX_ . 'order_state_lang,' .
        _DB_PREFIX_ . 'order_state WHERE ' .
        _DB_PREFIX_ . 'order_state_lang.id_order_state=' .
        pSQL(_DB_PREFIX_ . 'order_state.id_order_state') . ' and ' .
        _DB_PREFIX_ . 'order_state.deleted=' . (int) 0 . ' and ' .
        _DB_PREFIX_ . 'order_state_lang.id_lang=' . (int) $lang;
        $estados = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Multibanco Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(

                        'type' => 'select',
                        'label' => $this->l('State of Awaiting payment:'),
                        'name' => 'EUPAGO_MULTIBANCO_ESTADO_0',

                        'required' => false,
                        'options' => array(
                            'query' => $estados,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('State of Success payment:'),
                        'name' => 'EUPAGO_MULTIBANCO_ESTADO_1',
                        'required' => false,

                        'options' => array(
                            'query' => $estados,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Payment Deadline'),
                        'name' => 'EUPAGO_MULTIBANCO_TIPO_DL',
                        'class' => 'payment_deadline',
                        'is_bool' => true,
                        'desc' => $this->l('Use payment deadline in your multibanco references'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'col' => 7,
                        'type' => 'text',
                        'class' => 'ndias',
                        'prefix' => '<i class="icon icon-calendar"></i>',
                        'desc' => $this->l(
                            'If you have payment deadline enable,
                            please specify here the number of days that the reference
                            is valid for payment (Select 0 if you want to allow payment only in
                            same day that the reference is provided)'
                        ),
                        'name' => 'EUPAGO_MULTIBANCO_DL_DIAS',
                        'label' => $this->l('Number of days for payment deadline'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show Payment Deadline'),
                        'name' => 'EUPAGO_MULTIBANCO_SHOW_DL',
                        'class' => 'show_payment_deadline',
                        'is_bool' => true,
                        'desc' => $this->l('Show the payment deadline when checkout'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),

                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Duplicate payments'),
                        'name' => 'EUPAGO_MULTIBANCO_TIPO_SD',
                        'class' => 'duplicate_payments',
                        'is_bool' => true,
                        'desc' => $this->l('Allow pay the same reference several times'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * This method is used to render the payment button in version 1.7,
     * Take care if the button should be displayed or not.
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign('module_dir', $this->_path);

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $formAction = $this->context->link->getModuleLink($this->name, 'confirmation', array(), true);
        $this->smarty->assign(['action' => $formAction]);

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo_payment.png'))
            ->setCallToActionText("Multibanco")
            ->setAction(
                $this->context->link->getModuleLink(
                    $this->name,
                    'confirmation',
                    array(
                        'cart_id' => Context::getContext()->cart->id,
                        'secure_key' => Context::getContext()->customer->secure_key,
                    ),
                    true
                )
            );

        $payment_options = array(
            $newOption,
        );

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        //print_r($params);
        //die();
        if ($this->active == false) {
            return;
        }

        $order = (_PS_VERSION_ >= '1.7' ? $params['order'] : $params['objOrder']);

        $exist = $this->getOrderIdObjectFromEupagoTable($order->id);
        //print_r($order->id);
        //die();

        if ($exist) {
            $result = (object) $exist[0];
            $result->estado = 0;
        } else {
            $result->estado = 1;
        }

        $mostrar_data = Configuration::get('EUPAGO_MULTIBANCO_SHOW_DL');

        if ($result->estado != 0) {
            $history = new OrderHistory();
            $history->id_order = (int) $order->id;
            $erro = "Erro: " . $result->resposta;
            $history->changeIdOrderState((int) Configuration::get('PS_OS_ERROR'), (int) ($order->id));
            $this->smarty->assign('status', 'Nok');
            $this->smarty->assign('erro', $erro);
        } else {
            $this->smarty->assign('status', 'ok');
            $this->smarty->assign('entidade', $result->entidade);
            $this->smarty->assign('referencia', $result->referencia);
            if ($mostrar_data && isset($result->dataLimite) && $result->dataLimite != "2099-12-31") {
                $this->smarty->assign('dataLimite', $result->dataLimite);
            }
            if ((
                (int) Configuration::get('EUPAGO_MULTIBANCO_ESTADO_1') !== (int) $order->current_state
            ) && $result->estadoRef == "pendente") {
                $this->sendEmailPaymentDetails($order, $result);
            }
        }

        $this->smarty->assign(
            array(
                'id_order' => $order->id,
                'reference' => $order->reference,
                'params' => $params,
                'total' => Tools::displayPrice($order->total_paid, null, false),
                'module_dir' => $this->_path,
            )
        );

        unset($_SESSION);
        Tools::clearSmartyCache();
        return (_PS_VERSION_ >= '1.7' ? $this->fetch(
            'module:' . $this->name . '/views/templates/hook/confirmation_17.tpl'
        ) : $this->display(__FILE__, 'views/templates/hook/confirmation.tpl'));
    }

    public function getOrderIdObjectFromEupagoTable($order_id)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'eupago_multibanco where order_id = ' . (int) $order_id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    // Chamada cURL

    public function sendEmailPaymentDetails($order, $result)
    {
        $mostrar_data = Configuration::get('EUPAGO_MULTIBANCO_SHOW_DL');
        if (Validate::isEmail($this->context->customer->email)) {
            $email_tpl_vars = $this->getEmailVars($order, $result);
            $lang = new Language($order->id_lang);
            $diretorio = str_replace(
                "//",
                "/",
                _PS_MODULE_DIR_ . '/' . $this->name . '/mails/' . $lang->iso_code . '/'
            );

            $subject = ($lang->iso_code == "pt") ? 'Aguardar Pagamento' : 'Waiting for payment';
            if ($mostrar_data && $email_tpl_vars['{dataLimite}'] != "" &&
                $email_tpl_vars['{dataLimite}'] != "2099-12-31"
            ) {
                Mail::Send(
                    (int) $order->id_lang,
                    'payment_data_limite',
                    Mail::l($subject, (int) $order->id_lang),
                    $email_tpl_vars,
                    $this->context->customer->email,
                    $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    $diretorio,
                    false,
                    (int) $order->id_shop
                );
            } else {
                Mail::Send(
                    (int) $order->id_lang,
                    'payment_data',
                    Mail::l($subject, (int) $order->id_lang),
                    $email_tpl_vars,
                    $this->context->customer->email,
                    $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    $diretorio,
                    false,
                    (int) $order->id_shop
                );
            }
        }
    }

    public function getEmailVars($order, $referencia)
    {
        $dataLimite = ($referencia->dataLimite == '') ? $referencia->dataLimite : $referencia->dataLimite;

        $data = array(
            '{firstname}' => $this->context->customer->firstname,
            '{lastname}' => $this->context->customer->lastname,
            '{email}' => $this->context->customer->email,
            '{order_name}' => $order->getUniqReference(),
            '{entidade}' => $referencia->entidade,
            '{referencia}' => $referencia->referencia,
            '{dataLimite}' => $dataLimite,
            '{valor}' => Tools::displayPrice($referencia->valor, $this->context->currency, false),
            '{this_path}' => _PS_BASE_URL_ . __PS_BASE_URI__ . '/modules/' . $this->name,
        );
        return $data;
    }

    /**
     * Update EUPAGO
     */

    public function hookDisplayAdminOrder($params)
    {
        $order_id = $params['id_order'];

        $dados = $this->getOrderIdObjectFromEupagoTable($order_id);

        $this->context->smarty->assign('module_dir', $this->_path);

        if ($dados) {
            $this->smarty->assign(
                array(
                    'entidade' => $dados[0]['entidade'],
                    'referencia' => $dados[0]['referencia'],
                    'total' => $dados[0]['valor'],
                )
            );
            if (isset($dados[0]['dataLimite']) && $dados[0]['dataLimite'] != "2099-12-31") {
                $this->smarty->assign('dataLimite', $dados[0]['dataLimite']);
            }

            return $this->display(__FILE__, 'views/templates/admin/adminPaymentDetails.tpl');
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        $order_id = Tools::getValue('id_order');

        $dados = $this->getOrderIdObjectFromEupagoTable($order_id);

        if ($dados) {
            $this->smarty->assign(
                array(
                    'modules_dir' => $this->_path,
                    'entidade' => $dados[0]['entidade'],
                    'referencia' => $dados[0]['referencia'],
                    'total' => $dados[0]['valor'],
                )
            );

            if (isset($dados[0]['dataLimite']) && $dados[0]['dataLimite'] != "2099-12-31") {
                $this->smarty->assign('dataLimite', $dados[0]['dataLimite']);
            }
        } else {
            return;
        }

        return $this->display(__FILE__, 'views/templates/front/paymentDetails.tpl');
    }

    /**
     * GET order validate and update total_paid_real in Orders DB by order and paid value
     * Faz a chamada e gera a referencia
     */
    public function generateReference($id, $total)
    {
        unset($_SESSION);

        Tools::clearSmartyCache();

        $chave_api = Configuration::get('EUPAGO_MULTIBANCO_CHAVEAPI');
        $dl = (int) Configuration::get('EUPAGO_MULTIBANCO_TIPO_DL');
        $ndias_dl = (int) Configuration::get('EUPAGO_MULTIBANCO_DL_DIAS');
        $duplicados = (int) Configuration::get('EUPAGO_MULTIBANCO_TIPO_SD');

        // PREPARA O URL DA CHAMADA
        $demo = explode("-", $chave_api);
        if ($demo['0'] == 'demo') {
            $url = 'https://sandbox.eupago.pt/replica.eupagov20.wsdl';
            $url_curl = 'https://sandbox.eupago.pt/clientes/rest_api/multibanco/create';
        } else {
            $url = 'https://clientes.eupago.pt/eupagov20.wsdl';
            $url_curl = 'https://clientes.eupago.pt/clientes/rest_api/multibanco/create';
        }

        if ($duplicados == "1") {
            $per_dup = 1;
        } else {
            $per_dup = 0;
        }

        if ($dl == 1) {
            $tipo = "MBDL";
            $data_inicio = date("Y-m-d");
            $data_fim = $ndias_dl > 0 ? date(
                'Y-m-d',
                strtotime('+' . $ndias_dl . ' day', strtotime($data_inicio))
            ) : date('Y-m-d', strtotime('+ 1 day', strtotime($data_inicio)));
            $arraydados = array(
                "chave" => $chave_api,
                "valor" => $total,
                "id" => (int) ($id),
                "data_inicio" => $data_inicio,
                "data_fim" => $data_fim,
                "valor_minimo" => $total,
                "valor_maximo" => $total,
                "per_dup" => $per_dup,
                "teste_pagamento" => (int) 1,
            );
        } else {
            $tipo = "MB";
            $arraydados = array(
                "chave" => $chave_api,
                "valor" => $total,
                "id" => (int) ($id),
                "per_dup" => $per_dup,
                "teste_pagamento" => 0,
            );
        }

        if (class_exists("SOAPClient")) {
            $client = new SoapClient($url, array('cache_wsdl' => WSDL_CACHE_NONE));
            if ($tipo == "MB") {
                $result = $client->gerarReferenciaMB($arraydados);
            } else {
                if ($tipo == "MBDL") {
                    $result = $client->gerarReferenciaMBDL($arraydados);
                }
            }

            if (!$client) {
                $result->estado = "Falha no serviço SOAP";
            }
        } else {
            $reposta = $this->curlRequest($url_curl, $arraydados);
            $result = Tools::jsonDecode($reposta);
        }

        if ($result->estado == 0) {
            $this->saveResults($result, $id);
            unset($_SESSION);
        }
        return $result;
    }

    /**
     *GET order in eupago DB by reference
     *Save result from euPago server in DB
     */
    public function saveResults($result, $order_id)
    {
        unset($_SESSION);
        Tools::clearSmartyCache();
        /*ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);*/
        Db::getInstance()->insert(
            'eupago_multibanco',
            array(
                'id_eupago_multibanco' => '',
                'order_id' => $order_id,
                'valor' => $result->valor,
                'referencia' => $result->referencia,
                'entidade' => $result->entidade,
                'estadoRef' => 'pendente',
                'dataLimite' => isset($result->data_fim) ? $result->data_fim : "",
            )
        );
    }
    private function curlRequest($url, $post = null, $retries = 3)
    {

        $curl = curl_init($url);
        $result = array();
        if (is_resource($curl) === true) {
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            curl_setopt($curl, CURLOPT_ENCODING, "");
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, "euPago");
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($curl, CURLOPT_TIMEOUT, 120);

            if (isset($post) === true) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, (is_array($post) === true) ?
                    http_build_query($post, '', '&') : $post);
            }

            $result = false;

            while (($result === false) && (--$retries > 0)) {
                $result['resultado'] = curl_exec($curl);
                $result['estado'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            }

            curl_close($curl);
        }
        //var_dump($result);
        return $result['resultado'];
    }
    public function updateStatusDbExpirada($order_id)
    {
        Db::getInstance()->update(
            'eupago_multibanco',
            array(
                'estadoRef' => 'expirada',
            ),
            'order_id = ' . $order_id
        );
    }

    /**
     *Function to receive payment confirmation
     *The order state will be update to payed
     */
    public function callback($referencia, $valor, $chave, $identificador)
    {

        $chave_api = Configuration::get('EUPAGO_MULTIBANCO_CHAVEAPI');
        $context = Context::getContext();
        $context->link = new Link();

        if ($chave == $chave_api) {
            $valor = str_replace(',', '.', $valor);
            $order_byReference = $this->getOrderByReference($referencia, $valor);
            if ($order_byReference[0]['order_id'] != $identificador) {
                return "O identificador e a referencia não correspondem para esta encomenda";
            }
            if ($order_byReference[0]['estadoRef'] == 'pago') {
                return "Referencia Já paga";
            }
            $orderId = $identificador;
            if (!empty($orderId)) {
                $query = "UPDATE `" .
                _DB_PREFIX_ . "orders` SET current_state=" .
                (int) (Configuration::get('EUPAGO_MULTIBANCO_ESTADO_1')) . " WHERE id_order = " . (int) $orderId;
                Db::getInstance()->Execute($query);
                $query = "INSERT INTO `" . _DB_PREFIX_ .
                "order_history`(id_employee,id_order,id_order_state,date_add) values(0," .
                $orderId . "," .
                (int) (Configuration::get('EUPAGO_MULTIBANCO_ESTADO_1')) . ",now());";
                Db::getInstance()->Execute($query);

                $lang = $this->context->language->iso_code;
                $subject = ($lang == "pt") ? 'Pagamento bem sucedido' : 'Successful payment';
                //procurar o email do cliente para enviar lhe a notificação de pagamento bem sucedido
                $sql = "SELECT " . _DB_PREFIX_ . "customer.email, " . _DB_PREFIX_ . "orders.id_lang," .
                _DB_PREFIX_ . "orders.reference FROM " .
                _DB_PREFIX_ . "orders," . _DB_PREFIX_ . "customer WHERE " .
                _DB_PREFIX_ . "orders.id_order=" . (int) $orderId . " and " .
                _DB_PREFIX_ . "orders.id_customer = " . pSQL(_DB_PREFIX_ . "customer.id_customer");
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

                Mail::Send(
                    (int) $result[0]['id_lang'], // defaut language id
                    'payment', // email template file to be use
                    $subject, // email subject
                    array(
                        "message" => $subject,
                        "{firstname}" => $this->context->customer->firstname,
                        "{lastname}" => $this->context->customer->lastname,
                        "{order_name}" => $result[0]['reference'],
                    ),
                    $result[0]['email'], // receiver email address
                    null, //receiver name
                    null, //from email address
                    null//from name
                );
                $this->updateStatusDB($orderId);
                $this->updateValidateOrder($orderId, $valor);
                echo "Atualizada para paga"; //atualizada para paga
                return "Atualizada para paga";
            } else {
                return "Referencia não encontrada"; //Já paga
            }
        } else {
            return "Chave de API inválida"; //Chave inválida
        }
    }

    public function getOrderByReference($referencia, $valor = null)
    {
        $sql = 'SELECT * FROM ' .
        _DB_PREFIX_ . 'eupago_multibanco where referencia = '
        . (int) $referencia . ' and valor = ' . (float) $valor;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function updateStatusDB($order_id)
    {
        Db::getInstance()->update(
            'eupago_multibanco',
            array(
                'estadoRef' => 'pago',
            ),
            'order_id = ' . $order_id
        );
    }

    //Função envio SMS

    public function updateValidateOrder($order_id, $valor)
    {
        $query = "UPDATE `" .
        _DB_PREFIX_ . "orders` SET total_paid_real=" .
        (float) $valor . ", valid=1 WHERE id_order = " . (int) $order_id;
        Db::getInstance()->Execute($query);
    }

    public function callBackExpirada($referencia, $valor, $chave, $tipo_callback)
    {
        if ($tipo_callback != 'expirada') {
            return "tipo de calback invalido";
        }

        $chaveReg = Configuration::get('EUPAGO_MULTIBANCO_CHAVEAPI');

        $context = Context::getContext();
        $context->link = new Link();
        if ($chave == $chaveReg) {
            $valor = str_replace(',', '.', $valor);
            $orderId = $this->getEupago_multibancoOrderDb($referencia, $valor);
            if (!empty($orderId)) {
                $new_history = new OrderHistory();
                $new_history->id_order = (int) $orderId;

                $new_history->changeIdOrderState((int) 6, $orderId);
                $new_history->addWithemail(true, null, $context);

                $this->updateEupago_multibancoOrderDb_expirada($orderId);

                return 1; //atualizada para paga
            } else {
                return 0; //J� paga
            }
        } else {
            return -1; //Chave inv�lida
        }
    }
}
