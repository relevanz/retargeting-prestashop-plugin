<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
namespace Releva\Retargeting\Prestashop;

use Context;
use Configuration;
use Shop;

use Releva\Retargeting\Base\ConfigurationInterface;
use Releva\Retargeting\Base\Credentials;

class PrestashopConfiguration implements ConfigurationInterface
{
    const PLUGIN_VERSION = '1.1.0';
    const CONF_APIKEY = 'RELEVANZ_APIKEY';
    const CONF_USERID = 'RELEVANZ_USERID';

    public static function getCredentials() {
        $shopId = Context::getContext()->shop->id;
        $shopGroupId = Context::getContext()->shop->id_shop_group;
        return new Credentials(
            Configuration::get(self::CONF_APIKEY, null, $shopGroupId, $shopId, null).'',
            (int)Configuration::get(self::CONF_USERID, null, $shopGroupId, $shopId, null)
        );
    }

    public static function updateCredentials(Credentials $credentials) {
        $shopId = Context::getContext()->shop->id;
        $shopGroupId = Context::getContext()->shop->id_shop_group;
        Configuration::updateValue(self::CONF_APIKEY, $credentials->getApiKey(), false, $shopGroupId, $shopId);
        Configuration::updateValue(self::CONF_USERID, $credentials->getUserId(), false, $shopGroupId, $shopId);
    }

    public static function getPluginVersion() {
        return self::PLUGIN_VERSION;
    }

    public static function conflictsMultistore() {
        if (!Shop::isFeatureActive()) {
            return false;
        }
        $multiconf = Configuration::getMultiShopValues(self::CONF_APIKEY);
        $conflictTable = [];
        foreach ($multiconf as $shopId => $value) {
            if (isset($conflictTable[$value])) {
                return true;
            }
            $conflictTable[$value] = true;
        }
        return false;
    }

}
