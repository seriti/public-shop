<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\STORAGE;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;
use Seriti\Tools\Validate;
use Seriti\Tools\Audit;

class Product extends Table 
{
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Product','col_label'=>'name'];
        parent::setup($param);
                
        $this->addTableCol(array('id'=>'product_id','type'=>'INTEGER','title'=>'Product ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'seller_id','type'=>'INTEGER','title'=>'Seller','join'=>'name FROM '.TABLE_PREFIX.'seller WHERE seller_id'));
        $this->addTableCol(array('id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'title FROM '.TABLE_PREFIX.'category WHERE id'));
        //$this->addTableCol(array('id'=>'type_id','type'=>'INTEGER','title'=>TYPE_NAME,'join'=>'name FROM '.TABLE_PREFIX.'type WHERE type_id'));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Name'));
        $this->addTableCol(array('id'=>'description','type'=>'TEXT','title'=>'Description','list'=>false));
        $this->addTableCol(array('id'=>'options','type'=>'TEXT','title'=>'Options','required'=>false));
        
        $this->addTableCol(array('id'=>'quantity','type'=>'INTEGER','title'=>'Quantity'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price'));
        $this->addTableCol(array('id'=>'tax','type'=>'STRING','title'=>'Tax','new'=>'0','hint'=>'Use "type:x%"" or "type:0.x" syntax. Assumed "inclusive:" in price unless "exclusive:" prefix used'));
        $this->addTableCol(array('id'=>'discount','type'=>'STRING','title'=>'Discount','new'=>'0','hint'=>'Use "x%" for percentage or "x" for absolute syntax.'));
        $this->addTableCol(array('id'=>'weight','type'=>'DECIMAL','title'=>'Weight Kg','new'=>0));
        $this->addTableCol(array('id'=>'volume','type'=>'DECIMAL','title'=>'Volume Litres','new'=>0));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        
        $this->addSql('JOIN','JOIN '.TABLE_PREFIX.'category AS CT ON(T.category_id = CT.'.$this->tree_cols['node'].')');
        $this->addSortOrder('CT.rank,T.name','Category, then Name','DEFAULT');

        $this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        $sql_cat = 'SELECT id,CONCAT(IF(level > 1,REPEAT("--",level - 1),""),title) FROM '.TABLE_PREFIX.'category  ORDER BY rank';
        $this->addSelect('category_id',$sql_cat);
        $sql_status = '(SELECT "NEW") UNION (SELECT "OK") UNION (SELECT "HIDE")';
        $this->addSelect('status',$sql_status);

        $this->addSelect('seller_id','SELECT seller_id,name FROM '.TABLE_PREFIX.'seller WHERE status <> "HIDE" ORDER BY sort');
        //$this->addSelect('type_id','SELECT type_id,name FROM '.TABLE_PREFIX.'type WHERE status <> "HIDE" ORDER BY sort');

        $this->addSearch(array('category_id','name','description','seller_id','options','status'),array('rows'=>2));

        
        $this->setupImages(array('table'=>TABLE_PREFIX.'file','location'=>'PRD','max_no'=>10,
                                  'icon'=>'<span class="glyphicon glyphicon-picture" aria-hidden="true"></span>&nbsp;manage',
                                  'list'=>true,'list_no'=>1,'storage'=>STORAGE,'access'=>IMAGE_ACCESS,
                                  'link_url'=>'product_image','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
                                  
    }

    protected function viewTableActions() {
        $html = '';
        $list = array();
            
        $status_set = 'NEW';
        $date_set = date('Y-m-d');
        
        if(!$this->access['read_only']) {
            $list['SELECT'] = 'Action for selected '.$this->row_name_plural;
            $list['STATUS_CHANGE'] = 'Change Product Status.';
        }  
        
        if(count($list) != 0){
            $html .= '<span style="padding:8px;"><input type="checkbox" id="checkbox_all"></span> ';
            $param['class'] = 'form-control input-medium input-inline';
            $param['onchange'] = 'javascript:change_table_action()';
            $action_id = '';
            $status_change = 'NONE';
                        
            $html .= Form::arrayList($list,'table_action',$action_id,true,$param);
            
            //javascript to show collection list depending on selecetion      
            $html .= '<script type="text/javascript">'.
                     '$("#checkbox_all").click(function () {$(".checkbox_action").prop(\'checked\', $(this).prop(\'checked\'));});'.
                     'function change_table_action() {'.
                     'var table_action = document.getElementById(\'table_action\');'.
                     'var action = table_action.options[table_action.selectedIndex].value; '.
                     'var status_select = document.getElementById(\'status_select\');'.
                     'status_select.style.display = \'none\'; '.
                     'if(action==\'STATUS_CHANGE\') status_select.style.display = \'inline\';'.
                     '}'.
                     '</script>';
            
            $param = array();
            $param['class'] = 'form-control input-small input-inline';
            //$param['class']='form-control col-sm-3';
            $sql = '(SELECT "NONE") UNION (SELECT "NEW") UNION (SELECT "OK") UNION (SELECT "HIDE")';
            $html .= '<span id="status_select" style="display:none"> status&raquo;'.
                     Form::sqlList($sql,$this->db,'status_change',$status_change,$param).
                     '</span>'; 
                    
            $html .= '&nbsp;<input type="submit" name="action_submit" value="Apply action to selected '.
                     $this->row_name_plural.'" class="btn btn-primary">';
        }  
        
        return $html; 
    }
  
    //update multiple records based on selected action
    protected function updateTable() {
        $error_str = '';
        $error_tmp = '';
        $message_str = '';
        $audit_str = '';
        $audit_count = 0;
        $html = '';
            
        $action = Secure::clean('basic',$_POST['table_action']);
        if($action === 'SELECT') {
            $this->addError('You have not selected any action to perform on '.$this->row_name_plural.'!');
        } else {
            if($action === 'STATUS_CHANGE') {
                $status_change = Secure::clean('alpha',$_POST['status_change']);
                $audit_str = 'Status change['.$status_change.'] ';
                if($status_change === 'NONE') $this->addError('You have not selected a valid status['.$status_change.']!');
            }
            
            if(!$this->errors_found) {     
                foreach($_POST as $key => $value) {
                    if(substr($key,0,8) === 'checked_') {
                        $product_id = substr($key,8);
                        $audit_str .= 'Product ID['.$product_id.'] ';
                                            
                        if($action === 'STATUS_CHANGE') {
                            $sql = 'UPDATE '.$this->table.' SET status = "'.$this->db->escapeSql($status_change).'" '.
                                   'WHERE product_id = "'.$this->db->escapeSql($product_id).'" ';
                            $this->db->executeSql($sql,$error_tmp);
                            if($error_tmp === '') {
                                $message_str = 'Status set['.$status_change.'] for lot ID['.$product_id.'] ';
                                $audit_str .= ' success!';
                                $audit_count++;
                                
                                $this->addMessage($message_str);                
                            } else {
                                $this->addError('Could not update status for lot['.$product_id.']: '.$error_tmp);                
                            }  
                        }
                    }   
                }  
            
            }  
        }  
        
        //audit any updates except for deletes as these are already audited 
        if($audit_count != 0 and $action != 'DELETE') {
            $audit_action = $action.'_'.strtoupper($this->table);
            Audit::action($this->db,$this->user_id,$audit_action,$audit_str);
        }  
            
        $this->mode = 'list';
        $html .= $this->viewTable();
            
        return $html;
    }
}
?>
