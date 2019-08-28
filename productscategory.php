<?php

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class ProductsCategory
 */
class ProductsCategory extends Module
{
	protected $html;

	public function __construct()
	{
		$this->name = 'productscategory';
		$this->version = '1';
		$this->author = 'Pavel Topol';
		$this->tab = 'front_office_features';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Products in the same category');
		$this->description = $this->l('Adds a block on the product page that displays products from the same category.');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		$this->_clearCache('productscategory.tpl');

		return (parent::install()
			&& $this->registerHook('productfooter')
			&& $this->registerHook('header')
			&& $this->registerHook('addproduct')
			&& $this->registerHook('updateproduct')
			&& $this->registerHook('deleteproduct')
		);
	}

	public function uninstall()
	{
		$this->_clearCache('productscategory.tpl');

		return parent::uninstall();
	}

	public function hookProductFooter($params)
	{
		$id_product = (int)$params['product']->id;
		$product = $params['product'];

		$cache_id = $this->buildCacheKey($params);

		if (!$this->isCached('productscategory.tpl', $this->getCacheId($cache_id))) {
			$category = false;
			if (isset($params['category']->id_category)) {
                $category = $params['category'];
            } elseif (isset($product->id_category_default) && $product->id_category_default > 1) {
                $category = new Category((int)$product->id_category_default);
            }

			if (!Validate::isLoadedObject($category) || !$category->active) {
                return false;
            }

			// Get infos
			$category_products = $category->getProducts($this->context->language->id, 1, 100); /* 100 products max. */

			// Remove current product from the list
			if (is_array($category_products) && count($category_products)) {
				$taxes = Product::getTaxCalculationMethod();
                foreach ($category_products as $key => $category_product) {
                    if ($category_product['id_product'] != $id_product) {
                        if ($taxes == 0 || $taxes == 2) {
                            $category_products[$key]['displayed_price'] = Product::getPriceStatic(
                                (int)$category_product['id_product'],
                                true,
                                null,
                                2
                            );
                        } elseif ($taxes == 1) {
                            $category_products[$key]['displayed_price'] = Product::getPriceStatic(
                                (int)$category_product['id_product'],
                                false,
                                null,
                                2
                            );
                        }
                    } else {
                        unset($category_products[$key]);
                    }
                }

                $presenterFactory = new ProductPresenterFactory($this->context);
                $presentationSettings = $presenterFactory->getPresentationSettings();
                $presenter = new ProductListingPresenter(
                    new ImageRetriever(
                        $this->context->link
                    ),
                    $this->context->link,
                    new PriceFormatter(),
                    new ProductColorsRetriever(),
                    $this->context->getTranslator()
                );

                if (is_array($category_products)) {
                    foreach ($category_products as &$product) {
                        $product = $presenter->present(
                            $presentationSettings,
                            Product::getProductProperties($this->context->language->id, $product, $this->context),
                            $this->context->language
                        );
                    }
                    unset($product);
                }
			}

			$this->smarty->assign([
                'categoryProducts' => $category_products,
            ]);
		}

		return $this->display(__FILE__, 'productscategory.tpl', $this->getCacheId($cache_id));
	}

	public function hookHeader($params)
	{
		if (!isset($this->context->controller->php_self) || $this->context->controller->php_self !== 'product') {
            return;
        }

		$this->context->controller->addCSS($this->_path.'css/productscategory.css', 'all');
		$this->context->controller->addJS($this->_path.'js/productscategory.js');
	}

	public function hookAddProduct($params)
	{
		if (!isset($params['product'])) {
            return;
        }

		$this->_clearCache('productscategory.tpl', $this->getCacheId($this->buildCacheKey($params)));
	}

	public function hookUpdateProduct($params)
	{
		if (!isset($params['product'])) {
            return;
        }

		$this->_clearCache('productscategory.tpl', $this->getCacheId($this->buildCacheKey($params)));
	}

	public function hookDeleteProduct($params)
	{
		if (!isset($params['product'])) {
            return;
        }

		$this->_clearCache('productscategory.tpl', $this->getCacheId($this->buildCacheKey($params)));
	}

    /**
     * @param array $params
     *
     * @return string
     */
	protected function buildCacheKey(array $params): string
    {
        $id_product = (int)$params['product']->id;
        $product = $params['product'];

        return 'productscategory|'.$id_product.'|'.(isset($params['category']->id_category) ? (int)$params['category']->id_category : (int)$product->id_category_default);
    }
}
