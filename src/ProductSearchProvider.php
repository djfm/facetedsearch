<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use Db;

class ProductSearchProvider implements ProductSearchProviderInterface
{
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $result = new ProductSearchResult;

        $prefix      = $this->db->getPrefix();
        $id_category = (int)$query->getIdCategory();

        $sql = "SELECT count(*) FROM {$prefix}category_product WHERE id_category = $id_category";
        $count = $this->db->getValue($sql);

        $result->setTotalProductsCount($count);

        return $result;
    }
}
