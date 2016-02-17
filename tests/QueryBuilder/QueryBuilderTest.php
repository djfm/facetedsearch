<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

use PHPUnit_Framework_TestCase;
use Phake;

class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
    private $qb;

    public function setup()
    {
        $valueEscaper = Phake::mock(
            'PrestaShop\FacetedSearch\QueryBuilder\ValueEscaperInterface'
        );

        Phake::when($valueEscaper)
            ->escapeString(Phake::anyParameters())
            ->thenReturnCallback(function ($str) {
                return "escaped($str)";
            })
        ;

        $this->qb = new QueryBuilder($valueEscaper);
    }

    public function test_field_creates_a_fully_qualified_field_name()
    {
        $this->assertEquals("product.id", $this->qb->field("product", "id")->getSQL());
    }

    public function test_field_creates_a_non_qualified_field_name()
    {
        $this->assertEquals("id", $this->qb->field("id")->getSQL());
    }

    public function test_field_creates_a_fully_qualified_field_name_with_an_alias()
    {
        $this->assertEquals(
            "product.id AS productId",
            $this->qb->field("product", "id")->alias("productId")->getSQL()
        );
    }

    public function test_table_creates_a_table()
    {
        $this->assertEquals(
            "product",
            $this->qb->table("product")->getSQL()
        );
    }

    public function test_table_creates_an_aliased_table()
    {
        $this->assertEquals(
            "product AS p",
            $this->qb->table("product")->alias("p")->getSQL()
        );
    }

    public function test_select_field()
    {
        $this->assertEquals(
            "SELECT p",
            $this->qb->select($this->qb->field("p"))->getSQL()
        );
    }

    public function test_select_multiple_fields()
    {
        $this->assertEquals(
            "SELECT p, q",
            $this->qb
                ->select($this->qb->field("p"))
                ->select($this->qb->field("q"))
                ->getSQL()
        );
    }

    public function test_select_from_clause()
    {
        $this->assertEquals(
            "SELECT p FROM product",
            $this->qb
                ->select($this->qb->field("p"))
                ->from($this->qb->table("product"))
                ->getSQL()
        );
    }

    public function test_innerJoin_no_clause()
    {
        $this->assertEquals(
            "SELECT p FROM product INNER JOIN shop",
            $this->qb
                ->select($this->qb->field("p"))
                ->from($this->qb->table("product"))
                ->innerJoin($this->qb->table("shop"))
                ->getSQL()
        );
    }

    public function test_innerJoin_two_tables_no_clause()
    {
        $this->assertEquals(
            "SELECT p FROM product INNER JOIN shop INNER JOIN customer",
            $this->qb
                ->select($this->qb->field("p"))
                ->from($this->qb->table("product"))
                ->innerJoin($this->qb->table("shop"))
                ->innerJoin($this->qb->table("customer"))
                ->getSQL()
        );
    }

    public function test_equal_expression()
    {
        $this->assertEquals(
            "(a = b)",
            $this->qb->equal(
                $this->qb->field("a"),
                $this->qb->field("b")
            )->getSQL()
        );
    }

    public function test_equal_literal_number()
    {
        $this->assertEquals(
            "(a = 1)",
            $this->qb->equal(
                $this->qb->field("a"),
                $this->qb->value(1)
            )->getSQL()
        );
    }

    public function test_equal_literal_string()
    {
        $this->assertEquals(
            "(a = 'escaped(unsafe)')",
            $this->qb->equal(
                $this->qb->field("a"),
                $this->qb->value("unsafe")
            )->getSQL()
        );
    }

    public function test_both_expression()
    {
        $this->assertEquals(
            "(a AND b)",
            $this->qb->both(
                $this->qb->field("a"),
                $this->qb->field("b")
            )->getSQL()
        );
    }

    public function test_all_expression()
    {
        $this->assertEquals(
            "((a AND b) AND c)",
            $this->qb->all([
                $this->qb->field("a"),
                $this->qb->field("b"),
                $this->qb->field("c")
            ])->getSQL()
        );
    }

    public function test_either_expression()
    {
        $this->assertEquals(
            "(a OR b)",
            $this->qb->either(
                $this->qb->field("a"),
                $this->qb->field("b")
            )->getSQL()
        );
    }

    public function test_any_expression()
    {
        $this->assertEquals(
            "((a OR b) OR c)",
            $this->qb->any([
                $this->qb->field("a"),
                $this->qb->field("b"),
                $this->qb->field("c")
            ])->getSQL()
        );
    }

    public function test_count_expression()
    {
        $this->assertEquals(
            "COUNT(product.id)",
            $this->qb->count(
                $this->qb->field("product", "id")
            )->getSQL()
        );
    }

    public function test_innerJoin_equality_clause()
    {
        $this->assertEquals(
            "SELECT p FROM product INNER JOIN shop ON (product.shopId = shop.id)",
            $this->qb
                ->select($this->qb->field("p"))
                ->from($this->qb->table("product"))
                ->innerJoin(
                    $this->qb->table("shop"),
                    $this->qb->equal(
                        $this->qb->field("product", "shopId"),
                        $this->qb->field("shop", "id")
                    )
                )
                ->getSQL()
        );
    }

    public function test_where()
    {
        $this->assertEquals(
            "SELECT price FROM product WHERE (a = b)",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->where($this->qb->equal(
                    $this->qb->field("a"),
                    $this->qb->field("b")
                ))
                ->getSQL()
        );
    }

    public function test_andWhere()
    {
        $this->assertEquals(
            "SELECT price FROM product WHERE ((a = b) AND (x = y))",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->where($this->qb->equal(
                    $this->qb->field("a"),
                    $this->qb->field("b")
                ))
                ->andWhere($this->qb->equal(
                    $this->qb->field("x"),
                    $this->qb->field("y")
                ))
                ->getSQL()
        );
    }

    public function test_orWhere()
    {
        $this->assertEquals(
            "SELECT price FROM product WHERE ((a = b) OR (x = y))",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->where($this->qb->equal(
                    $this->qb->field("a"),
                    $this->qb->field("b")
                ))
                ->orWhere($this->qb->equal(
                    $this->qb->field("x"),
                    $this->qb->field("y")
                ))
                ->getSQL()
        );
    }

    public function test_group_by()
    {
        $this->assertEquals(
            "SELECT price FROM product GROUP BY x, y",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->groupBy($this->qb->field("x"))
                ->groupBy($this->qb->field("y"))
                ->getSQL()
        );
    }

    public function test_order_by()
    {
        $this->assertEquals(
            "SELECT price FROM product ORDER BY x DESC, y",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->orderBy($this->qb->field("x"), "DESC")
                ->orderBy($this->qb->field("y"))
                ->getSQL()
        );
    }

    public function test_table_name_is_prefixed_in_simple_select()
    {
        $this->assertEquals(
            "SELECT price FROM prefix_product",
            $this->qb
                ->select($this->qb->field("price"))
                ->from($this->qb->table("product"))
                ->setTablePrefix("prefix_")
                ->getSQL()
        );
    }

    public function test_table_aliases_are_not_prefixed()
    {
        $this->assertEquals(
            "SELECT p.x FROM prefix_product AS p",
            $this->qb->select(
                $this->qb->field("p", "x")
            )
            ->from(
                $this->qb->table("product")->alias("p")
            )
            ->setTablePrefix("prefix_")
            ->getSQL()
        );
    }

    public function test_table_aliases_are_suffixed()
    {
        $this->assertEquals(
            "SELECT p_suffix.x FROM prefix_product AS p_suffix",
            $this->qb->select(
                $this->qb->field("p", "x")
            )
            ->from(
                $this->qb->table("product")->alias("p")
            )
            ->setTablePrefix("prefix_")
            ->setAliasSuffix("_suffix")
            ->getSQL()
        );
    }

    public function test_table_aliases_are_suffixed_unless_nosuffix_is_used()
    {
        $this->assertEquals(
            "SELECT p.x FROM product AS p",
            $this->qb->select(
                $this->qb->field("p", "x")->noSuffix()
            )
            ->from(
                $this->qb->table("product")->alias("p")->noSuffix()
            )
            ->setAliasSuffix("_suffix")
            ->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__where_is_merged_with_and()
    {
        $this->assertEquals(
            "SELECT id FROM product WHERE ((x = 4) AND (y = 5))",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->where(
                $this->qb->equal(
                    $this->qb->field("x"),
                    $this->qb->value(4)
                )
            )->merge(
                $this->qb->where(
                    $this->qb->equal(
                        $this->qb->field("y"),
                        $this->qb->value(5)
                    )
                )
            )->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__select_is_concatenated()
    {
        $this->assertEquals(
            "SELECT id, stuff FROM product",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->merge(
                $this->qb->select(
                    $this->qb->field("stuff")
                )
            )->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__rhs_from_is_used()
    {
        $this->assertEquals(
            "SELECT id FROM not_product",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->merge(
                $this->qb->from(
                    $this->qb->table("not_product")
                )
            )->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__joins_are_concatenated()
    {
        $this->assertEquals(
            "SELECT id FROM product INNER JOIN x INNER JOIN y",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->innerJoin(
                $this->qb->table("x")
            )->merge(
                $this->qb->innerJoin(
                    $this->qb->table("y")
                )
            )->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__groupBy_are_concatenated()
    {
        $this->assertEquals(
            "SELECT id FROM product GROUP BY x, y",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->groupBy(
                $this->qb->field("x")
            )->merge(
                $this->qb->groupBy(
                    $this->qb->field("y")
                )
            )->getSQL()
        );
    }

    public function test_queryBuilders_are_merged__orderBy_are_concatenated()
    {
        $this->assertEquals(
            "SELECT id FROM product ORDER BY x, y",
            $this->qb->select(
                $this->qb->field("id")
            )->from(
                $this->qb->table("product")
            )->orderBy(
                $this->qb->field("x")
            )->merge(
                $this->qb->orderBy(
                    $this->qb->field("y")
                )
            )->getSQL()
        );
    }
}
