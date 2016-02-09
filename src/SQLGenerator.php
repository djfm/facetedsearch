<?php

namespace PrestaShop\FacetedSearch;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\Facet;
use PrestaShop\PrestaShop\Core\Product\Search\Filter;

class SQLGenerator
{
    private $prefix;
    private $id_lang;
    private $escaper;

    public function __construct($prefix, ProductSearchContext $context, callable $escaper)
    {
        $this->prefix = $prefix;
        $this->id_lang = (int)$context->getIdLang();
        $this->escaper = $escaper;
    }

    private function safeString($string)
    {
        $escape = $this->escaper;
        return "'" . $escape($string) . "'";
    }

    public function getJoinsForAttributeFacet($facetIndex)
    {
        return "INNER JOIN {$this->prefix}product_attribute_shop pa{$facetIndex}
                    ON pa{$facetIndex}.id_product = p.id_product AND pa{$facetIndex}.id_shop = p.id_shop
                INNER JOIN {$this->prefix}product_attribute_combination ac{$facetIndex}
                    ON ac{$facetIndex}.id_product_attribute = pa{$facetIndex}.id_product_attribute
                INNER JOIN {$this->prefix}attribute a{$facetIndex}
                    ON a{$facetIndex}.id_attribute = ac{$facetIndex}.id_attribute
                INNER JOIN {$this->prefix}attribute_lang al{$facetIndex}
                    ON al{$facetIndex}.id_attribute = a{$facetIndex}.id_attribute
                    AND al{$facetIndex}.id_lang = {$this->id_lang}
                INNER JOIN {$this->prefix}attribute_group_lang agl{$facetIndex}
                    ON agl{$facetIndex}.id_attribute_group = a{$facetIndex}.id_attribute_group
                    AND agl{$facetIndex}.id_lang = {$this->id_lang}
        ";
    }

    public function getFilterConditionForAttributeFacet($facetIndex, Facet $facet, Filter $filter)
    {
        $label = $this->safeString($facet->getLabel());
        $value = $this->safeString($filter->getValue());

        return "agl{$facetIndex}.name = $label AND al{$facetIndex}.name = $value";
    }

    public function buildQueryForAvailableAttributeFacets($facetIndex, QueryBuilder $qb)
    {
        return $qb
            ->select("agl{$facetIndex}.name as label")
            ->from("INNER JOIN {$this->prefix}product_attribute_shop pa{$facetIndex}
                        ON pa{$facetIndex}.id_product = p.id_product AND pa{$facetIndex}.id_shop = p.id_shop
                    INNER JOIN {$this->prefix}product_attribute_combination ac{$facetIndex}
                        ON ac{$facetIndex}.id_product_attribute = pa{$facetIndex}.id_product_attribute
                    INNER JOIN {$this->prefix}attribute a{$facetIndex}
                        ON a{$facetIndex}.id_attribute = ac{$facetIndex}.id_attribute
                    INNER JOIN {$this->prefix}attribute_group ag{$facetIndex}
                        ON  ag{$facetIndex}.id_attribute_group = a{$facetIndex}.id_attribute_group
                    INNER JOIN {$this->prefix}attribute_group_lang agl{$facetIndex}
                        ON agl{$facetIndex}.id_attribute_group = ag{$facetIndex}.id_attribute_group
                        AND agl{$facetIndex}.id_lang = {$this->id_lang}
            ")
            ->groupBy("ag{$facetIndex}.id_attribute_group")
            ->orderBy("ag{$facetIndex}.position ASC")
        ;
    }

    public function buildQueryForAttributeFacetFiltersAndMagnitudes(
        $facetIndex,
        Facet $facet,
        QueryBuilder $qb
    ) {
        return $qb
            ->select("al{$facetIndex}.name as label, COUNT(DISTINCT p.id_product) as magnitude", true)
            ->from("INNER JOIN {$this->prefix}product_attribute_shop pa{$facetIndex}
                        ON pa{$facetIndex}.id_product = p.id_product AND pa{$facetIndex}.id_shop = p.id_shop
                    INNER JOIN {$this->prefix}product_attribute_combination ac{$facetIndex}
                        ON ac{$facetIndex}.id_product_attribute = pa{$facetIndex}.id_product_attribute
                    INNER JOIN {$this->prefix}attribute a{$facetIndex}
                        ON a{$facetIndex}.id_attribute = ac{$facetIndex}.id_attribute
                    INNER JOIN {$this->prefix}attribute_group ag{$facetIndex}
                        ON  ag{$facetIndex}.id_attribute_group = a{$facetIndex}.id_attribute_group
                    INNER JOIN {$this->prefix}attribute_group_lang agl{$facetIndex}
                        ON agl{$facetIndex}.id_attribute_group = ag{$facetIndex}.id_attribute_group
                        AND agl{$facetIndex}.id_lang = {$this->id_lang}
                    INNER JOIN {$this->prefix}attribute_lang al{$facetIndex}
                        ON al{$facetIndex}.id_attribute = a{$facetIndex}.id_attribute
                        AND al{$facetIndex}.id_lang = {$this->id_lang}
            ")
            ->where("agl{$facetIndex}.name = " . $this->safeString($facet->getLabel()))
            ->groupBy("al{$facetIndex}.id_attribute")
            ->orderBy("magnitude DESC")
        ;
    }

    public function getJoinsForFeatureFacet($facetIndex)
    {
        return "INNER JOIN {$this->prefix}feature_product fp{$facetIndex}
                    ON fp{$facetIndex}.id_product = p.id_product
                INNER JOIN {$this->prefix}feature_lang fl{$facetIndex}
                    ON fl{$facetIndex}.id_feature = fp{$facetIndex}.id_feature
                    AND fl{$facetIndex}.id_lang = {$this->id_lang}
                INNER JOIN {$this->prefix}feature_value_lang fvl{$facetIndex}
                    ON fvl{$facetIndex}.id_feature_value = fp{$facetIndex}.id_feature_value
                    AND fvl{$facetIndex}.id_lang = {$this->id_lang}
        ";
    }

    public function getFilterConditionForFeatureFacet($facetIndex, Facet $facet, Filter $filter)
    {
        $label = $this->safeString($facet->getLabel());
        $value = $this->safeString($filter->getValue());

        return "fl{$facetIndex}.name = $label AND fvl{$facetIndex}.value = $value";
    }
}
