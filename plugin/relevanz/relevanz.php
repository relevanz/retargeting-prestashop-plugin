<?php
/**
 *  @author    Releva GmbH - https://www.releva.nz
 *  @copyright 2019-2021 Releva GmbH
 *  @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Relevanz extends Module
{

    protected $mainTab = 'AdminRelevanzMain';
    protected $subTabs = [
        'AdminRelevanzStats' => [
            'en' => 'Statistics',
            'de' => 'Statistik',
            'fr' => 'Statistique',
        ],
        'AdminRelevanzConf' => [
            'en' => 'Configuration',
            'de' => 'Konfiguration',
            'fr' => 'Configuration',
        ],
    ];

    public function __construct()
    {
        $this->name = 'relevanz';
        $this->module_key = 'e58b458525f7c6a1732148d101f9997d';
        $this->author = 'Releva GmbH';
        $this->tab = 'advertising_marketing';
        // The version compliancy check in prestashop 1.5 is kinda broken.
        $this->ps_versions_compliancy = ['min' => '1.5.1', 'max' => _PS_VERSION_.'.9'];
        $this->version = '1.0.0';
        // Indicates that the module’s template files have been built with PrestaShop 1.6’s
        // bootstrap tools in mind – and therefore, that PrestaShop should not try to wrap the
        // template code for the configuration screen (if there is one) with helper tags.
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = 'releva.nz';
        $this->description = $this->l('releva.nz - Technology for personalized marketing');
    }

    protected function addErrorMessage($errMsg)
    {
         $this->_errors[] = $errMsg;
    }

    protected function uninstallTabs()
    {
        $tabs = array_keys($this->subTabs);
        $tabs[] = $this->mainTab;
        $happy = true;
        foreach ($tabs as $className) {
            if (($tabId = Tab::getIdFromClassName($className)) > 0) {
                $tab = new Tab((int)$tabId);
                if (!$tab->delete()) {
                    $this->addErrorMessage($this->l('Unable to remove the releva.nz menu structure.'));
                    $happy = false;
                }
            }
        }
        return $happy;
    }

    protected function installTabs()
    {
        $languages = Language::getLanguages(false);

        $mainTabId = (int)Tab::getIdFromClassName($this->mainTab);
        $mainTab = null;

        if ($mainTabId > 0) {
            $mainTab = new Tab($mainTabId);
        } else {
            $mainTab = new Tab();
            $mainTab->class_name = $this->mainTab;
            $mainTab->module = $this->name;
            $mainTab->id_parent = 0;
            foreach ($languages as $lang) {
                $mainTab->name[(int)$lang['id_lang']] = $this->displayName;
            }
            if (!$mainTab->add()) {
                $this->addErrorMessage($this->l('Unable to register the releva.nz menu headline.'));
                return false;
            }

            $mainTabId = (int)Tab::getIdFromClassName($this->mainTab);
        }

        foreach ($this->subTabs as $className => $langDef) {
            if ((int)Tab::getIdFromClassName($className) > 0) {
                continue;
            }
            $subTab = new Tab();
            $subTab->module = $this->name;
            $subTab->class_name = $className;
            $subTab->id_parent = $mainTabId;

            $fallbackTranslation = reset($langDef);
            foreach ($languages as $lang) {
                $subTabName = $fallbackTranslation;
                if (isset($lang['iso_code']) && isset($langDef[$lang['iso_code']])) {
                    $subTabName = $langDef[$lang['iso_code']];
                }
                $subTab->name[(int)$lang['id_lang']] = $subTabName;
            }

            if (!$subTab->add()) {
                $this->addErrorMessage(sprintf(
                    $this->l('Unable to register the releva.nz %s sub-menu.'),
                    $fallbackTranslation
                ));
                return false;
            }
        }

        if (!Validate::isLoadedObject($mainTab)) {
            $this->addErrorMessage($this->l('The menu structure of the releva.nz plugin could not be installed.'));
            return false;
        }

        return true;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        return parent::install()
            && $this->uninstallTabs() && $this->installTabs()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('header');
    }

    public function uninstall()
    {
        // Hooks will be uninstalled by prestashop automatically.
        return parent::uninstall() && $this->uninstallTabs();
    }

    public function hookDisplayBackOfficeHeader()
    {
        // Use addCss(), registerStylesheet() is only for front controllers.
        $this->context->controller->addCss(
            $this->_path.'views/css/relevanz.css'
        );
    }

    public function getContent()
    {
        return Tools::redirectAdmin($this->context->link->getAdminLink('AdminRelevanzConf'));
    }

    public function hookHeader($params)
    {
        require_once(dirname(__FILE__).'/controllers/hook/TrackingHook.php');
        $trackingHookHandler = new RelevanzTracking\Hook\TrackingHook($this->context, $this->name);
        $trackingHookHandler->exec($params);
    }
}
