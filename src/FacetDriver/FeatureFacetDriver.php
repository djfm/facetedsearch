<?php

namespace PrestaShop\FacetedSearch\FacetDriver;

use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class FeatureFacetDriver extends AbstractFacetDriver
{
    public function getQueryBuilderForMainQuery(Facet $facet)
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
                            $this->qb->value($filter->getValue())
                        );
                    }, $facet->getFilters())
                )
            )
        ;
    }
}
