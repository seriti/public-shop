<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\TABLE_USER;

class UserExtend extends Table 
{
    protected function beforeUpdate($id,$edit_type,&$form,&$error_str) {
        if($form['parameter'] === 'HOURLY_RATE') {
          if(!is_numeric($form['value'])) $error_str .= 'Hourly rate not a valid number!';
        }  
    } 
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Setting','col_label'=>'parameter'];
        parent::setup($param);        

        $this->addTableCol(array('id'=>'extend_id','type'=>'INTEGER','title'=>'Extend ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'user_id','type'=>'INTEGER','title'=>'User','join'=>'CONCAT(name,": ",email) FROM '.TABLE_USER.' WHERE user_id'));
        $this->addTableCol(array('id'=>'name_invoice','type'=>'STRING','title'=>'Invoice name','required'=>true));
        $this->addTableCol(array('id'=>'cell','type'=>'STRING','title'=>'Cellphone','required'=>true));
        $this->addTableCol(array('id'=>'tel','type'=>'STRING','title'=>'Telephone','required'=>false));
        $this->addTableCol(array('id'=>'email_alt','type'=>'EMAIL','title'=>'Email alternative','required'=>false));
        $this->addTableCol(array('id'=>'bill_address','type'=>'TEXT','title'=>'Billing address','required'=>false));
        $this->addTableCol(array('id'=>'ship_address','type'=>'TEXT','title'=>'Shipping address','required'=>false));

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'view','text'=>'view'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        $this->addSearch(array('user_id','cell','tel','email_alt','bill_address','ship_address'),array('rows'=>2));

        $this->addSelect('user_id','SELECT user_id,name FROM '.TABLE_USER.' WHERE status = "OK"');
    }    

}
?>
