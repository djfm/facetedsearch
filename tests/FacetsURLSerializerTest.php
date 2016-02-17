<?php

use PrestaShop\FacetedSearch\FacetsURLSerializer;

class FacetsURLSerializerTest extends PHPUnit_Framework_TestCase
{
    private $serializer;

    public function setup()
    {
        $this->serializer = new FacetsURLSerializer;
    }

    public function test_unserialize_attribute_facet_with_one_filter()
    {
        $facets = $this->serializer->unserialize("attribute-Color-Blue");
        $this->assertCount(1, $facets);

        $facet = $facets[0];
        $this->assertInstanceOf('PrestaShop\PrestaShop\Core\Product\Search\Facet', $facet);
        $this->assertEquals('attribute', $facet->getType());
        $this->assertEquals('Color', $facet->getLabel());
        $this->assertCount(1, $facet->getFilters());
        $this->assertCount(1, $facet->getFilters());
        $filter = $facet->getFilters()[0];
        $this->assertInstanceOf('PrestaShop\PrestaShop\Core\Product\Search\Filter', $filter);
        $this->assertEquals('Blue', $filter->getValue());
        $this->assertTrue($filter->isActive());
    }
}
