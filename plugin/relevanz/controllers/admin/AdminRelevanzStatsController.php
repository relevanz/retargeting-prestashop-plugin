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

class AdminRelevanzStatsController extends PrestashopAdminBaseController
{
    public function __construct()
    {
        $this->show_toolbar = false;
        $this->bootstrap = true;
        parent::__construct();

        $this->page_header_toolbar_title = $this->l('Statistics');
            #.(Shop::isFeatureActive() ? ' ['.$this->context->shop->name.']' : '');
    }

    public function setMedia($isNewTheme = false) {
        $this->addCss(_MODULE_DIR_.$this->module->name.'/views/css/statistics.css');
        $this->addJs(_MODULE_DIR_.$this->module->name.'/views/js/statistics.js');
        parent::setMedia($isNewTheme);
    }

    public function renderView() {
        $credentials = PrestashopConfiguration::getCredentials();

        if (!$credentials->isComplete()) {
            return Tools::redirectAdmin($this->context->link->getAdminLink('AdminRelevanzConf'));
        }
        $this->context->smarty->assign([
            'stats_url' => RelevanzApi::RELEVANZ_STATS_FRAME.$credentials->getApiKey(),
        ]);
        return $this->createTemplate('statistics.tpl')->fetch();
    }

}
