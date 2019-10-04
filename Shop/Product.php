<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\STORAGE;

class Product extends Table 
{
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Product','col_label'=>'name'];
        parent::setup($param);
                
        $this->addTableCol(array('id'=>'product_id','type'=>'INTEGER','title'=>'Product ID','key'=>true,'key_auto'=>true,'list'=>true));
        
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Name'));
        $this->addTableCol(array('id'=>'description','type'=>'TEXT','title'=>'Description','list'=>false));
        $this->addTableCol(array('id'=>'options','type'=>'TEXT','title'=>'Options','required'=>false));
        $this->addTableCol(array('id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'title FROM '.TABLE_PREFIX.'category WHERE id'));
        $this->addTableCol(array('id'=>'quantity','type'=>'INTEGER','title'=>'Quantity'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price'));
        $this->addTableCol(array('id'=>'tax','type'=>'STRING','title'=>'Tax','new'=>'0','hint'=>'Use "type:x%"" or "type:0.x" syntax. Assumed "inclusive:" in price unless "exclusive:" prefix used'));
        $this->addTableCol(array('id'=>'discount','type'=>'STRING','title'=>'Discount','new'=>'0','hint'=>'Use "x%" for percentage or "x" for absolute syntax.'));
        $this->addTableCol(array('id'=>'weight','type'=>'DECIMAL','title'=>'Weight Kg','new'=>0));
        $this->addTableCol(array('id'=>'volume','type'=>'DECIMAL','title'=>'Volume Litres','new'=>0));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        
        //$this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        $sql_cat = 'SELECT id,CONCAT(IF(level > 1,REPEAT("--",level - 1),""),title) FROM '.TABLE_PREFIX.'category  ORDER BY rank';
        $this->addSelect('category_id',$sql_cat);
        $sql_status = '(SELECT "NEW") UNION (SELECT "OK") UNION (SELECT "HIDE")';
        $this->addSelect('status',$sql_status);

        $this->addSearch(array('name','description','category_id','status'),array('rows'=>1));

        $this->setupImages(array('table'=>TABLE_PREFIX.'file','location'=>'PRD','max_no'=>10,
                                  'icon'=>'<span class="glyphicon glyphicon-picture" aria-hidden="true"></span>&nbsp;manage',
                                  'list'=>true,'list_no'=>1,'storage'=>STORAGE,
                                  'link_page'=>'template_image','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
    }
}
?>
