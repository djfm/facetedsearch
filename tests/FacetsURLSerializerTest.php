<?php

use PrestaShop\FacetedSearch\FacetsURLSerializer;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FacetsURLSerializerTest extends PHPUnit_Framework_TestCase
{
    private $serializer;

    public function setup()
    {
        $this->serializer = new FacetsURLSerializer;
    }

    public function test_unserialize_facet_with_one_filter()
    {
        $facets = $this->serializer->unserialize("Color-Blue");
        $this->assertCount(1, $facets);

        $facet = $facets[0];
        $this->assertInstanceOf('PrestaShop\PrestaShop\Core\Product\Search\Facet', $facet);
        $this->assertEquals('Color', $facet->getLabel());
        $this->assertCount(1, $facet->getFilters());
        $this->assertCount(1, $facet->getFilters());
        $filter = $facet->getFilters()[0];
        $this->assertInstanceOf('PrestaShop\PrestaShop\Core\Product\Search\Filter', $filter);
        $this->assertEquals('Blue', $filter->getLabel());
        $this->assertTrue($filter->isActive());
    }

    public function test_serialize_facet()
    {
        $facets = [
            (new Facet)
                ->setLabel("FacetA")
                ->addFilter(
                    (new Filter)->setLabel("filterAA")->setActive(true)
                )
                ->addFilter(
                    (new Filter)->setLabel("filterAB")->setActive(true)
                ),
            (new Facet)
                ->setLabel("FacetB")
                ->addFilter(
                    (new Filter)->setLabel("filterBA")->setActive(false)
                )
                ->addFilter(
                    (new Filter)->setLabel("filterBB")->setActive(true)
                )

        ];

        $this->assertEquals(
            "FacetA-filterAA-filterAB/FacetB-filterBB",
            $this->serializer->serialize($facets)
        );
    }
}
