<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Operation extends AbstractMappable implements ExpressionInterface
{
    private $operator;
    private $arguments = [];
    private $fixity;
    private $alias;

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

    public function alias($alias)
    {
        $exp = clone $this;
        $exp->alias = $alias;
        return $exp;
    }

    private function doAlias($str)
    {
        if ($this->alias) {
            return $str . " AS " . $this->alias;
        } else {
            return $str;
        }
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

            return $this->doAlias("(" . $this->arguments[0]->getSQL() . " $operator " . $this->arguments[1]->getSQL() . ")");
        } else if ($this->fixity === 'prefix'){
            $args = array_map(function (ExpressionInterface $e) {
                return $e->getSQL();
            }, $this->arguments);
            return $this->doAlias($this->operator . "(" . implode(", ", $args) . ")");
        }
    }
}
