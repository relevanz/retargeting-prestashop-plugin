<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
namespace Releva\Retargeting\Prestashop;

use Translate;

class Helper
{
    /**
     * Helper method for making translations possible in twig templates using the old translation
     * system because the symfony one is not available for 3rd party modules yet.
     */
    public function l($string, $specific = false, $raw = false) {
        
        $s = Translate::getModuleTranslation('relevanz', $string, ($specific) ? $specific : 'relevanz');
        if ($raw) {
            $s = html_entity_decode($s, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        }
        return $s;
    }

}
