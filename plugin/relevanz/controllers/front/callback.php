<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2021 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

require_once(dirname(__FILE__).'/../../autoload.php');

use Releva\Retargeting\Base\HttpResponse;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;
use Releva\Retargeting\Prestashop\PrestashopShopInfo;
use Releva\Retargeting\Prestashop\FrontBaseController;

class RelevanzCallbackModuleFrontController extends FrontBaseController
{
    protected function discoverCallbacks()
    {
        $callbacks = [];
        $dir = new DirectoryIterator(dirname(__FILE__));
        foreach ($dir as $fileinfo) {
            $m = [];
            if (!preg_match('/^([A-Za-z0-9]+).php$/', $fileinfo->getFilename(), $m)) {
                continue;
            }
            $class = 'Relevanz'.Tools::ucfirst($m[1]).'ModuleFrontController';
            $cbname = Tools::strtolower($m[1]);
            $fpath = $fileinfo->getRealPath();
            if (!file_exists($fpath)) {
                continue;
            }
            $fc = Tools::file_get_contents($fpath);
            if (strpos($fc, $class) === false) {
                continue;
            }
            require_once($fpath);
            if (class_exists($class) && is_callable($class .'::discover')) {
                $callbacks[$cbname] = call_user_func($class .'::discover');
            }
        }
        return $callbacks;
    }

    protected function action()
    {
        $data = [
            'plugin-version' => PrestashopConfiguration::getPluginVersion(),
            'shop' => [
                'system' => PrestashopShopInfo::getShopSystem(),
                'version' => PrestashopShopInfo::getShopVersion(),
                'is-multishop' => Shop::isFeatureActive(),
                'shop-name' => $this->context->shop->name,
            ],
            'environment' => PrestashopShopInfo::getServerEnvironment(),
            'callbacks' => $this->discoverCallbacks()
        ];
        $r = new HttpResponse(json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION), [
            'Content-Type: application/json; charset="utf-8"',
            'Cache-Control: must-revalidate',
        ]);
        return $r;
    }
}
