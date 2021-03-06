<?php 
namespace App\Shop;

use Seriti\Tools\Table;
use Seriti\Tools\Form;
use Seriti\Tools\STORAGE;

use App\Shop\Helpers;

class Cart extends Table 
{
    protected $table_prefix = 'shp_';
    protected $order_id;

    //configure
    public function setup($param = []) 
    {
        //NB: csrf verification turned off as public user can update cart without being logged in or a user at all 
        $table_param = ['row_name'=>'Cart item','col_label'=>'product_id','table_edit_all'=>true,'verify_csrf'=>false];
        parent::setup($table_param);
       
        if(isset($param['table_prefix'])) $this->table_prefix = $param['table_prefix'];
        $this->order_id = $param['order_id'];

        $access['read_only'] = false;
        $access['edit'] = true;                         
        $access['delete'] = true;
        $access['add'] = false;
        $this->modifyAccess($access);

        //default of "Proceed" similar with title "Proceed to checkout"
        $this->changeText('btn_action','Update cart');

        $this->addTableCol(array('id'=>'item_id','type'=>'INTEGER','title'=>'Cart item ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'product_id','type'=>'STRING','title'=>'Product','edit'=>false));
        $this->addTableCol(array('id'=>'quantity','type'=>'INTEGER','title'=>'Quantity'));
        $this->addTableCol(array('id'=>'options','type'=>'TEXT','title'=>'Options'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price','edit'=>false));
        $this->addTableCol(array('id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal','edit'=>false));
        $this->addTableCol(array('id'=>'discount','type'=>'DECIMAL','title'=>'Discount','edit'=>false));
        $this->addTableCol(array('id'=>'tax','type'=>'DECIMAL','title'=>'Tax','edit'=>false));
        $this->addTableCol(array('id'=>'total','type'=>'DECIMAL','title'=>'Total','edit'=>false));

        $this->addSql('WHERE','T.order_id = "'.$this->db->escapeSql($param['order_id']).'" ');
        
        $this->addAction(['type'=>'check_box','text'=>'','checked'=>true]);
        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        //$this->addSearch(array('product_id','options','price'),array('rows'=>1));
        
    }

    //NB: this will update cart item with latest product pricing,discounts,tax
    protected function afterUpdate($id,$context,$data) 
    {
        $error = '';
        $error_tmp = '';

        if($context === 'UPDATE') {
            //NB: product_id NOT available in $data as not included in form
            $item = $this->get($id);
            
            $sql = 'SELECT product_id,name,status,options,quantity,price,discount,tax '.
                   'FROM '.$this->table_prefix.'product '.
                   'WHERE product_id = "'.$this->db->escapeSql($item['product_id']).'" ';
            $product = $this->db->readSqlRecord($sql);

            if($product === 0) {
                $error = 'Could not find product data to update cart item totals!';
                if($this->debug) $error .= ': product_id['.$item['product_id'].'] SQL:'.$sql;
                $this->addError($error);
            } else {
                $data['price'] = $product['price'];
                //product discount and tax are text fields which need to be converted to a numerical format
                $data['discount'] = $product['discount'];
                $data['tax'] = $product['tax'];
                $data['weight'] = $product['ship_weight'];
                Helpers::calcOrderItemTotals($data);

                $where = ['item_id'=>$id];
                $this->db->updateRecord($this->table,$data,$where,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'Could not find update cart item totals!';
                    if($this->debug) $error .= ': item_id['.$id.'] error:'.$error_tmp;
                    $this->addError($error);
                }
            }
        }         
    } 
    

    protected function modifyRowValue($col_id,$data,&$value)
    {
        if($col_id === 'product_id') {
            $product_id = $value;
            $s3 = $this->getContainer('s3');

            $value = Helpers::getProductSummary($this->db,$this->table_prefix,$s3,$product_id);
        }
    }

    protected function modifyEditValue($col_id,$value,$edit_type,$param) 
    {
        $html = '';

        if($col_id === 'product_id') {
            //view() returns joined product name and not id, do not want user to update product id but just to see namwe
            $item = $this->view($this->key['value']);
            //must still have product_id 
            $html .= '<h1>'.$item['product_id'].'</h1>';;//Form::hiddenInput(['product_id'=>$value]);
        }

        if($col_id === 'options') {
            $html .= nl2br($value).'<i>(You cannot modify product options from the cart.)</i>';
        }

        return $html;
        
    }

    protected function afterDelete($id) 
    {
        $error = '';
        $count = $this->count();

        if($count['row_count'] == 0) {
            $sql = 'DELETE FROM '.$this->table_prefix.'order WHERE order_id = "'.$this->db->escapeSql($this->order_id).'" ';
            $this->db->executeSql($sql,$error);
            if($error !== '') $this->addError('Could not erase product order.');
        }

    } 

    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {} 
}
?>
