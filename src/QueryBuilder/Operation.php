<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Operation extends AbstractMappable implements ExpressionInterface
{
    private $operator;
    private $arguments = [];
    private $fixity;

    public function __construct($operator, $fixity = 'infix')
    {
        $this->operator = $operator;
        $this->fixity = $fixity;
    }

    public function addArgument(ExpressionInterface $arg)
    {
        $op = clone $this;
        $op->arguments[] = $arg;
        return $op;
    }

    public function getSQL()
    {
        if ($this->fixity === 'infix') {
            switch ($this->operator) {
                case "equal":
                    $operator = "=";
                    break;
                case "both":
                    $operator = "AND";
                    break;
                case "either":
                    $operator = "OR";
                    break;
            }

            return "(" . $this->arguments[0]->getSQL() . " $operator " . $this->arguments[1]->getSQL() . ")";
        } else if ($this->fixity === 'prefix'){
            $args = array_map(function (ExpressionInterface $e) {
                return $e->getSQL();
            }, $this->arguments);
            return $this->operator . "(" . implode(", ", $args) . ")";
        }
    }
}
