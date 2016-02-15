<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\FacetCollection;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;
use Db;

class ProductSearchProvider implements ProductSearchProviderInterface
{
    private $db;
    private $qb;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->qb = (new QueryBuilder(
            new ValueEscaper($db)
        ))->setTablePrefix(
            $this->db->getPrefix()
        );
    }

    private function getRootCondition(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        if ($query->getQueryType() === 'category') {
            return $this->qb
                ->innerJoin(
                    $this->qb->table("category_product")->alias("cp"),
                    $this->qb->equal(
                        $this->qb->field("cp", "id_product"),
                        $this->qb->field("p", "id_product")
                    )
                )
                ->andWhere(
                    $this->qb->equal(
                        $this->qb->field("cp", "id_category"),
                        $this->qb->value((int)$query->getIdCategory())
                    )
                )
            ;
        }
    }

    private function getBaseQueryBuilder(ProductSearchContext $context, ProductSearchQuery $query)
    {
        return $this->qb
            ->from(
                $this->qb->table("product_shop")->alias("p")
            )
            ->andWhere(
                $this->qb->equal(
                    $this->qb->field("p", "id_shop"),
                    $this->qb->value((int)$context->getIdShop())
                )
            )
            ->merge(
                $this->getRootCondition($context, $query)
            )
        ;
    }

    private function getFacetDriver($facetType)
    {
        $className = 'PrestaShop\\FacetedSearch\\' . ucfirst($facetType) . 'FacetDriver';
        return new $className;
    }

    private function generateCountSQL(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $qb = $this->getBaseQueryBuilder($context, $query);

        /*
        $facets = (new FacetsURLSerializer)->unserialize($query->getEncodedFacets());

        foreach ($facets as $facetIndex => $facet) {
            $facetType = $facet->getType();
            $driver    = $this->getFacetDriver($facetType);
        }*/

        return $qb->select(
            $qb->count($qb->distinct($qb->field("p", "id_product")))
        )->getSQL();
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $result = new ProductSearchResult;

        $sql = $this->generateCountSQL($context, $query);
        print_r($sql);
        $count = $this->db->getValue($sql);
        $result->setTotalProductsCount($count);

        // $this->addFacetsToResult($context, $query, $result);

        return $result;
    }
}
