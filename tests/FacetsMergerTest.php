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

    public function test_new_facets_are_appended()
    {
        $this->assertCount(1, $this->merger->merge([], [(new Facet)->setLabel('FacetA')]));
    }

    public function test_old_facets_are_kept()
    {
        $this->assertCount(2, $this->merger->merge(
            [(new Facet)->setLabel('FacetA')],
            [(new Facet)->setLabel('FacetB')])
        );
    }

    public function test_filters_are_copied_to_existing_facets__new_filter()
    {
        $initial = [(new Facet)->setLabel('FacetA')];
        $new = [(new Facet)->setLabel('FacetA')->addFilter((new Filter)->setLabel('a'))];
        $final = $this->merger->merge($initial, $new);
        $this->assertCount(1, $final);
        $this->assertCount(1, $final[0]->getFilters());
    }

    public function test_filters_are_copied_to_existing_facets__common_filter()
    {
        $initial = [(new Facet)->setLabel('FacetA')->addFilter((new Filter)->setLabel('a'))];
        $new = [(new Facet)->setLabel('FacetA')->addFilter((new Filter)->setLabel('a'))];
        $final = $this->merger->merge($initial, $new);
        $this->assertCount(1, $final);
        $this->assertCount(1, $final[0]->getFilters());
    }
}
