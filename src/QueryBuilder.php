<?php

namespace PrestaShop\FacetedSearch;

class QueryBuilder
{
    private $select = [];
    private $from = [];
    private $where = [];

    public function select($fragment)
    {
        $this->select[] = $fragment;
        return $this;
    }

    public function from($fragment)
    {
        $this->from[] = $fragment;
        return $this;
    }

    public function where($fragment)
    {
        $this->where[] = $fragment;
        return $this;
    }

    public function innerJoin($fragment)
    {
        return $this->from('INNER JOIN ' . $fragment);
    }

    public function getSQL()
    {
        $parts = [];

        $parts[] = 'SELECT ' . implode(',', $this->select);
        $parts[] = 'FROM ' . implode(' ', $this->from);

        if (!empty($this->where)) {
            $parts[] = 'WHERE ' . implode(" AND ", $this->where);
        }

        return implode("\n", $parts);
    }
}
