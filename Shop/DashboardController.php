<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use App\Shop\Dashboard;

class DashboardController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $dashboard = new Dashboard($this->container->mysql,$this->container);
        
        $dashboard->setup();
        $html = $dashboard->viewBlocks();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.' Shop dashboard';
        //$template['javascript'] = $dashboard->getJavascript();

        return $this->container->view->render($response,'admin.php',$template);
    }
}