<?php

namespace PrestaShop\FacetedSearch;

class FacetsMerger
{
    public function merge(array $facets, array $newFacets)
    {
        $mergedFacets = [];
        foreach ($newFacets as $newFacet) {
            foreach ($facets as $key => $facet) {
                if ($facet->getLabel() === $newFacet->getLabel()) {
                    unset($facets[$key]);
                    $merged = clone $facet;
                    $mergedFacets[] = $merged;
                    foreach ($newFacet->getFilters() as $newFilter) {
                        foreach ($merged->getFilters() as &$filter) {
                            if ($filter->getLabel() === $newFilter->getLabel()) {
                                $filter = clone $newFilter;
                                continue 2;
                            }
                        }
                        $merged->addFilter(clone $newFilter);
                        unset($filter);
                    }
                    continue 2;
                }
            }
            $mergedFacets[] = clone $newFacet;
        }

        foreach ($facets as $originalFacet) {
            array_unshift($mergedFacets, clone $originalFacet);
        }

        return $mergedFacets;
    }
}
