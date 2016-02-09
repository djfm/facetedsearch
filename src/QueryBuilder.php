<?php

namespace PrestaShop\FacetedSearch;

class QueryBuilder
{
    private $select = [];
    private $from = [];
    private $where = [];
    private $orderBy = [];
    private $groupBy = [];

    public function select($fragment, $replace = false)
    {
        if ($replace) {
            $this->select = [];
        }
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
        if ("" !== $fragment) {
            $this->where[] = $fragment;
        }
        return $this;
    }

    public function innerJoin($fragment)
    {
        return $this->from('INNER JOIN ' . $fragment);
    }

    public function orderBy($fragment)
    {
        $this->orderBy[] = $fragment;
        return $this;
    }

    public function groupBy($fragment)
    {
        $this->groupBy[] = $fragment;
        return $this;
    }

    public function getSQL()
    {
        $parts = [];

        $parts[] = 'SELECT ' . implode(',', $this->select);
        $parts[] = 'FROM ' . implode(' ', $this->from);

        if (!empty($this->where)) {
            $parts[] = 'WHERE ' . implode(" AND ", $this->where);
        }

        if (!empty($this->groupBy)) {
            $parts[] = 'GROUP BY ' . implode(',', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $parts[] = 'ORDER BY ' . implode(',', $this->orderBy);
        }

        return implode("\n", $parts);
    }
}
