<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
require_once(__DIR__.'/../../vendor/autoload.php');

use RelevanzTracking\Lib\Credentials;
use RelevanzTracking\Lib\RelevanzException;
use RelevanzTracking\Lib\HttpResponse;
use RelevanzTracking\PrestashopConfiguration;

class RelevanzCallbackModuleFrontController extends ModuleFrontController
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

    protected function getDbStats() {
        $r = [];
        try {
            $data = Db::getInstance()->getRow('SELECT @@version, @@version_comment');
        } catch (\Exception $e) {
            $data = null;
        }
        if (isset($data['@@version_comment'])) {
            $r['server'] = $data['@@version_comment'];
        }
        if (isset($data['@@version'])) {
            $r['version'] = $data['@@version'];
        }
        return empty($r) ? null : $r;
    }

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

    public function display() {
        $herp = $this->verifyRequest();
        if ($herp !== null) {
            $herp->out();
            return;
        }
        $data = [
            'plugin-version' => PrestashopConfiguration::getPluginVersion(),
            'shop' => [
                'system' => 'prestashop',
                'version' => _PS_VERSION_,
            ],
            'environment' => [
                'server-software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null,
                'php' => [
                    'version' => phpversion(),
                    'sapi-name' => php_sapi_name(),
                    'memory-limit' => ini_get('memory_limit'),
                    'max-execution-time' => ini_get('max_execution_time'),
                ],
                'db' => $this->getDbStats(),
            ],
            'callbacks' => $this->discoverCallbacks()
        ];
        $r = new HttpResponse(json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION), [
            'Content-Type: application/json; charset="utf-8"',
            'Cache-Control: must-revalidate',
        ]);
        $r->out();
    }

}
