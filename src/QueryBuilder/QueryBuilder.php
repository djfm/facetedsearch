<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class QueryBuilder extends AbstractMappable
{
    private $select         = [];
    private $from           = null;
    private $joins          = [];
    private $where          = null;
    private $groupBy        = [];
    private $orderBy        = [];
    private $escaper        = null;
    private $tablePrefix    = '';
    private $aliasSuffix    = '';
    private $limit          = null;

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

    public function select(ExpressionInterface $field)
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

    public function all(array $expressions)
    {
        $exp = array_shift($expressions);

        while (!empty($expressions)) {
            $exp = $this->both($exp, array_shift($expressions));
        }

        return $exp;
    }

    public function either(ExpressionInterface $a, ExpressionInterface $b)
    {
        return (new Operation("either"))->addArgument($a)->addArgument($b);
    }

    public function any(array $expressions)
    {
        $exp = array_shift($expressions);

        while (!empty($expressions)) {
            $exp = $this->either($exp, array_shift($expressions));
        }

        return $exp;
    }

    public function count(ExpressionInterface $a)
    {
        return (new Operation("COUNT", "prefix"))->addArgument($a);
    }

    public function distinct(ExpressionInterface $a)
    {
        return (new Operation("DISTINCT", "prefix"))->addArgument($a);
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

    public function andWhere(ExpressionInterface $expression = null)
    {
        if (null === $expression) {
            return $this;
        }

        if (null !== $this->where) {
            return $this->where($this->both(
                $this->where,
                $expression
            ));
        } else {
            return $this->where($expression);
        }
    }

    public function orWhere(ExpressionInterface $expression = null)
    {
        if (null === $expression) {
            return $this;
        }

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

    public function setTablePrefix($tablePrefix)
    {
        $qb = clone $this;
        $qb->tablePrefix = $tablePrefix;
        return $qb;
    }

    public function setAliasSuffix($aliasSuffix)
    {
        $qb = clone $this;
        $qb->aliasSuffix = $aliasSuffix;
        return $qb;
    }

    private function doGetSQL()
    {
        $parts = [];

        if (!empty($this->select)) {
            $parts[] = "SELECT " . implode(", ", array_map(function (ExpressionInterface $f) {
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

        if (null !== $this->limit) {
            $parts[] = "LIMIT " . (int)$this->limit;
        }

        return implode(" ", $parts);
    }

    public function renameTablesAndFields()
    {
        if (!$this->tablePrefix && !$this->aliasSuffix) {
            return $this;
        }

        $renamed = $this->map(function ($fragment) {
            if ($fragment instanceof Table) {
                if ($fragment->getAlias()) {
                    $prefixed = $fragment->setTableName(
                        $this->tablePrefix . $fragment->getTableName()
                    );
                    if ($prefixed->getNoSuffix()) {
                        return $prefixed;
                    } else {
                        return $prefixed->alias(
                            $prefixed->getAlias() . $this->aliasSuffix
                        );
                    }
                } else {
                    return $fragment->setTableName(
                        $this->tablePrefix . $fragment->getTableName()
                    );
                }
            } else if (
                $fragment instanceof Field &&
                $fragment->getTableName() &&
                !$fragment->getNoSuffix()
            ) {
                return $fragment->setTableName(
                    $fragment->getTableName() . $this->aliasSuffix
                );
            } else {
                return $fragment;
            }
        });

        $renamed->tablePrefix = '';
        $renamed->aliasSuffix = '';

        return $renamed;
    }

    public function merge(QueryBuilder $other)
    {
        $lhs = $this->renameTablesAndFields();
        $rhs = $other->renameTablesAndFields();

        $lhs->select = array_merge(
            $lhs->select, $rhs->select
        );

        if ($rhs->from) {
            $lhs->from = $rhs->from;
        }

        $lhs->joins = array_merge(
            $lhs->joins, $rhs->joins
        );

        if (!$lhs->where) {
            $lhs->where = $rhs->where;
        } else if ($rhs->where) {
            $lhs->where = $lhs->both($lhs->where, $rhs->where);
        }

        $lhs->groupBy = array_merge(
            $lhs->groupBy, $rhs->groupBy
        );

        $lhs->orderBy = array_merge(
            $lhs->orderBy, $rhs->orderBy
        );

        return $lhs;
    }

    public function limit($limit)
    {
        $qb = clone $this;
        $qb->limit = (int)$limit;
        return $qb;
    }

    public function getSQL()
    {
        return $this->renameTablesAndFields()->doGetSQL();
    }
}
