<?php

class ProductSearchProviderTest extends PHPUnit_Framework_TestCase
{
    private $module;

    public function setup()
    {
        $this->module = Module::getInstanceByName('facetedsearch');
    }

    public function test_module_responds_to_hookProductSearchProvider()
    {
        $this->assertInstanceOf(
            'PrestaShop\FacetedSearch\ProductSearchProvider',
            $this->module->hookProductSearchProvider([])
        );
    }
}
