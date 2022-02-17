<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2022 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

namespace Releva\Retargeting\Prestashop;

use Context;
use Db;

use Releva\Retargeting\Base\AbstractShopInfo;

class PrestashopShopInfo extends AbstractShopInfo
{
    /**
     * Technical name of the shop system.
     *
     * @return string
     */
    public static function getShopSystem()
    {
        return 'prestashop';
    }

    /**
     * Version of the shop as a string.
     */
    public static function getShopVersion()
    {
        return _PS_VERSION_;
    }

    public static function getDbVersion()
    {
        $r = [];
        try {
            $data = Db::getInstance()->getRow(
                'SELECT @@version AS `version`, @@version_comment AS `server`'
            );
            if (is_array($data)) {
            	return $data;
            }
        } catch (\Exception $e) {}
        return [
            'version' => null,
            'server' => null,
        ];
    }
    
    public static function getUrlCallback()
    {
        return str_replace(
            '_auth', ':auth',
            Context::getContext()->link->getModuleLink('relevanz', 'callback', ['auth' => '_auth'])
        );
    }

    public static function getUrlProductExport()
    {
        return str_replace(
            '_auth', ':auth',
            Context::getContext()->link->getModuleLink('relevanz', 'export', ['auth' => '_auth'])
        );
    }
}
