<?php
namespace App\Shop;

use Seriti\Tools\Date;
use Seriti\Tools\CURRENCY_SYMBOL;
use Seriti\Tools\Dashboard AS DashboardTool;

class AccountDashboard extends DashboardTool
{
     

    //configure
    public function setup($param = []) 
    {
        $error = '';  
        $this->col_count = 2;

        $user = $this->getContainer('user'); 

        //Class accessed outside /App/Shop so cannot use TABLE_PREFIX constant
        $module = $this->container->config->get('module','shop');
        $table_prefix = $module['table_prefix'];

        $sql = 'SELECT * FROM '.$table_prefix.'user_extend WHERE user_id = "'.$user->getId().'" ';
        $user_extend = $this->db->readSqlRecord($sql);


        //check cart contents
        $cart_html = ''; 
        $temp_token = $user->getTempToken(False);
        $cart = Helpers::getCart($this->db,$table_prefix,$temp_token);
        if($cart === 0 ) {
            $cart_html .= 'Your shopping cart is empty.';
        } else {
            $cart_html .=  'You have '.$cart['item_count'].' item/s in your shopping cart.<br/>'.
                           '<a href="/public/cart">Click to view cart contents:<span class="glyphicon glyphicon-shopping-cart"></span></a>';
        }

        //check for active orders
        $sql = 'SELECT order_id,date_create FROM '.$table_prefix.'order '.
               'WHERE user_id = "'.$user->getId().'" AND status <> "COMPLETED" '.
               'ORDER BY date_create DESC ';
        $new_orders = $this->db->readSqlList($sql);
        if($new_orders === 0) {
            $order_html = 'NO outstanding orders';
        } else {
            $order_html .= '<ul>';
            foreach($new_orders as $order_id => $date_create) {
                $order = Helpers::getOrderDetails($this->db,$table_prefix,$order_id,$error);
                if($error !== '') {
                    $order_html .= '<li>Error: '.$error.'</li>';
                } else {
                    $item_href = "javascript:open_popup('order_item?id=".$order_id."',600,600)";
                    $payment_link = '';
                    if($order['order']['status'] === 'ACTIVE') {
                       $payment_link = '<a href = "payment?order='.$order_id.'">Make payment now</a>';
                    }
                    $order_html .= '<li>'.
                                   'Order-'.$order_id.' Created on '.Date::formatDate($date_create).':<br/> '.
                                   'Status: <strong>'.Helpers::getOrderStatusText($order['order']['status']).'</strong><br/>'.
                                   'Items: '.$order['order']['no_items'].' <a href="'.$item_href.'">(view items)</a><br/>'.
                                   'Ship method: '.$order['order']['ship_option'].'<br/>'.
                                   'Ship location: '.$order['order']['ship_location'].'<br/>'.
                                   'Ship address: '.$order['order']['ship_address'].'<br/>'.
                                   'Sub total: '.CURRENCY_SYMBOL.$order['order']['subtotal'].'<br/>'.
                                   'Shipping: '.CURRENCY_SYMBOL.$order['order']['ship_cost'].'<br/>'.
                                   'Total: '.CURRENCY_SYMBOL.$order['order']['total'].'<br/>'.
                                   'Payment method: '.$order['order']['pay_option'].'<br/>'.$payment_link.
                                   '</li>'; 


                }
               
            }
            $order_html .= '</ul>';
        }    

        //(block_id,col,row,title)
        $this->addBlock('USER',1,1,'User data: <a href="profile?mode=edit">edit</a>');
        $this->addItem('USER','<strong>Email:</strong> '.$user->getEmail());
        $this->addItem('USER','<strong>Invoice name:</strong> '.$user_extend['name_invoice']);
        $this->addItem('USER','<strong>Cellphone:</strong> '.$user_extend['cell']);
        $this->addItem('USER','<strong>Landline:</strong> '.$user_extend['tel']);
        $this->addItem('USER','<strong>Shipping Address:</strong><br/>'.nl2br($user_extend['ship_address']));
        $this->addItem('USER','<strong>Billing Address:</strong><br/>'.nl2br($user_extend['bill_address']));
        
        $this->addBlock('CART',1,2,'Shopping cart');
        $this->addItem('CART',$cart_html); 

        $this->addBlock('ORDERS',2,1,'Outstanding Orders');
        $this->addItem('ORDERS',$order_html);  
        
    }

}

?>