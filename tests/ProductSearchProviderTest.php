<?php

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class ProductSearchProviderTest extends PHPUnit_Framework_TestCase
{
    private $module;

    public function setup()
    {
        $this->module = Module::getInstanceByName('facetedsearch');
    }

    private function getProductSearchProvider(ProductSearchQuery $query)
    {
        return $this->module->hookProductSearchProvider(['query' => $query]);
    }

    private function getProductSearchContext()
    {
        Context::getContext()->currency = Currency::getDefaultCurrency();
        return new ProductSearchContext(Context::getContext());
    }

    public function test_module_responds_to_hookProductSearchProvider()
    {
        $this->assertInstanceOf(
            'PrestaShop\FacetedSearch\ProductSearchProvider',
            $this->module->hookProductSearchProvider([])
        );
    }

    public function dataProvider_for_test_search_products_in_category_has_correct_total_count()
    {
        return [
            [2, null, 7],
            [4, null, 2],
            [2, 'attribute-Color-Blue', 2],
            [2, 'attribute-Color-Blue-Green', 3],
            [2, 'feature-Styles-Dressy', 1]
        ];
    }

    /**
     * @dataProvider dataProvider_for_test_search_products_in_category_has_correct_total_count
     */
    public function test_search_products_in_category_has_correct_total_count(
        $id_category,
        $encodedFacets,
        $expected_count
    ) {
        $query = (new ProductSearchQuery)
            ->setQueryType('category')
            ->setIdCategory($id_category)
            ->setEncodedFacets($encodedFacets)
        ;

        $this->assertEquals(
            $expected_count,
            $this
                ->getProductSearchProvider($query)
                ->runQuery($this->getProductSearchContext(), $query)
                ->getTotalProductsCount()
        );
    }
}
