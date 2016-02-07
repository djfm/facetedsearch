<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;
use Db;

class ProductSearchProvider implements ProductSearchProviderInterface
{
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    private function getSQLGenerator(ProductSearchContext $context)
    {
        return new SQLGenerator($this->db->getPrefix(), $context, function ($string) {
            return $this->db->escape($string);
        });
    }

    private function generateCountSQL(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $prefix = $this->db->getPrefix();
        $id_shop = (int)$context->getIdShop();

        $qb = new QueryBuilder;

        $qb
            ->select("count(DISTINCT p.id_product)")
            ->from("{$prefix}product_shop p")
            ->where("p.id_shop = $id_shop")
        ;

        if ($query->getQueryType() === 'category') {
            $id_category = (int)$query->getIdCategory();
            $qb
                ->innerJoin("{$prefix}category_product cp ON cp.id_product = p.id_product")
                ->where("cp.id_category = $id_category")
            ;
        }

        $sqlGenerator = $this->getSQLGenerator($context);

        $facets = (new FacetsURLSerializer)->unserialize($query->getEncodedFacets());
        foreach ($facets as $facetIndex => $facet) {

            $facetType = $facet->getType();

            if (in_array($facetType, ["attribute", "feature"])) {
                $qb->from($sqlGenerator->{"getJoinsFor{$facetType}Facet"}($facetIndex));
                $qb->where(implode(" OR ", array_map(
                    function (Filter $filter) use (
                        $sqlGenerator,
                        $facetIndex,
                        $facet,
                        $facetType
                    ) {
                        $condition = $sqlGenerator
                            ->{"getFilterConditionFor{$facetType}Facet"}(
                                $facetIndex,
                                $facet,
                                $filter
                            )
                        ;
                        return "($condition)";
                    },
                    $facet->getFilters()))
                );
            }
        }

        return $qb->getSQL();
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $result = new ProductSearchResult;

        $sql = $this->generateCountSQL($context, $query);

        $count = $this->db->getValue($sql);

        $result->setTotalProductsCount($count);

        return $result;
    }
}
