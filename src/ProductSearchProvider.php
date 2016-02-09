<?php

namespace PrestaShop\FacetedSearch;

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

    private function addRootCondition(
        ProductSearchContext $context,
        ProductSearchQuery $query,
        QueryBuilder $qb
    ) {
        $prefix = $this->db->getPrefix();

        if ($query->getQueryType() === 'category') {
            $id_category = (int)$query->getIdCategory();
            $qb
                ->innerJoin("{$prefix}category_product cp ON cp.id_product = p.id_product")
                ->where("cp.id_category = $id_category")
            ;
        }
    }

    private function generateFacetCondition(ProductSearchContext $context, $facetIndex, $facet)
    {
        $sqlGenerator = $this->getSQLGenerator($context);
        return implode(" OR ", array_map(
            function (Filter $filter) use (
                $sqlGenerator,
                $facetIndex,
                $facet
            ) {
                $facetType = $facet->getType();
                $condition = $sqlGenerator
                    ->{"getFilterConditionFor{$facetType}Facet"}(
                        $facetIndex,
                        $facet,
                        $filter
                    )
                ;
                return "($condition)";
            },
            $facet->getFilters())
        );
    }

    private function getBaseQueryBuilder(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $prefix = $this->db->getPrefix();
        $id_shop = (int)$context->getIdShop();

        $qb = new QueryBuilder;

        $qb
            ->select("count(DISTINCT p.id_product)")
            ->from("{$prefix}product_shop p")
            ->where("p.id_shop = $id_shop")
        ;

        $this->addRootCondition($context, $query, $qb);

        return $qb;
    }

    private function generateCountSQL(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $qb = $this->getBaseQueryBuilder($context, $query);

        $sqlGenerator = $this->getSQLGenerator($context);

        $facets = (new FacetsURLSerializer)->unserialize($query->getEncodedFacets());
        foreach ($facets as $facetIndex => $facet) {
            $facetType = $facet->getType();
            if (in_array($facetType, ["attribute", "feature"])) {
                $qb->from($sqlGenerator->{"getJoinsFor{$facetType}Facet"}($facetIndex));
                $qb->where($this->generateFacetCondition($context, $facetIndex, $facet));
            }
        }

        return $qb->getSQL();
    }

    private function getAvailableFacets(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $baseQb = $this->getBaseQueryBuilder($context, $query);
        $sqlGenerator = $this->getSQLGenerator($context);

        $qb = $sqlGenerator->buildQueryForAvailableAttributeFacets("_availableFacets", clone $baseQb);
        $attributeFacets = [];
        foreach ($this->db->executeS($qb->getSQL()) as $row) {
            $attributeFacets[] = (new Facet)
                ->setType('attribute')
                ->setLabel($row['label'])
            ;
        }

        return $attributeFacets;
    }

    private function addFacetsToResult(
        ProductSearchContext $context,
        ProductSearchQuery $query,
        ProductSearchResult $result
    ) {
        $availableFacets = $this->getAvailableFacets($context, $query);
        $queryFacets = (new FacetsURLSerializer)->unserialize($query->getEncodedFacets());
        $facets = (new FacetsMerger)->merge($availableFacets, $queryFacets);

        $sqlGenerator = $this->getSQLGenerator($context);

        foreach ($facets as $facet) {
            $facetType = $facet->getType();
            $qb = $this->getBaseQueryBuilder($context, $query);
            $fetchMagnitudes = false;
            foreach ($facets as $facetIndex => $constrainingFacet) {
                if ($constrainingFacet === $facet) {
                    // select filters, no conditions
                    if ($facetType === "attribute") {
                        $sqlGenerator->buildQueryForAttributeFacetFiltersAndMagnitudes(
                            $facetIndex,
                            $facet,
                            $qb
                        );
                        $fetchMagnitudes = true;
                    }
                } else {
                    // add constraints
                    if (in_array($facetType, ["attribute", "feature"])) {
                        $qb->from($sqlGenerator->{"getJoinsFor{$facetType}Facet"}($facetIndex));
                        $qb->where($this->generateFacetCondition($context, $facetIndex, $facet));
                    }
                }
            }

            if ($fetchMagnitudes) {
                $filters = $this->db->executeS($qb->getSQL());
                foreach ($filters as $filterRow) {
                    $filter = (new Filter)
                        ->setLabel($filterRow['label'])
                        ->setMagnitude((int)$filterRow['magnitude'])
                    ;
                    foreach ($facet->getFilters() as $currentFilter) {
                        if ($filter->getLabel() === $currentFilter->getLabel()) {
                            $currentFilter->setMagnitude($filter->getMagnitude());
                            continue 2;
                        }
                    }
                    $facet->addFilter($filter);
                }
            }
        }

        $facetCollection = (new FacetCollection)->setFacets($facets);
        $result->setFacetCollection($facetCollection);
        return $this;
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $result = new ProductSearchResult;

        $sql = $this->generateCountSQL($context, $query);
        $count = $this->db->getValue($sql);
        $result->setTotalProductsCount($count);

        $this->addFacetsToResult($context, $query, $result);

        return $result;
    }
}
