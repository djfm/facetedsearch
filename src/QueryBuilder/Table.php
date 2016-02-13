<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Table
{
    private $tableName;
    private $alias;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
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
        if ($this->alias) {
            return $this->tableName . " AS " . $this->alias;
        } else {
            return $this->tableName;
        }
    }

    public function alias($alias)
    {
        $newTable = clone $this;
        $newTable->alias = $alias;
        return $newTable;
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
