<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Value implements ExpressionInterface
{
    private $value;
    private $escaper;

    public function __construct($value, ValueEscaperInterface $escaper)
    {
        $this->value    = $value;
        $this->escaper  = $escaper;
    }

    public function getSQL()
    {
        switch (gettype($this->value)) {
            case "boolean":
                return (int)$this->value;
            case "integer":
            case "double":
                return $this->value;
            case "string":
                return "'" . $this->escaper->escapeString($this->value) . "'";
            default:
                return "NULL";
        }
    }
}
