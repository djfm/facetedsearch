<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\FacetedSearch\QueryBuilder\QueryBuilder;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;

class AttributeFacetDriver
{
    private $qb;
    private $context;

    public function __construct(
        QueryBuilder $qb,
        ProductSearchContext $context
    ) {
        $this->qb = $qb;
        $this->context = $context;
    }

    public function getQueryBuilderForMainQuery(Facet $facet)
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
                            $this->qb->value($filter->getValue())
                        );
                    }, $facet->getFilters())
                )
            )
        ;
    }
}
