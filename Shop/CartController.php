<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\Cart;
use App\Shop\Helpers;

class CartController
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
        
        //NB: Cart contents same as order but user_id = 0 and temp_token identifies 
        $temp_token = $user->getTempToken();
        $cart = Helpers::getCart($db,$table_prefix,$temp_token);

        if($cart === 0) {
            $title = 'Your cart is empty!';
            $html = '<h2>If you have just completed checkout process then <a href="account/dashboard">check your account</a> for active orders.</h2>';
        } else {
            $title = 'Your shopping cart contains: <a href="checkout">&raquo;Checkout</a>';

            $table_name = $table_prefix.'order_item'; 
            $table = new Cart($this->container->mysql,$this->container,$table_name);

            $param = [];
            $param['order_id'] = $cart['order_id'];
            $param['table_prefix'] = $table_prefix;
            $table->setup($param);
            $html = $table->processTable();
        
            //display cart order totals
            if(strpos('list',$table->getMode()) !== false) {
                $template_shop = new Template(BASE_TEMPLATE.'shop/');
                $template_shop->data = Helpers::getCartItemTotals($db,$table_prefix,$cart['order_id']);
                $html .= $template_shop->render('totals.php');
            }
        }        
            

        $template['html'] = $html;
        $template['title'] = $title;
        //$template['javascript'] = $dashboard->getJavascript();
        
        return $this->container->view->render($response,'public.php',$template);
    }
}