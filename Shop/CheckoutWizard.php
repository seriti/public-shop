<?php
namespace App\Shop;

use Exception;

use Seriti\Tools\Wizard;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Doc;
use Seriti\Tools\Calc;
use Seriti\Tools\Secure;
use Seriti\Tools\Plupload;
use Seriti\Tools\STORAGE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_TEMP;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\TABLE_USER;

use App\Shop\Helpers;

class CheckoutWizard extends Wizard 
{
    protected $user;
    protected $temp_token;
    protected $user_id;
    
    //configure
    public function setup($param = []) 
    {
        $this->user = $this->getContainer('user');
        $this->temp_token = $this->user->getTempToken();

        $this->user_id = $this->user->getId();

        $param['bread_crumbs'] = true;
        $param['strict_var'] = false;
        $param['csrf_token'] = $this->temp_token;
        parent::setup($param);

        //standard user cols
        $this->addVariable(array('id'=>'ship_option_id','type'=>'INTEGER','title'=>'Shipping option','required'=>true));
        $this->addVariable(array('id'=>'ship_location_id','type'=>'INTEGER','title'=>'Shipping location','required'=>true));
        $this->addVariable(array('id'=>'pay_option_id','type'=>'INTEGER','title'=>'Payment option','required'=>true));
        
        $this->addVariable(array('id'=>'user_email','type'=>'EMAIL','title'=>'Your email address','required'=>false));
        $this->addVariable(array('id'=>'user_name','type'=>'STRING','title'=>'Your name','required'=>false));
        $this->addVariable(array('id'=>'user_cell','type'=>'STRING','title'=>'Your name','required'=>false));
        $this->addVariable(array('id'=>'user_ship_address','type'=>'TEXT','title'=>'Shipping address','required'=>true));
        $this->addVariable(array('id'=>'user_bill_address','type'=>'TEXT','title'=>'Billing address','required'=>true));
        
        //define pages and templates
        $this->addPage(1,'Setup','shop/checkout_page1.php',['go_back'=>true]);
        $this->addPage(2,'Confirm totals','shop/checkout_page2.php');
        $this->addPage(3,'Delivery details','shop/checkout_page3.php');
        $this->addPage(4,'Payment','shop/checkout_page4.php',['final'=>true]);  
        

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';


        //PROCESS create new user with public access
        if($this->page_no == 1) {

            
            $ship_option_id = $this->form['ship_option_id'];
            $ship_location_id = $this->form['ship_location_id'];
            $pay_option_id = $this->form['pay_option_id'];

            $output = Helpers::calcCartTotals($this->db,TABLE_PREFIX_SHOP,$this->temp_token,$ship_option_id,$ship_location_id,$error_tmp);
            if($error_tmp !== '') {
               $error = 'Could not calculate cart totals. ';
               if($this->debug) $error .= $error_tmp; 
               $this->addError($error); 
            } else {
                $sql = 'SELECT name FROM '.TABLE_PREFIX_SHOP.'ship_location WHERE location_id = "'.$this->db->escapeSql($ship_location_id).'" ';
                $this->data['ship_location'] = $this->db->readSqlValue($sql);
                $sql = 'SELECT name FROM '.TABLE_PREFIX_SHOP.'ship_option WHERE option_id = "'.$this->db->escapeSql($ship_option_id).'" ';
                $this->data['ship_option'] = $this->db->readSqlValue($sql);
                $sql = 'SELECT name,type_id,config FROM '.TABLE_PREFIX_SHOP.'pay_option WHERE option_id = "'.$this->db->escapeSql($pay_option_id).'" ';
                $this->data['pay'] = $this->db->readSqlRecord($sql);
                $this->data['pay_option'] = $this->data['pay']['name'];
                
                $this->data['totals'] = $output['totals'];
                $this->data['items'] = $output['items'];
            }

        } 
        
        //PROCESS additional info required
        if($this->page_no == 2) {
            
        }  
        
        //address details and user register if not logged in
        if($this->page_no == 3) {
            
            if($this->user_id == 0) {
                $exist = $this->user->getUser('EMAIL_EXIST',$this->form['user_email']);
                if($exist !== 0 ) {
                    $this->addError('Your email address is already in use! Please <a href="/login">login</a> with that email, or use a different address.');
                }    
            }

            //register new user if not exist
            if(!$this->errors_found and $this->user_id == 0) {
                
                $password = Form::createPassword();
                $access = 'USER';
                $zone = 'PUBLIC';
                $status = 'NEW';
                $name = $this->form['user_name'];
                $email = $this->form['user_email'];

                $this->user->createUser($name,$email,$password,$access,$zone,$status,$error_tmp);
                if($error_tmp !== '') {
                    $this->addError($error_tmp);
                } else {
                    $user = $this->user->getUser('EMAIL',$email);
                    $remember_me = true;
                    $days_expire = 30;
                    $this->user->manageUserAction('LOGIN_REGISTER',$user,$remember_me,$days_expire);
                    
                    $this->data['user_created'] = true;
                    $this->data['user_name'] = $name;   
                    $this->data['user_email'] = $email;   
                    $this->data['password'] = $password;
                    $this->data['user_id'] = $user[$this->user_cols['id']];
                    $this->user_id == $this->data['user_id'];
                }

            }


            if(!$this->errors_found) {
                $table_extend = TABLE_PREFIX_SHOP.'user_extend';  

                $data = [];
                $data['user_id'] = $this->user_id;
                $data['cell'] = $this->form['user_cell'];
                $data['ship_address'] = $this->form['user_ship_address'];
                $data['bill_address'] = $this->form['user_bill_address'];

                $extend = $this->db->getRecord($table_extend,['user_id'=>$data['user_id']]);
                if($extend === 0) {
                    $this->db->insertRecord($table_extend,$data,$error_tmp );
                } else {
                    unset($data['user_id']);
                    $where = ['extend_id' => $extend['extend_id']];
                    $this->db->updateRecord($table_extend,$data,$where,$error_tmp );
                }

                if($error_tmp !== '') {
                    $error = 'We could not save your details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            } 

            //finally update cart/order with all details
            if(!$this->errors_found) {
                $table_order = TABLE_PREFIX_SHOP.'order';
                $data = [];
                //NB: assign user id and remove temp token
                $data['user_id'] = $this->user_id;
                $data['temp_token'] = '';
                $data['date_create'] = date('Y-m-d H:i:s');
                $data['ship_address'] = $this->form['user_ship_address'];

                $where = ['temp_token' => $this->temp_token];
                $this->db->updateRecord($table_order,$data,$where,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not update order details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            }    
        }  
    }

    public function setupPageData($no)
    {
        if($no == 3) {
            //setup user data ONCE only, if a user is logged in
            if($this->user_id != 0 and !isset($this->data['user_id'])) {
                $this->data['user_id'] = $this->user_id;    
                $this->data['user_name'] = $this->user->getName();
                $this->data['user_email'] = $this->user->getEmail();
                //get extended user info
                $sql = 'SELECT * FROM '.TABLE_PREFIX_SHOP.'user_extend WHERE user_id = "'.$this->user_id.'" ';
                $user_extend = $this->db->readSqlRecord($sql);
                
                $this->form['user_email_alt'] = $user_extend['email_alt'];
                $this->form['user_cell'] = $user_extend['cell'];
                $this->form['user_ship_address'] = $user_extend['ship_address'];
                $this->form['user_bill_address'] = $user_extend['bill_address'];

                //NB: need to save $this->data as required in subsequent pages
                $this->saveData('data');
            }
        }
    }

}

?>


