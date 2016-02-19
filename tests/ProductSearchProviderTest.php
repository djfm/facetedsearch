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

    public function dataProvider_for_test_search_in_category_returns_additional_filters_with_correct_magnitude()
    {
        return [
            [2, null, ['Color' => [
                'Beige' => 1,
                'White' => 2,
                'Black' => 2,
                'Orange' => 3,
                'Blue' => 2,
                'Green' => 1,
                'Yellow' => 3
            ]]],
            [2, null, ['Styles' => [
                'Casual' => 3,
                'Dressy' => 1,
                'Girly' => 3
            ]]]
        ];
    }

    /**
     * @dataProvider dataProvider_for_test_search_in_category_returns_additional_filters_with_correct_magnitude
     */
    public function test_search_in_category_returns_additional_filters_with_correct_magnitude(
        $id_category,
        $encodedFacets,
        $expectedMagnitudes
    ) {
        $query = (new ProductSearchQuery)
            ->setQueryType('category')
            ->setIdCategory($id_category)
            ->setEncodedFacets($encodedFacets)
        ;

        $result = $this
            ->getProductSearchProvider($query)
            ->runQuery($this->getProductSearchContext(), $query)
        ;

        $this->assertNotNull(
            $result->getFacetCollection(),
            'SearchProvider did not return a facetCollection.'
        );

        $this->assertGreaterThanOrEqual(
            count($expectedMagnitudes),
            count($result->getFacetCollection()->getFacets())
        );

        foreach ($expectedMagnitudes as $facetLabel => $filtersLabelsAndMagnitudes) {
            $facet = $this->getFacetByLabel($result->getFacetCollection()->getFacets(), $facetLabel);
            foreach ($filtersLabelsAndMagnitudes as $filterLabel => $magnitude) {
                $filter = $this->getFilterByLabel($facet, $filterLabel);
                $this->assertEquals(
                    $magnitude,
                    $filter->getMagnitude(),
                    sprintf(
                        'Wrong magnitude for `%1$s` filter of the `%2$s` facet.',
                        $filterLabel, $facetLabel
                    )
                );
            }
        }
    }

    private function getFacetByLabel(array $facets, $label)
    {
        foreach ($facets as $facet) {
            if ($facet->getLabel() === $label) {
                return $facet;
            }
        }

        throw new Exception(sprintf(
            'Could not find a facet labelled `%1$s` in the provided list of `%2$s` facets.',
            $label, count($facets)
        ));
    }

    private function getFilterByLabel(Facet $facet, $label)
    {
        foreach ($facet->getFilters() as $filter) {
            if ($filter->getLabel() === $label) {
                return $filter;
            }
        }

        throw new Exception(sprintf(
            'Could not find a filter labelled `%1$s` in the `%2$s` facet containing `%3$s` filters (%4$s).',
            $label, $facet->getLabel(), count($facet->getFilters()), implode(", ", array_map(function (Filter $f) {
                return $f->getLabel();
            }, $facet->getFilters()))
        ));
    }
}
