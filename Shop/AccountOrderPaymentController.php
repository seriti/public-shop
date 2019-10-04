<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\AccountOrderPayment;
use App\Shop\Helpers;

class AccountOrderPaymentController
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
        //$table_prefix = TABLE_PREFIX_SHOP;
        $module = $this->container->config->get('module','shop');
        $table_prefix = $module['table_prefix'];
       
        
        $table_name = $table_prefix.'payment'; 
        $table = new AccountOrderPayment($this->container->mysql,$this->container,$table_name);

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