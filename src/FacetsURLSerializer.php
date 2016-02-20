<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\URLFragmentSerializer;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FacetsURLSerializer
{
    public function serialize(array $facets)
    {
        $urlFragmentSerializer = new URLFragmentSerializer;
        $labels = [];
        foreach ($facets as $facet) {
            foreach ($facet->getFilters() as $filter) {
                if ($filter->isActive()) {
                    $labels[$facet->getLabel()][] = $filter->getLabel();
                }
            }
        }
        return $urlFragmentSerializer->serialize($labels);
    }

    public function unserialize($facetsAsString)
    {
        $urlFragmentSerializer = new URLFragmentSerializer;

        $facetsAsArray = $urlFragmentSerializer->unserialize($facetsAsString);

        $facets = [];
        foreach ($facetsAsArray as $label => $filters) {
            $facet = new Facet;
            $facet
                ->setLabel($label)
            ;

            foreach ($filters as $filterLabel) {
                $filter = new Filter;
                $filter->setLabel($filterLabel)->setActive(true);
                $facet->addFilter($filter);
            }

            $facets[] = $facet;
        }

        return $facets;
    }
}
