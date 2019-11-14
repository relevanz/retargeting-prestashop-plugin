<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
namespace RelevanzTracking\Hook;

require_once(__DIR__.'/../../vendor/autoload.php');

use Exception;

use Context;
use Media;
use Order;

use IndexController;
use CategoryController;
use ProductController;
use OrderConfirmationController;

use RelevanzTracking\Lib\RelevanzApi;
use RelevanzTracking\Lib\Credentials;
use RelevanzTracking\Lib\RelevanzException;
use RelevanzTracking\PrestashopConfiguration;

class TrackingHook
{
    protected $credentials = null;
    protected $context = null;
    protected $moduleName = '';

    public function __construct(Context $context, $moduleName) {
        $this->context = $context;
        $this->moduleName = $moduleName;
        $this->credentials = PrestashopConfiguration::getCredentials();
    }

    protected function buildTrackerIndex($params) {
        return [
            'url' => RelevanzApi::RELEVANZ_TRACKER_URL,
            'params' => array_merge($params, [
                'action' => 's'
             ]),
        ];
    }

    protected function buildTrackerCategory($params) {
        if (!($this->context->controller instanceof CategoryController)) {
            return [];
        }
        $id = isset($this->context->controller->getCategory()->id)
            ? (int)$this->context->controller->getCategory()->id
            : 0;
        if (!($id > 0)) {
            return [];
        }
        return [
            'url' => RelevanzApi::RELEVANZ_TRACKER_URL,
            'params' => array_merge($params, [
                'action' => 'c',
                'id' => $id
            ]),
        ];
    }

    protected function buildTrackerProduct($params) {
        if (!($this->context->controller instanceof ProductController)) {
            return [];
        }
        $id = isset($this->context->controller->getProduct()->id)
            ? (int)$this->context->controller->getProduct()->id
            : 0;
        if (!($id > 0)) {
            return [];
        }
        return [
            'url' => RelevanzApi::RELEVANZ_TRACKER_URL,
            'params' => array_merge($params, [
                'action' => 'p',
                'id' => $id
            ]),
        ];
    }

    protected function buildTrackerOrderConfirmation($params) {
        if (!($this->context->controller instanceof OrderConfirmationController)) {
            return [];
        }
        $id = isset($this->context->controller->id_order)
            ? (int)$this->context->controller->id_order
            : 0;
        if (!($id > 0)) {
            return [];
        }

        $order = new Order($id);
        if ($order->id != $id) {
            return [];
        }

        $r = [
            'url' => RelevanzApi::RELEVANZ_CONV_URL,
            'params' => [
                'cid' => $this->credentials->getUserId(),
                'orderId' => $order->id,
                'amount' => round($order->total_paid, 2),
                'products' => null,
            ],
        ];

        $pIds = [];
        foreach ($order->getCartProducts() as $p) {
            if (!isset($p['product_id']) || !((int)$p['product_id'] > 0)) {
                continue;
            }
            $pIds[] = $p['product_id'];
        }

        $r['params']['products'] = implode(',', $pIds);

        return $r;
    }

    protected function registerJs($tr) {
        if (empty($tr)) {
            return;
        }
        Media::addJsDef([
            'relevanz_tr' => $tr,
        ]);
        if (is_callable([$this->context->controller, 'registerJavascript'])) {
            // v1.7
            $this->context->controller->registerJavascript(
                'relevanz-front-js',
                'modules/' . $this->moduleName . '/views/js/front.js'
            );
        } else if (is_callable([$this->context->controller, 'addJS'])) {
            // v1.6, maybe older
            $this->context->controller->addJs(_MODULE_DIR_.$this->moduleName.'/views/js/front.js');
        }
    }

    public function exec($hookParams) {
        if (!$this->credentials->isComplete()) {
            return;
        }
        /*
        if (is_callable([$this->context->controller, 'getPageName'])) {
            $pageName = $this->context->controller->getPageName();
        } else {
            // extract page name from smarty variables for older versions of prestashop
            $pageName = $this->context->smarty->getTemplateVars('page_name');
        }

        $pageName = str_replace(' ', '', ucwords(
            str_replace(['-', '_'], ' ', $pageName)
        ));
        */
        $pageName = str_replace('Controller', '', get_class($this->context->controller));
        /*
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')) {
            var_dump($pageName);
        }
        //*/

        $cb = [$this, 'buildTracker'.$pageName];
        if (!is_callable($cb)) {
            return;
        }

        try {
            $tr = call_user_func($cb, ['cid' => $this->credentials->getUserId(), 't' => 'd']);
        } catch (Exception $e) {
            $tr = ['status' => 'exception', 'message' => $e->getMessage()];
        }

        if (empty($tr)) {
            return;
        }

        if (isset($tr['params'])) {
            $tr['params'] = http_build_query($tr['params']);
        }

        $this->registerJs($tr);
    }
}
