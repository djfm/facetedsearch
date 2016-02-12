<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

use PHPUnit_Framework_TestCase;

class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
    private $qb;

    public function setup()
    {
        $this->qb = new QueryBuilder;
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
            "product.id as productId",
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
            "product as p",
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
}
