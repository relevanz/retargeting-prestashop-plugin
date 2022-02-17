<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2021 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

require_once(dirname(__FILE__).'/../../autoload.php');

use Releva\Retargeting\Base\RelevanzApi;
use Releva\Retargeting\Base\Credentials;
use Releva\Retargeting\Base\Exception\RelevanzException;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;
use Releva\Retargeting\Prestashop\PrestashopShopInfo;
use Releva\Retargeting\Prestashop\PrestashopHelper;
use Releva\Retargeting\Prestashop\AdminBaseController;

class AdminRelevanzConfController extends AdminBaseController
{
    public function __construct()
    {

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

    public function setMedia($isNewTheme = false)
    {
        if (!$isNewTheme) {
            $this->addJs(_MODULE_DIR_.$this->module->name.'/views/js/old-theme-helper.js');
        }
        $this->addCss(_MODULE_DIR_.$this->module->name.'/views/css/configuration.css');
        $this->addJs(_MODULE_DIR_.$this->module->name.'/views/js/configuration.js');
        parent::setMedia($isNewTheme);
    }

    public function saveAction()
    {
        $post = Tools::getValue('relevanz');
        if (!isset($post['conf']) || !is_array($post['conf'])) {
            return;
        }

        try {
            $credentials = RelevanzApi::verifyApiKey(
                isset($post['conf']['apikey']) ? $post['conf']['apikey'] : '',
                ['callback-url' => PrestashopShopInfo::getUrlCallback()]
            );
            PrestashopConfiguration::updateCredentials($credentials);
            $this->confirmations[] = $this->l('Configuration saved.');
        } catch (RelevanzException $re) {
            $msgKey = 'msg_'.$re->getCode();
            $msg = $this->l($msgKey);
            if ($msg === $msgKey) {
                $msg = $re->getMessage();
            }
            $this->errors[] = call_user_func_array(
                'sprintf',
                array_merge([$msg.' (E%s)'], $re->getSprintfArgs(), [$re->getCode()])
            );
        }
    }

    public function renderView()
    {
        $this->saveAction();
        $credentials = PrestashopConfiguration::getCredentials();
        $exportUrl = str_replace(':auth', $credentials->getAuthHash(), PrestashopShopInfo::getUrlProductExport());

        if (PrestashopConfiguration::conflictsMultistore()) {
            // Presta: Fix your $this->l() parser and i'll fix the length of this line.
            $this->errors[] = $this->l('One or more API keys are used by different multistore instances. This can lead to unwanted behavior. Please make sure that each shop uses its own API key. If you need more API keys, please contact the releva.nz support.');
        }

        $this->context->smarty->assign([
            'credentials' => $credentials,
            'exportUrl' => $exportUrl,
        ]);
        return $this->createTemplate('configuration.tpl')->fetch();
    }
}
