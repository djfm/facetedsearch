<?php

namespace PrestaShop\FacetedSearch;

use PHPUnit_Framework_TestCase;

use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FiltersTogglerTest extends PHPUnit_Framework_TestCase
{
    private function mockEncodeFacets(array $facets)
    {
        $str = '';
        foreach ($facets as $facet) {
            foreach ($facet->getFilters() as $filter) {
                $str .= $filter->isActive() ? '1' : '0';
            }
        }
        return $str;
    }

    private function getNextEncodedFacets(array $facets)
    {
        $arr = [];
        foreach ($facets as $facet) {
            foreach ($facet->getFilters() as $filter) {
                $arr[] = $filter->getNextEncodedFacets();
            }
        }
        return $arr;
    }

    public function test_forEachToggledFilter_single_facet()
    {
        $f = (new Facet)
            ->setMultipleSelectionAllowed(true)
            ->addFilter(
                (new Filter)->setActive(false)
            )
            ->addFilter(
                (new Filter)->setActive(true)
            )
        ;

        $facets = [$f];

        $toggler = new FiltersToggler;
        $toggler->forEachToggledFilter($facets, function (
            Filter $originalFilter,
            array $facetsWithOriginalFilterToggled
        ) {
            $originalFilter->setNextEncodedFacets(
                $this->mockEncodeFacets($facetsWithOriginalFilterToggled)
            );
        });

        $this->assertEquals(
            ["11", "00"],
            $this->getNextEncodedFacets($facets)
        );
    }

    public function test_forEachToggledFilter_two_facets()
    {
        $f = (new Facet)
            ->setMultipleSelectionAllowed(true)
            ->addFilter(
                (new Filter)->setActive(false)
            )
            ->addFilter(
                (new Filter)->setActive(true)
            )
        ;

        $g = (new Facet)
            ->setMultipleSelectionAllowed(true)
            ->addFilter(
                (new Filter)->setActive(false)
            )
            ->addFilter(
                (new Filter)->setActive(false)
            )
        ;

        $facets = [$f, $g];

        $toggler = new FiltersToggler;
        $toggler->forEachToggledFilter($facets, function (
            Filter $originalFilter,
            array $facetsWithOriginalFilterToggled
        ) {
            $originalFilter->setNextEncodedFacets(
                $this->mockEncodeFacets($facetsWithOriginalFilterToggled)
            );
        });

        $this->assertEquals(
            ["1100", "0000", "0110", "0101"],
            $this->getNextEncodedFacets($facets)
        );
    }

    public function test_forEachToggledFilter_single_facet_no_multiple_selection()
    {
        $f = (new Facet)
            ->setMultipleSelectionAllowed(false)
            ->addFilter(
                (new Filter)->setActive(false)
            )
            ->addFilter(
                (new Filter)->setActive(false)
            )
            ->addFilter(
                (new Filter)->setActive(true)
            )
        ;

        $facets = [$f];

        $toggler = new FiltersToggler;
        $toggler->forEachToggledFilter($facets, function (
            Filter $originalFilter,
            array $facetsWithOriginalFilterToggled
        ) {
            $originalFilter->setNextEncodedFacets(
                $this->mockEncodeFacets($facetsWithOriginalFilterToggled)
            );
        });

        $this->assertEquals(
            ["100", "010", "000"],
            $this->getNextEncodedFacets($facets)
        );
    }
}
