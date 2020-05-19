<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\AccountOrderItem;
use App\Shop\Helpers;

class AccountOrderItemController
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
        $table_prefix = MODULE_SHOP['table_prefix'];
               
        $table_name = $table_prefix.'order_item'; 
        $table = new AccountOrderItem($this->container->mysql,$this->container,$table_name);

        $param = [];
        $param['table_prefix'] = $table_prefix;
        $table->setup($param);
        $html = $table->processTable();
            
        $template['title'] = '';
        $template['html'] = $html;
        //$template['javascript'] = $dashboard->getJavascript();
        
        return $this->container->view->render($response,'public_popup.php',$template);
    }
}