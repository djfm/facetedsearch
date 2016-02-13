<?php

class AttributeFacetDriver
{
    public function getLabelsOfAvailableFacetsQueryBuilder()
    {
        $qb = new QueryBuilder;

        return $qb
            ->select(
                $qb->field("agl", "name")->alias("label")
            )
            -> from(
                $qb->table("p")
            )
            ->innerJoin(
                $qb->table("product_attribute_shop")->alias("pa"),
                $qb->equal(
                    $qb->field("pa", "id_product"),
                    $qb->table("p", "id_product")
                )
            )
            ->innerJoin(
                $qb->table("product_attribute_combination")->alias("pac"),
                $qb->equal(
                    $qb->field("pac", "id_product_attribute"),
                    $qb->field("pa", "id_product_attribute")
                )
            )
            ->innerJoin(
                $qb->table("attribute")->alias("a"),
                $qb->equal(
                    $qb->field("a", "id_attribute"),
                    $qb->field("pac", "id_attribute")
                )
            )
            ->innerJoin(
                $qb->table("attribute_group")->alias("ag"),
                $qb->equal(
                    $qb->field("ag", "id_attribute_group"),
                    $qb->field("a", "id_attribute_group")
                )
            )
            ->innerJoin(
                $qb->table("attribute_group_lang")->alias("agl"),
                $qb->and(
                    $qb->equal(
                        $qb->field("agl", "id_attribute_group"),
                        $qb->field("ag", "id_attribute_group")
                    ),
                    $qb->equal(
                        $qb->field("agl", "id_lang"),
                        $this->id_lang
                    )
                )
            )
            ->groupBy($this->field("ag", "id_attribute_group"))
            ->orderBy($this->field("ag", "position"), "ASC")
        ;
    }

    public function getFiltersAndMagnitudesQueryBuilder($facetLabel)
    {
        return $this
            ->getLabelsOfAvailableFacetsQueryBuilder()
            ->replaceSelect(
                $qb->field("ag", "name")->as("label")
            )
            ->select(
                $qb->count(
                    $qb->distinct($qb->field("p", "id_product"))
                )->as("magnitude"))
            ->innerJoin(
                $qb->table("attribute_lang")->as("al"),
                $qb->and(
                    $qb->equal($qb->field("id_attribute"), $qb->table("a")),
                    $qb->equal($qb->field("id_lang"), $this->id_lang)
                )
            )
            ->where($qb->equal(
                $qb->table($qb->field("agl", "name")), $facetLabel
            ))
        ;
    }
}
