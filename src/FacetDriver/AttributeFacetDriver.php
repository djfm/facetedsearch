<?php

namespace PrestaShop\FacetedSearch\FacetDriver;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class AttributeFacetDriver extends AbstractFacetDriver
{
    private function getBaseQueryBuilder()
    {
        return $this->qb
            ->innerJoin(
                $this->qb->table("product_attribute_shop")->alias("pas"),
                $this->qb->both(
                    $this->qb->equal(
                        $this->qb->field("pas", "id_product"),
                        $this->qb->field("p", "id_product")->noSuffix()
                    ),
                    $this->qb->equal(
                        $this->qb->field("pas", "id_shop"),
                        $this->qb->value($this->context->getIdShop())
                    )
                )
            )
            ->innerJoin(
                $this->qb->table("product_attribute_combination")->alias("pac"),
                $this->qb->equal(
                    $this->qb->field("pac", "id_product_attribute"),
                    $this->qb->field("pas", "id_product_attribute")
                )
            )
            ->innerJoin(
                $this->qb->table("attribute")->alias("a"),
                $this->qb->equal(
                    $this->qb->field("a", "id_attribute"),
                    $this->qb->field("pac", "id_attribute")
                )
            )
            ->innerJoin(
                $this->qb->table("attribute_group_lang")->alias("agl"),
                $this->qb->both(
                    $this->qb->equal(
                        $this->qb->field("agl", "id_attribute_group"),
                        $this->qb->field("a", "id_attribute_group")
                    ),
                    $this->qb->equal(
                        $this->qb->field("agl", "id_lang"),
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
                $this->qb->table("attribute_lang")->alias("al"),
                $this->qb->both(
                    $this->qb->equal(
                        $this->qb->field("al", "id_attribute"),
                        $this->qb->field("a", "id_attribute")
                    ),
                    $this->qb->equal(
                        $this->qb->field("al", "id_lang"),
                        $this->qb->value($this->context->getIdLang())
                    )
                )
            )
            ->where(
                $this->qb->equal(
                    $this->qb->field("agl", "name"),
                    $this->qb->value($facet->getLabel())
                )
            )
            ->andWhere(
                $this->qb->any(
                    array_map(function (Filter $filter) {
                        return $this->qb->equal(
                            $this->qb->field("al", "name"),
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
                ->select($this->qb->field("agl", "name")->alias("label"))
                ->groupBy($this->qb->field("agl", "name"))
        );

        foreach ($this->db->executeS($qb->getSQL()) as $row) {
            $availableFacets[] = (new Facet)
                ->setType('attribute')
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
                    $this->qb->table("attribute_lang")->alias("al"),
                    $this->qb->both(
                        $this->qb->equal(
                            $this->qb->field("al", "id_attribute"),
                            $this->qb->field("a", "id_attribute")
                        ),
                        $this->qb->equal(
                            $this->qb->field("al", "id_lang"),
                            $this->qb->value($this->context->getIdLang())
                        )
                    )
                )
                ->where(
                    $this->qb->equal(
                        $this->qb->field("agl", "name"),
                        $this->qb->value($facet->getLabel())
                    )
                )
                ->groupBy(
                    $this->qb->field("al", "name")
                )
                ->select(
                    $this->qb->field("al", "name")->alias("label")
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
                    ->setType("attribute")
                    ->setLabel($row["label"])
                    ->setMagnitude((int)$row["magnitude"])
                    ->setActive($this->isFilterActive($facet, $row["label"]))
            );
        }
        return $newFacet;
    }

    private function isFilterActive(Facet $facet, $filterLabel) {
        foreach ($facet->getFilters() as $facetFilter) {
            if ($facetFilter->getLabel() === $filterLabel) {
                return $facetFilter->isActive();
            }
        }
        return false;
    }
}
