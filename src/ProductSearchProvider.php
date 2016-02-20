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
use ReflectionClass;

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
        if ($query->getIdCategory()) {
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

    private function getFacetDriver(
        $facetType,
        ProductSearchContext $context
    ) {
        $className = 'PrestaShop\\FacetedSearch\\FacetDriver\\' . ucfirst($facetType) . 'FacetDriver';
        $refl = new ReflectionClass($className);
        return $refl->newInstanceArgs([
            $this->qb,
            $context,
            $this->db
        ]);
    }

    private function getBaseQueryBuilder(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
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

    private function getConstrainedQueryBuilderForFacets(
        ProductSearchContext $context,
        ProductSearchQuery $query,
        array $facets
    ) {
        $qb = $this->getBaseQueryBuilder($context, $query);

        foreach ($facets as $facetIndex => $facet) {
            $driver = $this->getFacetDriver($facet->getType(), $context);
            $facetQb = $driver
                ->getQueryBuilderForMainQuery($facet)
                ->setAliasSuffix(
                    "_" . $facet->getType() . "_" . $facetIndex
                )
            ;
            $qb = $qb->merge($facetQb);
        }

        return $qb;
    }

    private function debugShowFacets(array $facets)
    {
        $labels = [];
        foreach ($facets as $facet) {
            $labels[] = $facet->getLabel();
        }
        echo "\nFacets: (" . implode(", ", $labels) . ")\n";

        return $facets;
    }

    private function getCurrentFacets(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $facetTypes = ['attribute', 'feature'];

        $qb = $this->getBaseQueryBuilder($context, $query);

        $availableFacets = [];

        foreach ($facetTypes as $facetType) {
            $facetDriver = $this->getFacetDriver(
                $facetType,
                $context
            );
            $facetDriver->getAvailableFacets($qb);
            $availableFacets = array_merge(
                $availableFacets,
                $facetDriver->getAvailableFacets($qb)
            );
        }

        $activeFacets = (new FacetsURLSerializer)->unserialize($query->getEncodedFacets());

        (new FacetsMerger)->mergeFilters($availableFacets, $activeFacets);

        return $availableFacets;
    }

    private function getCurrentConstrainedFacets(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $facets = $this->getCurrentFacets($context, $query);
        $constrainedFacets = [];

        foreach ($facets as $facet) {
            foreach ($facet->getFilters() as $filter) {
                if ($filter->isActive()) {
                    $constrainedFacets[] = $facet;
                    continue 2;
                }
            }
        }

        return $constrainedFacets;
    }

    private function getConstrainedQueryBuilder(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $facets = $this->getCurrentConstrainedFacets($context, $query);
        return $this->getConstrainedQueryBuilderForFacets($context, $query, $facets);
    }

    private function generateCountSQL(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $qb = $this->getConstrainedQueryBuilder($context, $query);
        return $qb->select(
            $qb->count($qb->distinct($qb->field("p", "id_product")))
        )->getSQL();
    }

    private function generateSelectSQL(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $qb = $this->getConstrainedQueryBuilder($context, $query);
        return $qb
            ->select(
                $qb->field("p", "id_product")->alias("id_product")
            )
            ->groupBy(
                $qb->field("p", "id_product")
            )
            ->limit(
                $query->getResultsPerPage()
            )
            ->offset(
                $query->getResultsPerPage() * ($query->getPage() - 1)
            )
            ->getSQL()
        ;
    }

    private function mapFacets(array $facets, callable $cb)
    {
        $mapped = [];

        foreach ($facets as $key => $facet) {
            $otherFacets = $facets;
            unset($otherFacets[$key]);
            $mapped[] = $cb($facet, $otherFacets);
        }

        return $mapped;
    }

    private function getUpdatedFacets(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $facets = $this->getCurrentFacets($context, $query);

        return $this->mapFacets($facets, function (
            Facet $facet,
            array $otherFacets
        ) use (
            $context,
            $query
        ) {
            $facetDriver = $this->getFacetDriver(
                $facet->getType(),
                $context
            );

            return $facetDriver->updateFacet(
                $this->getConstrainedQueryBuilderForFacets(
                    $context,
                    $query,
                    $otherFacets
                ),
                clone $facet
            );
        });
    }

    private function addNextEncodedFacetsToFilters(array $facets)
    {
        (new FiltersToggler)->forEachToggledFilter($facets, function (Filter $f, array $facets) {
            $f->setNextEncodedFacets((new FacetsURLSerializer)->serialize($facets));
        });
        return $this;
    }

    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $result = new ProductSearchResult;

        $sql = $this->generateCountSQL($context, $query);
        $count = $this->db->getValue($sql);
        $result->setTotalProductsCount($count);

        $facets = $this->getUpdatedFacets($context, $query);
        $this->addNextEncodedFacetsToFilters($facets);
        $result->setFacetCollection((new FacetCollection)->setFacets($facets));
        $result->setEncodedFacets((new FacetsURLSerializer)->serialize($facets));

        $sql = $this->generateSelectSQL($context, $query);
        $products = $this->db->executeS($sql);
        $result->setProducts($products);

        return $result;
    }
}
