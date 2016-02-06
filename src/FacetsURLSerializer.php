<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\URLFragmentSerializer;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FacetsURLSerializer
{
    private function decodeFacetType($type)
    {
        if ($type === '@') {
            return 'attribute';
        }

        return $type;
    }

    public function unserialize($facetsAsString)
    {
        $urlFragmentSerializer = new URLFragmentSerializer;

        $facetsAsArray = $urlFragmentSerializer->unserialize($facetsAsString);

        $facets = [];
        foreach ($facetsAsArray as $encodedType => $data) {
            $facet = new Facet;

            $facet
                ->setType($this->decodeFacetType($encodedType))
                ->setLabel(array_shift($data))
            ;

            foreach ($data as $filterValue) {
                $filter = new Filter;
                $filter->setValue($filterValue);
                $facet->addFilter($filter);
            }

            $facets[] = $facet;
        }

        return $facets;
    }
}
