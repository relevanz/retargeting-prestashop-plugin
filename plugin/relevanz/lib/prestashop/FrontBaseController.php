<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
namespace Releva\Retargeting\Prestashop;

use Exception;

use Tools;
use ModuleFrontController;

use Releva\Retargeting\Base\HttpResponse;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;

abstract class FrontBaseController extends ModuleFrontController
{
    protected function verifyRequest() {
        $credentials = PrestashopConfiguration::getCredentials();
        if (!$credentials->isComplete()) {
            return new HttpResponse('releva.nz module is not configured', [
                'HTTP/1.0 403 Forbidden',
                'Content-Type: text/plain; charset="utf-8"',
                'Cache-Control: must-revalidate',
            ]);
        }

        if (Tools::getValue('auth') !== $credentials->getAuthHash()) {
            return new HttpResponse('Missing authentification', [
                'HTTP/1.0 401 Unauthorized',
                'Content-Type: text/plain; charset="utf-8"',
                'Cache-Control: must-revalidate',
            ]);
        }
        return null;
    }

    /**
     * Run the controller main action.
     *
     * @return HttpResponse
     */
    abstract protected function action();

    public function display() {
        $herp = $this->verifyRequest();
        if ($herp !== null) {
            $herp->out();
            return;
        }
        try {
            $derp = $this->action();

        } catch (Exception $e) {
            $derp = new HttpResponse(get_class($e).': '.$e->getMessage(), [
                'HTTP/1.0 500 Internal Server Error',
                'Content-Type: text/plain; charset="utf-8"',
                'Cache-Control: must-revalidate',
            ]);
        }

        $derp->out();
    }

}
