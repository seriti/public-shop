<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\STORAGE;
use Seriti\Tools\TABLE_USER;

class Order extends Table 
{
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Order','col_label'=>'order_id'];
        parent::setup($param);

        $this->addForeignKey(array('table'=>TABLE_PREFIX.'order_item','col_id'=>'order_id','message'=>'Order Items'));
        $this->addForeignKey(array('table'=>TABLE_PREFIX.'order_message','col_id'=>'order_id','message'=>'Order messages'));
        $this->addForeignKey(array('table'=>TABLE_PREFIX.'payment','col_id'=>'order_id','message'=>'Order Payments'));
                
        $this->addTableCol(array('id'=>'order_id','type'=>'INTEGER','title'=>'Order ID','key'=>true,'key_auto'=>true,'list'=>true));
        
        $this->addTableCol(array('id'=>'user_id','type'=>'INTEGER','title'=>'User','join'=>'CONCAT(name,": ",email) FROM '.TABLE_USER.' WHERE user_id'));
        $this->addTableCol(array('id'=>'date_create','type'=>'DATETIME','title'=>'Date created','edit'=>false));
        $this->addTableCol(array('id'=>'date_update','type'=>'DATETIME','title'=>'Date updated','edit'=>false));
        $this->addTableCol(array('id'=>'no_items','type'=>'INTEGER','title'=>'Number of items','new'=>1));
        $this->addTableCol(array('id'=>'subtotal','type'=>'DECIMAL','title'=>'Sub-total'));
        $this->addTableCol(array('id'=>'tax','type'=>'DECIMAL','title'=>'Tax'));
        $this->addTableCol(array('id'=>'discount','type'=>'DECIMAL','title'=>'Discount'));
        $this->addTableCol(array('id'=>'ship_cost','type'=>'DECIMAL','title'=>'Shipping cost'));
        $this->addTableCol(array('id'=>'total','type'=>'DECIMAL','title'=>'Total cost'));
        $this->addTableCol(array('id'=>'ship_address','type'=>'TEXT','title'=>'Shipping address','required'=>false));
        $this->addTableCol(array('id'=>'ship_location_id','type'=>'INTEGER','title'=>'Shipping location','join'=>'name FROM '.TABLE_PREFIX.'ship_location WHERE location_id'));
        $this->addTableCol(array('id'=>'ship_option_id','type'=>'INTEGER','title'=>'Shipping location','join'=>'name FROM '.TABLE_PREFIX.'ship_option WHERE option_id'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        //order table also store cart contents before converted to an order in checkout wizard
        //$this->addSql('WHERE','T.user_id <> 0 ');

        $this->addSortOrder('T.order_id DESC','Most recent first','DEFAULT');
        
        //$this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));
        $this->addAction(array('type'=>'popup','text'=>'Payments','url'=>'order_payment','mode'=>'view','width'=>600,'height'=>600));
        $this->addAction(array('type'=>'popup','text'=>'Items','url'=>'order_item','mode'=>'view','width'=>700,'height'=>600));
        $this->addAction(array('type'=>'popup','text'=>'Messages','url'=>'order_message','mode'=>'view','width'=>700,'height'=>600));

        $sql_status = '(SELECT "NEW") UNION (SELECT "ACTIVE") UNION (SELECT "PAID") UNION (SELECT "SHIPPED") UNION (SELECT "COMPLETED")';
        $this->addSelect('status',$sql_status);
        $this->addSelect('user_id','SELECT user_id,name FROM '.TABLE_USER.' WHERE status <> "HIDE"');
        $this->addSelect('ship_location_id','SELECT location_id,name FROM '.TABLE_PREFIX.'ship_location WHERE status <> "HIDE" ORDER By sort');
        $this->addSelect('ship_option_id','SELECT option_id,name FROM '.TABLE_PREFIX.'ship_option WHERE status <> "HIDE" ORDER By sort');


        $this->addSearch(array('user_id','date_create','date_update','status'),array('rows'=>1));

        $this->setupFiles(array('table'=>TABLE_PREFIX.'file','location'=>'ORD','max_no'=>10,
                                  'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                                  'list'=>true,'list_no'=>1,'storage'=>STORAGE,
                                  'link_url'=>'order_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
    }

    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        if($context === 'UPDATE') {
            Helpers::checkOrderUpdateOk($this->db,TABLE_PREFIX,$id,$error);    
        }
    }
    
    protected function beforeDelete($id,&$error) 
    {
        Helpers::checkOrderUpdateOk($this->db,TABLE_PREFIX,$id,$error);
    }


    protected function afterUpdate($id,$edit_type,$form) {
        $error = '';
        if($edit_type === 'INSERT') {
            $sql = 'UPDATE '.$this->table.' SET date_create = NOW() '.
                   'WHERE order_id = "'.$this->db->escapeSql($id).'"';
            $this->db->executeSql($sql,$error);
        }

        if($edit_type === 'UPDATE') { 
            $sql = 'UPDATE '.$this->table.' SET date_update = NOW() '.
                   'WHERE order_id = "'.$this->db->escapeSql($id).'"';
            $this->db->executeSql($sql,$error);
        }    
    }

    protected function afterDelete($id) {
        $error = '';

        $sql = 'DELETE FROM '.TABLE_PREFIX.'order_item WHERE order_id = "'.$this->db->escapeSql($id).'" ';
        $this->db->executeSql($sql,$error); 
    }
}
?>
