<?php

require implode(DIRECTORY_SEPARATOR, [__DIR__, 'vendor', 'autoload.php']);
use PrestaShop\FacetedSearch\ProductSearchProvider;

class FacetedSearch extends Module
{
    public function __construct()
    {
        $this->name = 'facetedsearch';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'fmdj';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];

        $this->bootstrap = true;

        $this->displayName = $this->l('Faceted Search');
        $this->description = $this->l('Get a rich, faceted search engine on all your product listing pages.');

        parent::__construct();
    }

    public function install()
    {
        return parent::install() && $this->registerHook('productSearchProvider') && $this->registerHook('displayLeftColumn');
    }

    public function hookProductSearchProvider($params)
    {
        return new ProductSearchProvider(Db::getInstance());
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->display(__FILE__, 'facets.tpl');
    }
}
