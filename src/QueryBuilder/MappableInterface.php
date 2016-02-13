<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

interface MappableInterface {
    public function map(callable $cb);
}
