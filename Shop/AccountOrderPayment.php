<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class AccountOrderPayment extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Payment','col_label'=>'amount','pop_up'=>true];
        parent::setup($param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX_SHOP.'order','key'=>'order_id','child_col'=>'order_id', 
                                 'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] created-",date_create) FROM '.TABLE_PREFIX_SHOP.'order WHERE order_id = "{KEY_VAL}" '));  

        
        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(array('id'=>'payment_id','type'=>'INTEGER','title'=>'Payment ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'date_create','type'=>'DATETIME','title'=>'Date paid'));
        $this->addTableCol(array('id'=>'amount','type'=>'DECIMAL','title'=>'Amount'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        //$this->addSearch(array('notes','date'),array('rows'=>1));
    }    
}

?>
