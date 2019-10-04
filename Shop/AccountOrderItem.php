<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class AccountOrderItem extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Item','col_label'=>'name','pop_up'=>true];
        parent::setup($param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX_SHOP.'order','key'=>'order_id','child_col'=>'order_id', 
                                 'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] created-",date_create) FROM '.TABLE_PREFIX_SHOP.'order WHERE order_id = "{KEY_VAL}" '));  

        
        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(array('id'=>'item_id','type'=>'INTEGER','title'=>'Item ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'product_id','type'=>'INTEGER','title'=>'Item','join'=>'name FROM '.TABLE_PREFIX_SHOP.'product WHERE product_id'));
        $this->addTableCol(array('id'=>'quantity','type'=>'INTEGER','title'=>'Quantity'));
        $this->addTableCol(array('id'=>'options','type'=>'TEXT','title'=>'Options'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price'));
        $this->addTableCol(array('id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal'));
        $this->addTableCol(array('id'=>'discount','type'=>'DECIMAL','title'=>'Discount'));
        $this->addTableCol(array('id'=>'tax','type'=>'DECIMAL','title'=>'Tax'));
        $this->addTableCol(array('id'=>'total','type'=>'DECIMAL','title'=>'Total'));

        //$this->addSearch(array('notes','date'),array('rows'=>1));
    }    
}

?>
