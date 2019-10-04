<?php
namespace App\Shop;

use Seriti\Tools\Dashboard AS DashboardTool;

class AccountDashboard extends DashboardTool
{
     

    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $user = $this->getContainer('user'); 

        //Class accessed outside /App/Shop so cannot use TABLE_PREFIX constant
        $module = $this->container->config->get('module','shop');
        $table_prefix = $module['table_prefix'];

        $sql = 'SELECT * FROM '.$table_prefix.'user_extend WHERE user_id = "'.$user->getId().'" ';
        $user_extend = $this->db->readSqlRecord($sql);

        $sql = 'SELECT order_id,date_create,no_items,total FROM '.$table_prefix.'order '.
               'WHERE user_id = "'.$user->getId().'" AND status = "NEW" '.
               'ORDER BY date_create DESC ';
        $new_orders = $this->db->readSqlArray($sql);
        if($new_orders === 0) {
            $order_html = 'NO outstanding orders';
        } else {
            $order_html .= '<ul>';
            foreach($new_orders as $order_id => $order) {
               $item_href = "javascript:open_popup('order_item?id=".$order_id."',600,600)";
               $order_html .= '<li>Order ID['.$order_id.'] created['.$order['date_create'].'] <a href="'.$item_href.'">items['.$order['no_items'].']</a> total['.$order['total'].']</li>'; 
            }
            $order_html .= '</ul>';
        }    

        //(block_id,col,row,title)
        $this->addBlock('USER',1,1,'User data: <a href="profile?mode=edit">edit</a>');
        $this->addItem('USER','Email: '.$user->getEmail());
        $this->addItem('USER','Cellphone: '.$user_extend['cell']);
        $this->addItem('USER','Landline: '.$user_extend['tel']);
        $this->addItem('USER','Address:<br/>'.nl2br($user_extend['ship_address']));
        
        $this->addBlock('ORDERS',2,1,'Outstanding Orders');
        $this->addItem('ORDERS',$order_html);  
        
    }

}

?>