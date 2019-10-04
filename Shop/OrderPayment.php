<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class OrderPayment extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Payment','col_label'=>'amount','pop_up'=>true];
        parent::setup($param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX.'order','key'=>'order_id','child_col'=>'order_id', 
                                 'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] created-",date_create) FROM '.TABLE_PREFIX.'order WHERE order_id = "{KEY_VAL}" '));  

        
        $this->addTableCol(array('id'=>'payment_id','type'=>'INTEGER','title'=>'Payment ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'date_create','type'=>'DATETIME','title'=>'Date paid'));
        $this->addTableCol(array('id'=>'amount','type'=>'DECIMAL','title'=>'Amount'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $status_list = ['NEW','CONFIRMED'];
        $this->addSelect('status',['list'=>$status_list,'list_assoc'=>false]);
    }    
}

?>
