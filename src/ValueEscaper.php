<?php

namespace PrestaShop\FacetedSearch;

use PrestaShop\FacetedSearch\QueryBuilder\ValueEscaperInterface;
use Db;

class ValueEscaper implements ValueEscaperInterface
{
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function escapeString($str)
    {
        return $this->db->escape($str);
    }
}
