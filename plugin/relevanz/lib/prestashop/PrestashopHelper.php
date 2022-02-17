<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2022 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

namespace Releva\Retargeting\Prestashop;

use Translate;

class Helper
{
    /**
     * Helper method for making translations possible in twig templates using the old translation
     * system because the symfony one is not available for 3rd party modules yet.
     */
    public function l($string, $specific = false, $raw = false)
    {
        
        $s = Translate::getModuleTranslation('relevanz', $string, ($specific) ? $specific : 'relevanz');
        if ($raw) {
            $s = html_entity_decode($s, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        }
        return $s;
    }
}
