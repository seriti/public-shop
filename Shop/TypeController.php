<?php
namespace App\Shop;

use App\Shop\Type;
use Psr\Container\ContainerInterface;

class TypeController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'type'; 
        $table = new Type($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Product types';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}