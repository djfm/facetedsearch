<?php

namespace PrestaShop\FacetedSearch\FacetDriver;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use Db;

abstract class AbstractFacetDriver
{
    protected $qb;
    protected $context;
    protected $db;

    public function __construct(
        QueryBuilder $qb,
        ProductSearchContext $context,
        Db $db
    ) {
        $this->qb = $qb;
        $this->context = $context;
        $this->db = $db;
    }
}
