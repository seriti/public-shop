<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\AccountOrder;
use App\Shop\Helpers;

class AccountOrderController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $user = $this->container->user;

        //NB: TABLE_PREFIX constant not applicable as not called within admin module
        $module = $this->container->config->get('module','shop');
        $table_prefix = $module['table_prefix'];
       
        
        $table_name = $table_prefix.'order'; 
        $table = new AccountOrder($this->container->mysql,$this->container,$table_name);

        $param = [];
        $param['user_id'] = $user->getId();
        $param['table_prefix'] = $table_prefix;
        $table->setup($param);
        $html = $table->processTable();
            
        $template['title'] = 'Your Orders';
        $template['html'] = $html;
        //$template['javascript'] = $dashboard->getJavascript();
        
        return $this->container->view->render($response,'public.php',$template);
    }
}