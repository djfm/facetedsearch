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

    public function dataProvider_for_test_search_products_in_category_correct_count()
    {
        return [
            [2, 7]
        ];
    }

    public function dataProvider_for_test_search_products_in_category_with_attribute_correct_count()
    {
        return [
            [2, '@-Color-Blue', 2],
            [2, '@-Color-Blue-Green', 3]
        ];
    }

    /**
     * @dataProvider dataProvider_for_test_search_products_in_category_correct_count
     */
    public function test_search_products_in_category_correct_count($id_category, $expected_count)
    {
        $query = (new ProductSearchQuery)
            ->setQueryType('category')
            ->setIdCategory($id_category)
        ;

        $result = $this
            ->getProductSearchProvider($query)
            ->runQuery($this->getProductSearchContext(), $query)
        ;

        $this->assertEquals($expected_count, $result->getTotalProductsCount());
    }

    /**
     * @dataProvider dataProvider_for_test_search_products_in_category_with_attribute_correct_count
     */
    public function test_search_products_in_category_with_facets_count(
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
