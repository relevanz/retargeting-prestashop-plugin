<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2022 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

require_once(dirname(__FILE__).'/lib/relevanz/retargeting-base-lib/ClassLoader.php');
Releva\Retargeting\Base\ClassLoader::init()->addPsr4Map([
    'Releva\\Retargeting\\Base\\' => dirname(__FILE__).'/lib/relevanz/retargeting-base-lib/',
    'Releva\\Retargeting\\Prestashop\\' => dirname(__FILE__).'/lib/prestashop/',
]);
