<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\Form;
use Seriti\Tools\STORAGE;

use App\Shop\Helpers;

class AccountOrder extends Table 
{
    protected $table_prefix = MODULE_SHOP['table_prefix'];
    protected $user_id = 0;

    //configure
    public function setup($param = []) 
    {
        $table_param = ['row_name'=>'Order','col_label'=>'date_create'];
        parent::setup($table_param);
       
        if(isset($param['table_prefix'])) $this->table_prefix = $param['table_prefix'];
        if(isset($param['user_id'])) $this->user_id = $param['user_id'];

        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(array('id'=>'order_id','type'=>'INTEGER','title'=>'Order ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        $this->addTableCol(array('id'=>'date_create','type'=>'DATETIME','title'=>'Date created'));
        $this->addTableCol(array('id'=>'date_update','type'=>'DATETIME','title'=>'Date updated'));
        $this->addTableCol(array('id'=>'no_items','type'=>'INTEGER','title'=>'Number of items'));
        $this->addTableCol(array('id'=>'subtotal','type'=>'DECIMAL','title'=>'Sub-total'));
        $this->addTableCol(array('id'=>'tax','type'=>'DECIMAL','title'=>'Tax'));
        $this->addTableCol(array('id'=>'discount','type'=>'DECIMAL','title'=>'Discount'));
        $this->addTableCol(array('id'=>'ship_cost','type'=>'DECIMAL','title'=>'Shipping cost'));
        $this->addTableCol(array('id'=>'total','type'=>'DECIMAL','title'=>'Total cost'));
        $this->addTableCol(array('id'=>'ship_address','type'=>'TEXT','title'=>'Shipping address'));
        $this->addTableCol(array('id'=>'ship_location_id','type'=>'INTEGER','title'=>'Shipping location','join'=>'name FROM '.$this->table_prefix.'ship_location WHERE location_id'));
        $this->addTableCol(array('id'=>'ship_option_id','type'=>'INTEGER','title'=>'Shipping option','join'=>'name FROM '.$this->table_prefix.'ship_option WHERE option_id'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addSql('WHERE','T.user_id = "'.$this->db->escapeSql($this->user_id).'" ');
        
        //$this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        //$this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));
        $this->addAction(array('type'=>'popup','text'=>'Items','url'=>'order_item','mode'=>'view','width'=>600,'height'=>600)); 
        $this->addAction(array('type'=>'popup','text'=>'Payments','url'=>'order_payment','mode'=>'view','width'=>600,'height'=>600)); 

        //$this->addSearch(array('product_id','options','price'),array('rows'=>1));
        
    }

    

    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {} 
}
?>
