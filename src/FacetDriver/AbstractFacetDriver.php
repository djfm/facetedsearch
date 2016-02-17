<?php

namespace PrestaShop\FacetedSearch\FacetDriver;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;

abstract class AbstractFacetDriver
{
    protected $qb;
    protected $context;

    public function __construct(
        QueryBuilder $qb,
        ProductSearchContext $context
    ) {
        $this->qb = $qb;
        $this->context = $context;
    }
}
