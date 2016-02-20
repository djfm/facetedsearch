<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FiltersToggler
{
    private function backupActiveStates(Facet $facet)
    {
        return array_map(function (Filter $f) {
            return $f->isActive();
        }, $facet->getFilters());
    }

    private function restoreActiveStates(Facet $facet, array $activeStates)
    {
        foreach ($facet->getFilters() as $key => $filter) {
            $filter->setActive($activeStates[$key]);
        }
        return $facet;
    }

    public function forEachToggledFilter(array $facets, callable $cb)
    {
         foreach ($facets as $facet) {
             foreach ($facet->getFilters() as $originalFilterKey => $filter) {
                $activeStates = $this->backupActiveStates($facet);

                if ($facet->isMultipleSelectionAllowed()) {
                    $filter->setActive(!$filter->isActive());
                } else {
                    $wasActive = $filter->isActive();
                    foreach ($facet->getFilters() as $key => $filterToToggle) {
                        if ($key === $originalFilterKey) {
                            $filterToToggle->setActive(!$wasActive);
                        } else if (!$wasActive) {
                            $filterToToggle->setActive(false);
                        }
                    }
                }
                $cb($filter, $facets);
                $this->restoreActiveStates($facet, $activeStates);
             }
         }
    }
}
