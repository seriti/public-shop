<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\STORAGE;

class Order extends Table 
{
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Order','col_label'=>'order_id'];
        parent::setup($param);
                
        $this->addTableCol(array('id'=>'order_id','type'=>'INTEGER','title'=>'Order ID','key'=>true,'key_auto'=>true,'list'=>true));
        
        //$this->addTableCol(array('id'=>'user_id','type'=>'INTEGER','title'=>'User'));
        $this->addTableCol(array('id'=>'date_create','type'=>'DATETIME','title'=>'Date created'));
        $this->addTableCol(array('id'=>'date_update','type'=>'DATETIME','title'=>'Date updated'));
        $this->addTableCol(array('id'=>'no_items','type'=>'INTEGER','title'=>'Number of items'));
        $this->addTableCol(array('id'=>'subtotal','type'=>'DECIMAL','title'=>'Sub-total'));
        $this->addTableCol(array('id'=>'tax','type'=>'DECIMAL','title'=>'Tax'));
        $this->addTableCol(array('id'=>'discount','type'=>'DECIMAL','title'=>'Discount'));
        $this->addTableCol(array('id'=>'ship_cost','type'=>'DECIMAL','title'=>'Shipping cost'));
        $this->addTableCol(array('id'=>'total','type'=>'DECIMAL','title'=>'Total cost'));
        $this->addTableCol(array('id'=>'ship_address','type'=>'TEXT','title'=>'Shipping address'));
        $this->addTableCol(array('id'=>'ship_location_id','type'=>'INTEGER','title'=>'Shipping location','join'=>'name FROM '.TABLE_PREFIX.'ship_location WHERE location_id'));
        $this->addTableCol(array('id'=>'ship_option_id','type'=>'INTEGER','title'=>'Shipping location','join'=>'name FROM '.TABLE_PREFIX.'ship_option WHERE option_id'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        
        //$this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));
        $this->addAction(array('type'=>'popup','text'=>'Payments','url'=>'order_payment','mode'=>'view','width'=>600,'height'=>600));

        $sql_status = '(SELECT "NEW") UNION (SELECT "OK") UNION (SELECT "HIDE")';
        $this->addSelect('status',$sql_status);

        $this->addSearch(array('name','description','category_id','status'),array('rows'=>1));

        $this->setupFiles(array('table'=>TABLE_PREFIX.'file','location'=>'ORD','max_no'=>10,
                                  'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                                  'list'=>true,'list_no'=>1,'storage'=>STORAGE,
                                  'link_page'=>'template_image','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
    }
}
?>
