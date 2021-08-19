<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2021 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

namespace Releva\Retargeting\Prestashop;

use ModuleAdminController;
use Shop;
use Translate;

class AdminBaseController extends ModuleAdminController
{

    public function initToolbarFlags()
    {
        parent::initToolbarFlags();
        $this->context->smarty->assign('help_link', null);
    }

    public function initContent()
    {
        $this->display = 'view';
        if (Shop::isFeatureActive() && ($this->context->shop->getContextType() !== Shop::CONTEXT_SHOP)) {
            $this->context->smarty->assign([
                'msg_wrong_shop_context' => sprintf(
                    // A very long line for the primitive parser of the prestashop translation tool.
                    $this->l('The releva.nz plugin can only be configured for specific shops. Please change the context to a specific shop. Currently the shop [%s] is used.', __CLASS__),
                    $this->context->shop->name
                )
            ]);
        }
        $this->context->smarty->assign(['controller_name' => $this->controller_name]);
        parent::initContent();
    }

    public function createTemplate($tplName)
    {
        $ds = DIRECTORY_SEPARATOR;
        $tplName = __DIR__.$ds.'..'.$ds.'..'.$ds.'views'.$ds.'templates'.$ds.'admin'.$ds.$tplName;
        return $this->context->smarty->createTemplate($tplName, $this->context->smarty);
    }

    /**
     * The old translation system is stoopid (and deprecated for a reason, multiple reasons even).
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($class === __CLASS__) {
            $controller = str_replace(__NAMESPACE__.'\\', '', __CLASS__);
            return Translate::getModuleTranslation($this->module->name, $string, $controller, null, $addslashes);
        }
        return parent::l($string, $class, $addslashes, $htmlentities);
    }
}
