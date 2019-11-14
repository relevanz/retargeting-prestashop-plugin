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
use RelevanzTracking\Lib\CSVExporter;
use RelevanzTracking\Lib\JsonExporter;
use RelevanzTracking\Lib\HttpResponse;
use RelevanzTracking\PrestashopConfiguration;

class RelevanzExportModuleFrontController extends ModuleFrontController
{
    const ITEMS_PER_PAGE = 2000;

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

    protected function exportProducts() {
        $shopId = (int)$this->context->shop->id;
        $langId = (int)$this->context->language->id;

        $pCount = (int)Db::getInstance()->getValue('
            SELECT count(*)
              FROM `' . _DB_PREFIX_ . 'product_shop`
             WHERE `id_shop` = '.$shopId.' AND `active`="1"
        ');
        if (empty($pCount)) {
            return new HttpResponse('No products found.', [
                'HTTP/1.0 404 Not Found'
            ]);
        }

        $exporter = null;
        switch (Tools::getValue('type')) {
            case 'json': {
                $exporter = new JsonExporter();
                break;
            }
            default: {
                $exporter = new CSVExporter();
                break;
            }
        }

        $sql = '
            SELECT `id_product` AS id
              FROM `' . _DB_PREFIX_ . 'product_shop`
             WHERE `id_shop` = '.$shopId.' AND `active`="1"
          ORDER BY id_product ASC
        ';
        if (($page = (int)Tools::getValue('page')) > 0) {
            $sql .= '     LIMIT '.(($page - 1) * self::ITEMS_PER_PAGE).','.self::ITEMS_PER_PAGE;
        }

        $products = Db::getInstance()->executeS($sql);

        if (empty($products)) {
            return new HttpResponse('', [
                'HTTP/1.0 404 Not Found',
                'X-Relevanz-Product-Count: '.$pCount,
            ]);
        }

        foreach ($products as $p) {
            $product =  new Product($p['id'], false, $langId , $shopId, $this->context);

            $coverImageId = (int)$product->getCoverWs();
            $productImage = null;
            if ($coverImageId > 0) {
                $productImage = $this->context->link->getImageLink($product->link_rewrite, $p['id'].'-'.$coverImageId);
            }

            $exporter->addRow([
                'product_id' => (int)$p['id'],
                'category_ids' => Product::getProductCategories($p['id']),
                'product_name' => $product->name,
                'short_description' => $product->description_short,
                'long_description' => $product->description,
                'price' => $product->price,
                'link' => $product->getLink($this->context),
                'image' => $productImage,
            ]);
        }

        return new HttpResponse(
            $exporter->getContents(),
            array_merge(
                $exporter->getHttpHeaders(),
                [
                    'Cache-Control: must-revalidate',
                    'X-Relevanz-Product-Count: '.$pCount,
                    'Content-Type: text/plain; charset="utf-8"', 'Content-Disposition: inline',
                ]
            )
        );
    }

    public function display() {
        $herp = $this->verifyRequest();
        if ($herp !== null) {
            $herp->out();
            return;
        }
        $this->exportProducts()->out();
    }

    public static function discover() {
        return [
            'url' => PrestashopConfiguration::getUrlExport(),
            'parameters' => [
                'type' => [
                    'values' => ['csv', 'json'],
                    'default' => 'csv',
                    'optional' => true,
                ],
                'page' => [
                    'type' => 'integer',
                    'optional' => true,
                    'info' => [
                         'items-per-page' => self::ITEMS_PER_PAGE,
                    ],
                ],
            ]
        ];
    }

}
