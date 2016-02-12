<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

class Join
{
    const INNER = "INNER JOIN";
    private $type;
    private $table;
    private $on;

    public function __construct($type, Table $table, ExpressionInterface $on = null)
    {
        $this->type = $type;
        $this->table = $table;
        $this->on = $on;
    }

    public function getSQL()
    {
        return $this->type . " " . $this->table->getSQL() . ($this->on ? " ON " . $this->on->getSQL() : '');
    }
}
