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
}
