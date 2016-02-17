<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Field implements ExpressionInterface
{
    private $tableName;
    private $fieldName;
    private $noSuffix = false;
    private $alias;

    public function __construct($tableName, $fieldName)
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
    }

    public function setTableName($tableName)
    {
        $table = clone $this;
        $table->tableName = $tableName;
        return $table;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getSQL()
    {
        $base = $this->tableName ?
            $this->tableName . "." . $this->fieldName :
            $this->fieldName
        ;
        return $this->alias ? ($base . " AS " . $this->alias) : $base;
    }

    public function alias($alias)
    {
        $field = clone $this;
        $field->alias = $alias;
        return $field;
    }

    public function noSuffix()
    {
        $field = clone $this;
        $field->noSuffix = true;
        return $field;
    }

    public function getNoSuffix()
    {
        return $this->noSuffix;
    }
}
