<?php

use PrestaShop\FacetedSearch\FacetsMerger;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FacetsMergerTest extends PHPUnit_Framework_TestCase
{
    private $merger;

    public function setup()
    {
        $this->merger = new FacetsMerger;
    }

    public function test_mergeFilters()
    {
        $targetFacets = [
            (new Facet)
                ->setLabel("FacetA"),
            (new Facet)
                ->setLabel("FacetB")
        ];

        $sourceFacets = [
            (new Facet)
                ->setLabel("FacetA")
                ->addFilter((new Filter)->setLabel("FacetAFilterA")->setActive(true))
                ->addFilter((new Filter)->setLabel("FacetAFilterB")->setActive(false)),
            (new Facet)
                ->setLabel("FacetB")
                ->addFilter((new Filter)->setLabel("FacetBFilterA")->setActive(true))
                ->addFilter((new Filter)->setLabel("FacetBFilterB")->setActive(true))
        ];

        $this->merger->mergeFilters($targetFacets, $sourceFacets);

        $this->assertEquals($targetFacets, $sourceFacets);
    }
}
