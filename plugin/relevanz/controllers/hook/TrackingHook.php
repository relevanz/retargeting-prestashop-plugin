<?php
/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2021 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

namespace RelevanzTracking\Hook;

require_once(dirname(__FILE__).'/../../autoload.php');

use Exception;
use ReflectionClass;

use Context;
use Media;
use Order;

use IndexController;
use CategoryController;
use ProductController;
use OrderConfirmationController;

use Releva\Retargeting\Base\RelevanzApi;
use Releva\Retargeting\Base\Credentials;
use Releva\Retargeting\Base\Exception\RelevanzException;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;

class TrackingHook
{
    protected $credentials = null;
    protected $context = null;
    protected $moduleName = '';

    public function __construct(Context $context, $moduleName)
    {
        $this->context = $context;
        $this->moduleName = $moduleName;
        $this->credentials = PrestashopConfiguration::getCredentials();
    }

    protected function accessControllerObject($method, $property)
    {
        $getCall = [$this->context->controller, $method];
        if (is_callable($getCall)) {
            // Sane 1.6+
            return call_user_func($getCall);
        }
        // Insane Prestashop 1.5
        $class = new ReflectionClass(get_class($this->context->controller));
        if (!$class->hasProperty($property)) {
            return null;
        }
        $rp = $class->getProperty($property);
        $rp->setAccessible(true);
        return $rp->getValue($this->context->controller);
    }

    protected function buildTrackerIndex($params)
    {
        return [
            'url' => RelevanzApi::RELEVANZ_TRACKER_URL,
            'params' => array_merge($params, [
                'action' => 's'
             ]),
        ];
    }

    protected function buildTrackerCategory($params)
    {
        if (!($this->context->controller instanceof CategoryController)) {
            return [];
        }
        $category = $this->accessControllerObject('getCategory', 'category');
        $id = isset($category->id) ? (int)$category->id : 0;
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

    protected function buildTrackerProduct($params)
    {
        if (!($this->context->controller instanceof ProductController)) {
            return [];
        }
        $product = $this->accessControllerObject('getProduct', 'product');
        $id = isset($product->id) ? (int)$product->id : 0;
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

    protected function buildTrackerOrderConfirmation($params)
    {
        if (!($this->context->controller instanceof OrderConfirmationController)) {
            return [];
        }
        $params; // Just to silence the validation process.
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
        foreach ($order->getProducts() as $p) {
            if (!isset($p['product_id']) || !((int)$p['product_id'] > 0)) {
                continue;
            }
            $pIds[] = $p['product_id'];
        }

        $r['params']['products'] = implode(',', $pIds);

        return $r;
    }

    protected function registerJs($tr)
    {
        if (empty($tr)) {
            return;
        }

        if (!is_callable('Media::addJsDef')) {
            // prestashop v1.5
            $this->context->controller->js_files[] = htmlentities($tr['script']);
            return;
        }

        Media::addJsDef([
            'relevanz_px' => $tr['script'],
        ]);

        if (is_callable([$this->context->controller, 'registerJavascript'])) {
            // prestashop v1.7
            $this->context->controller->registerJavascript(
                'relevanz-front-js',
                'modules/' . $this->moduleName . '/views/js/front.js'
            );
        } elseif (is_callable([$this->context->controller, 'addJS'])) {
            // prestashop v1.6
            $this->context->controller->addJs(_MODULE_DIR_.$this->moduleName.'/views/js/front.js');
        }
    }

    public function exec()
    {
        if (!$this->credentials->isComplete()) {
            return;
        }

        $pageName = str_replace('Controller', '', get_class($this->context->controller));

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

        $tr['script'] = $tr['url'];
        if (isset($tr['params'])) {
            $tr['params'] = http_build_query($tr['params']);
            $tr['script'] .= '?'.$tr['params'];
        }

        $this->registerJs($tr);
    }
}
