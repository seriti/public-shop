<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class AccountOrderPayment extends Table 
{
    protected $table_prefix = MODULE_SHOP['table_prefix'];

    //configure
    public function setup($param = []) 
    {
        if(isset($param['table_prefix'])) $this->table_prefix = $param['table_prefix'];

        $table_param = ['row_name'=>'Payment','col_label'=>'amount','pop_up'=>true];
        parent::setup($table_param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>$this->table_prefix.'order','key'=>'order_id','child_col'=>'order_id', 
                                 'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] created-",date_create) FROM '.$this->table_prefix.'order WHERE order_id = "{KEY_VAL}" '));  

        
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
