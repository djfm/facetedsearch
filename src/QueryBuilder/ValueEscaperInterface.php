<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

interface ValueEscaperInterface
{
    public function escapeString($rawValue);
}
