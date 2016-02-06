<?php

class InstallationTest extends  PHPUnit_Framework_TestCase
{
    public function test_module_is_installed()
    {
        $module = Module::getInstanceByName('facetedsearch');
        $this->assertNotNull($module);
        $this->assertTrue($module->install());
    }
}
