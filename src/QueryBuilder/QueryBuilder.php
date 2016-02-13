<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class QueryBuilder
{
    private $select     = [];
    private $from       = null;
    private $joins      = [];
    private $where      = null;
    private $groupBy    = [];
    private $orderBy    = [];
    private $escaper    = null;

    public function __construct(ValueEscaperInterface $escaper)
    {
        $this->escaper = $escaper;
    }

    public function field($tableNameOrFieldName, $fieldName = null)
    {
        return new Field(
            $fieldName ? $tableNameOrFieldName : null,
            $fieldName ? $fieldName : $tableNameOrFieldName
        );
    }

    public function table($tableName)
    {
        return new Table($tableName);
    }

    public function select(Field $field)
    {
        $qb = clone $this;
        $qb->select[] = $field;
        return $qb;
    }

    public function from(Table $table)
    {
        $qb = clone $this;
        $qb->from = $table;
        return $qb;
    }

    public function innerJoin(Table $table, ExpressionInterface $on = null)
    {
        $qb = clone $this;
        $qb->joins[] = new Join(Join::INNER, $table, $on);
        return $qb;
    }

    public function equal(ExpressionInterface $a, ExpressionInterface $b)
    {
        return (new Operation("equal"))->addArgument($a)->addArgument($b);
    }

    public function both(ExpressionInterface $a, ExpressionInterface $b)
    {
        return (new Operation("both"))->addArgument($a)->addArgument($b);
    }

    public function either(ExpressionInterface $a, ExpressionInterface $b)
    {
        return (new Operation("either"))->addArgument($a)->addArgument($b);
    }

    public function count(ExpressionInterface $a)
    {
        return (new Operation("COUNT", "prefix"))->addArgument($a);
    }

    public function value($v)
    {
        return new Value($v, $this->escaper);
    }

    public function where(ExpressionInterface $expression)
    {
        $qb = clone $this;
        $qb->where = $expression;
        return $qb;
    }

    public function andWhere(ExpressionInterface $expression)
    {
        if (null !== $this->where) {
            return $this->where($this->both(
                $this->where,
                $expression
            ));
        } else {
            return $this->where($expression);
        }
    }

    public function orWhere(ExpressionInterface $expression)
    {
        if (null !== $this->where) {
            return $this->where($this->either(
                $this->where,
                $expression
            ));
        } else {
            return $this->where($expression);
        }
    }

    public function groupBy(ExpressionInterface $expression)
    {
        $qb = clone $this;
        $qb->groupBy[] = $expression;
        return $qb;
    }

    public function orderBy(ExpressionInterface $expression, $direction = null)
    {
        $qb = clone $this;
        $qb->orderBy[] = new OrderBy($expression, $direction);
        return $qb;
    }

    public function getSQL()
    {
        $parts = [];

        if (!empty($this->select)) {
            $parts[] = "SELECT " . implode(", ", array_map(function (Field $f) {
                return $f->getSQL();
            }, $this->select));
        }

        if (null !== $this->from) {
            $parts[] = "FROM " . $this->from->getSQL();
        }

        if (!empty($this->joins)) {
            $parts = array_merge($parts, array_map(function (Join $j) {
                return $j->getSQL();
            }, $this->joins));
        }

        if (null !== $this->where) {
            $parts[] = "WHERE " . $this->where->getSQL();
        }

        if (!empty($this->groupBy)) {
            $parts[] = "GROUP BY " . implode(", ", array_map(function (ExpressionInterface $e) {
                return $e->getSQL();
            }, $this->groupBy));
        }

        if (!empty($this->orderBy)) {
            $parts[] = "ORDER BY " . implode(", ", array_map(function (OrderBy $e) {
                return $e->getSQL();
            }, $this->orderBy));
        }

        return implode(" ", $parts);
    }
}
