<?php

namespace PrestaShop\FacetedSearch\FacetDriver;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FeatureFacetDriver extends AbstractFacetDriver
{
    private function getBaseQueryBuilder()
    {
        return $this->qb
            ->innerJoin(
                $this->qb->table("feature_product")->alias("fp"),
                $this->qb->equal(
                    $this->qb->field("fp", "id_product"),
                    $this->qb->field("p", "id_product")->noSuffix()
                )
            )
            ->innerJoin(
                $this->qb->table("feature_lang")->alias("fl"),
                $this->qb->both(
                    $this->qb->equal(
                        $this->qb->field("fl", "id_feature"),
                        $this->qb->field("fp", "id_feature")
                    ),
                    $this->qb->equal(
                        $this->qb->field("fl", "id_lang"),
                        $this->qb->value($this->context->getIdLang())
                    )
                )
            )
        ;
    }

    public function getQueryBuilderForMainQuery(Facet $facet)
    {
        return $this
            ->getBaseQueryBuilder()
            ->innerJoin(
                $this->qb->table("feature_value_lang")->alias("fvl"),
                $this->qb->both(
                    $this->qb->equal(
                        $this->qb->field("fvl", "id_feature_value"),
                        $this->qb->field("fp", "id_feature_value")
                    ),
                    $this->qb->equal(
                        $this->qb->field("fvl", "id_lang"),
                        $this->qb->value($this->context->getIdLang())
                    )
                )
            )
            ->where(
                $this->qb->equal(
                    $this->qb->field("fl", "name"),
                    $this->qb->value($facet->getLabel())
                )
            )
            ->andWhere(
                $this->qb->any(
                    array_map(function (Filter $filter) {
                        return $this->qb->equal(
                            $this->qb->field("fvl", "value"),
                            $this->qb->value($filter->getLabel())
                        );
                    }, $facet->getFilters())
                )
            )
        ;
    }

    public function getAvailableFacets(QueryBuilder $baseQuery)
    {
        $availableFacets = [];

        $qb = $baseQuery->merge(
            $this
                ->getBaseQueryBuilder()
                ->select($this->qb->field("fl", "name")->alias("label"))
                ->groupBy($this->qb->field("fl", "name"))
        );

        foreach ($this->db->executeS($qb->getSQL()) as $row) {
            $availableFacets[] = (new Facet)
                ->setType('feature')
                ->setLabel($row['label'])
            ;
        }

        return $availableFacets;
    }

    public function updateFacet(QueryBuilder $constrainedQuery, Facet $facet)
    {
        $qb = $constrainedQuery
            ->merge($this
                ->getBaseQueryBuilder()
                ->innerJoin(
                    $this->qb->table("feature_value_lang")->alias("fvl"),
                    $this->qb->both(
                        $this->qb->equal(
                            $this->qb->field("fvl", "id_feature_value"),
                            $this->qb->field("fp", "id_feature_value")
                        ),
                        $this->qb->equal(
                            $this->qb->field("fvl", "id_lang"),
                            $this->qb->value($this->context->getIdLang())
                        )
                    )
                )
                ->where(
                    $this->qb->equal(
                        $this->qb->field("fl", "name"),
                        $this->qb->value($facet->getLabel())
                    )
                )
                ->groupBy(
                    $this->qb->field("fvl", "value")
                )
                ->select(
                    $this->qb->field("fvl", "value")->alias("label")
                )
                ->select(
                    $this->qb->count(
                        $this->qb->distinct(
                            $this->qb->field("p", "id_product")->noSuffix()
                        )
                    )->alias("magnitude")
                )
            )
        ;

        $newFacet = (new Facet)->setLabel($facet->getLabel());
        foreach ($this->db->executeS($qb->getSQL()) as $row) {
            $newFacet->addFilter(
                (new Filter)
                    ->setType("feature")
                    ->setLabel($row["label"])
                    ->setMagnitude((int)$row["magnitude"])
            );
        }
        return $newFacet;
    }
}
