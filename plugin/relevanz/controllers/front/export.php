<?php
/* -----------------------------------------------------------
Copyright (c) 2019 Releva GmbH - https://www.releva.nz
Released under the MIT License (Expat)
[https://opensource.org/licenses/MIT]
--------------------------------------------------------------
*/
require_once(__DIR__.'/../../autoload.php');

use Releva\Retargeting\Base\Export\Item\ProductExportItem;
use Releva\Retargeting\Base\Export\ProductCsvExporter;
use Releva\Retargeting\Base\Export\ProductJsonExporter;
use Releva\Retargeting\Base\HttpResponse;
use Releva\Retargeting\Prestashop\PrestashopConfiguration;
use Releva\Retargeting\Prestashop\PrestashopShopInfo;
use Releva\Retargeting\Prestashop\FrontBaseController;

class RelevanzExportModuleFrontController extends FrontBaseController
{
    const ITEMS_PER_PAGE = 1500;

    protected function action() {
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
        switch (Tools::getValue('format')) {
            case 'json': {
                $exporter = new ProductJsonExporter();
                break;
            }
            default: {
                $exporter = new ProductCsvExporter();
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

            $price = $product->getPublicPrice(true, null, 2, null, false, false);
            $priceOffer = $product->getPublicPrice(true, null, 2);

            $exporter->addItem(new ProductExportItem(
                (int)$p['id'],
                Product::getProductCategories($p['id']),
                $product->name,
                $product->description_short,
                $product->description,
                $price,
                $priceOffer,
                $product->getLink($this->context),
                $productImage
            ));
        }


        $headers = [];
        foreach ($exporter->getHttpHeaders() as $hkey => $hval) {
            $headers[] = $hkey.': '.$hval;
        }
        $headers[] = 'Cache-Control: must-revalidate';
        $headers[] = 'X-Relevanz-Product-Count: '.$pCount;
        #$headers[] = 'Content-Type: text/plain; charset="utf-8"'; $headers[] = 'Content-Disposition: inline';

        return new HttpResponse($exporter->getContents(), $headers);
    }

    public static function discover() {
        return [
            'url' => PrestashopShopInfo::getUrlProductExport(),
            'parameters' => [
                'format' => [
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
