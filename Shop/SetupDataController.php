<?php
namespace App\Shop;

use App\Shop\SetupData;
use Psr\Container\ContainerInterface;

class SetupDataController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $module = $this->container->config->get('module','shop');   
        $setup = new SetupData($this->container->mysql,$this->container->system,$module);
       
        $setup->setupSql();
        //$html = $setup->destroy();
        $html = $setup->process();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Shop data configuration';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}