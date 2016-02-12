<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Field implements ExpressionInterface
{
    private $tableName;
    private $fieldName;
    private $alias;

    public function __construct($tableName, $fieldName)
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
    }

    public function getSQL()
    {
        $base = $this->tableName ?
            $this->tableName . "." . $this->fieldName :
            $this->fieldName
        ;
        return $this->alias ? ($base . " as " . $this->alias) : $base;
    }

    public function alias($alias)
    {
        $field = clone $this;
        $field->alias = $alias;
        return $field;
    }
}
