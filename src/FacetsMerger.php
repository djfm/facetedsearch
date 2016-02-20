<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;
use ReflectionClass;


class FacetsMerger
{
    private function copyFacetFilters(Facet $target, Facet $source)
    {
        // FIXME: dirty hack, but don't want to touch the Core Facet class at the moment
        // -- what it needs is a setFilters() method.
        $refl = new ReflectionClass($target);
        $filters = $refl->getProperty("filters");
        $filters->setAccessible(true);
        $filters->setValue($target, $source->getFilters());
    }

    public function mergeFilters(array $targetFacets, array $sourceFacets)
    {
        foreach ($targetFacets as $targetFacet) {
            foreach ($sourceFacets as $sourceFacet) {
                if ($targetFacet->getLabel() === $sourceFacet->getLabel()) {
                    $this->copyFacetFilters(
                        $targetFacet,
                        $sourceFacet
                    );
                    continue 2;
                }
            }
        }

        return $targetFacets;
    }
}
