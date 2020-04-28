<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;
use App\Shop\Setup;

class SetupController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $module = $this->container->config->get('module','shop');  
        $setup = new Setup($this->container->mysql,$this->container,$module);

        $setup->setup();
        $html = $setup->processSetup();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Shop settings';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}