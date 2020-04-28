<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class OrderItem extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'product','col_label'=>'product_id','pop_up'=>true,'update_calling_page'=>true,];
        parent::setup($param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX.'order','key'=>'order_id','child_col'=>'order_id', 
                                 'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] created-",date_create) FROM '.TABLE_PREFIX.'order WHERE order_id = "{KEY_VAL}" '));  

        
        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(array('id'=>'item_id','type'=>'INTEGER','title'=>'Item ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'product_id','type'=>'INTEGER','title'=>'Product ID'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        /*
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        $sql_status = '(SELECT "OK") UNION (SELECT "OUT_STOCK") UNION (SELECT "DELETRE")';
        $this->addSelect('status',$sql_status);
        */

        $this->addSearch(array('product_id','status','price'),array('rows'=>2));
    } 

    protected function modifyRowValue($col_id,$data,&$value)
    {
        if($col_id === 'product_id') {
            $product_id = $value;
            $s3 = $this->getContainer('s3');

            $value = Helpers::getProductSummary($this->db,TABLE_PREFIX,$s3,$product_id);
        }
    } 

    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        //$order_id = $this->master['key_val'];
        //Helpers::checkOrderUpdateOk($this->db,$this->table_prefix,$order_id,$error);

        //check product exists and assigned to correct Shop as well as check pricing
        $sql = 'SELECT product_id,price,name,description,options FROM '.TABLE_PREFIX.'product '.
               'WHERE product_id = "'.$this->db->escapeSql($data['product_id']).'" ';
        $product = $this->db->readSqlRecord($sql);
        if($product === 0) {
            $this->addError('Order Product ID['.$data['product_id'].'] does not exist anymore!');
        } else {
            if($product['price'] > $data['price']) $this->addError('product price['.$product['price'].'] is GREATER that entered price['.$data['price'].']');
        }  

        //check that product not already part of this order
        if(!$this->errors_found) {
            $sql = 'SELECT COUNT(*) FROM '.$this->table.' '.
                   'WHERE order_id = "'.$this->db->escapeSql($this->master['key_val']).'" AND '.
                         'product_id = "'.$this->db->escapeSql($data['product_id']).'" AND item_id <> "'.$this->db->escapeSql($id).'" ';
            $exist = $this->db->readSqlValue($sql);
            if($exist) $this->addError('product is already part of order. Please update that record.');
        }
    } 

    protected function afterUpdate($id,$context,$data) 
    {
        $order_id = $this->master['key_val'];
        Helpers::updateOrderTotals($this->db,TABLE_PREFIX,$order_id,$error);
    } 

    /* ASSUME ADMIN PEOPLE WILL KNOW WHAT THEY ARE DOING???    
    protected function beforeDelete($id,&$error) 
    {
        $order_id = $this->master['key_val'];
        Helpers::checkOrderUpdateOk($this->db,$this->table_prefix,$order_id,$error);
    }
    */

    protected function afterDelete($id) 
    {
        $order_id = $this->master['key_val'];
        Helpers::updateOrderTotals($this->db,TABLE_PREFIX,$order_id,$error);
    }
}

?>
