<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
require_once(__DIR__.'/../../autoload.php');

use Releva\Retargeting\Base\HttpResponse;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;
use Releva\Retargeting\Prestashop\PrestashopShopInfo;
use Releva\Retargeting\Prestashop\FrontBaseController;

class RelevanzCallbackModuleFrontController extends FrontBaseController
{
    protected function discoverCallbacks() {
        $callbacks = [];
        $dir = new DirectoryIterator(__DIR__);
        foreach ($dir as $fileinfo) {
            $m = [];
            if (!preg_match('/^([A-Za-z0-9]+).php$/', $fileinfo->getFilename(), $m)) {
                continue;
            }
            $class = 'Relevanz'.ucfirst($m[1]).'ModuleFrontController';
            $cbname = strtolower($m[1]);
            $fpath = $fileinfo->getRealPath();
            if (!file_exists($fpath)) {
                continue;
            }
            $fc = file_get_contents($fpath);
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

    protected function action() {
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
