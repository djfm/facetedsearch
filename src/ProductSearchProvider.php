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
        $id_shop     = (int)$context->getIdShop();

        $sql = "SELECT
            count(DISTINCT p.id_product)
            FROM {$prefix}product_shop p
            INNER JOIN {$prefix}category_product cp
                ON cp.id_product = p.id_product
            WHERE cp.id_category = $id_category
            AND p.id_shop = $id_shop
        ";

        $count = $this->db->getValue($sql);

        $result->setTotalProductsCount($count);

        return $result;
    }
}
