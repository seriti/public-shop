<?php 
namespace App\Shop;

use Seriti\Tools\Table;

class PayOption extends Table 
{
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Payment option','col_label'=>'name'];
        parent::setup($param);
                
        $this->addTableCol(array('id'=>'option_id','type'=>'INTEGER','title'=>'Option ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Name'));
        $this->addTableCol(array('id'=>'provider_code','type'=>'STRING','title'=>'Payment provider code'));
        $this->addTableCol(array('id'=>'sort','type'=>'INTEGER','title'=>'Sort Order','hint'=>'Option display order in dropdowns'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        

        $this->addSortOrder('T.sort','Sort order','DEFAULT');

        //$this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
   }

   protected function beforeUpdate($id,$context,&$data,&$error) 
   {
        //check provider code valid
        $table_provider = MODULE_PAYMENT['table_prefix'].'provider';
        $sql = 'SELECT * FROM '.$table_provider.' WHERE code = "'.$this->db->escapeSql($data['provider_code']).'" '; 
        $provider = $this->db->readSqlRecord($sql);
        if($provider == 0) $error .= 'Invalid provider code['.$data['provider_code'].']. Please check Payment providers setup.';
   }
}
?>
