<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
require_once(__DIR__.'/../../vendor/autoload.php');

use RelevanzTracking\Lib\RelevanzApi;
use RelevanzTracking\Lib\Credentials;
use RelevanzTracking\Lib\RelevanzException;
use RelevanzTracking\PrestashopAdminBaseController;
use RelevanzTracking\PrestashopConfiguration;
use RelevanzTracking\PrestashopHelper;

class AdminRelevanzConfController extends PrestashopAdminBaseController
{
    public function __construct() {

        $this->show_toolbar = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->page_header_toolbar_title = $this->l('Configuration');

        if (false) {
            // This part is used to make prestashops translation utility aware of the error messages.
            $this->l('msg_1553934614');
            $this->l('msg_1553935480');
            $this->l('msg_1553935569');
            $this->l('msg_1553935786');
        }
    }

    public function setMedia($isNewTheme = false) {
        if (!$isNewTheme) {
            $this->addJs(_MODULE_DIR_.$this->module->name.'/views/js/old-theme-helper.js');
        }
        $this->addCss(_MODULE_DIR_.$this->module->name.'/views/css/configuration.css');
        $this->addJs(_MODULE_DIR_.$this->module->name.'/views/js/configuration.js');
        parent::setMedia($isNewTheme);
    }

    public function saveAction() {
        $post = Tools::getValue('relevanz');
        if (!isset($post['conf']) || !is_array($post['conf'])) {
            return;
        }

        try {
            $credentials = RelevanzApi::verifyApiKey(
                isset($post['conf']['apikey']) ? $post['conf']['apikey'] : '',
                ['callback-url' => PrestashopConfiguration::getUrlExport()]
            );
            PrestashopConfiguration::updateCredentials($credentials);

            $this->confirmations[] = $this->l('Configuration saved.');

        } catch (RelevanzException $re) {
            $sarg = [$this->l('msg_'.$re->getCode())];
            $sarg = array_merge($sarg, $re->getSprintfArgs());
            $this->errors[] = call_user_func_array('sprintf', $sarg).' (E'.$re->getCode().')';
        }
    }

    public function renderView() {
        $this->saveAction();
        $credentials = PrestashopConfiguration::getCredentials();
        $exportUrl = str_replace(':auth', $credentials->getAuthHash(), PrestashopConfiguration::getUrlExport());

        if (PrestashopConfiguration::conflictsMultistore()) {
            $this->errors[] = $this->l('One or more API keys are used by different multistore instances. This can lead to unwanted behavior. Please make sure that each shop uses its own API key. If you need more API keys, please contact the releva.nz support.');
        }

        $this->context->smarty->assign([
            'credentials' => $credentials,
            'exportUrl' => $exportUrl,
        ]);
        return $this->createTemplate('configuration.tpl')->fetch();
    }

}
