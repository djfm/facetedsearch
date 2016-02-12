<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class OrderBy
{
    private $expression;
    private $direction;

    public function __construct(ExpressionInterface $expression, $direction = null)
    {
        $this->expression = $expression;
        $this->direction  = $direction;
    }

    public function getSQL()
    {
        return $this->expression->getSQL() . ($this->direction ? " " . $this->direction : "");
    }
}
