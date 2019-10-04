<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;
use App\Shop\Product;

class ProductController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'product'; 
        $table = new Product($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Product manager';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}