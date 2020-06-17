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
    protected $table_prefix = MODULE_SHOP['table_prefix'];
    
    //configure
    public function setup($param = []) 
    {
        $this->user = $this->getContainer('user');
        $this->temp_token = $this->user->getTempToken();

        //will return 0 if NO logged in user
        $this->user_id = $this->user->getId();

        $param['bread_crumbs'] = true;
        $param['strict_var'] = false;
        $param['csrf_token'] = $this->temp_token;
        parent::setup($param);

        //standard user cols
        $this->addVariable(array('id'=>'ship_option_id','type'=>'INTEGER','title'=>'Shipping option','required'=>true));
        $this->addVariable(array('id'=>'ship_location_id','type'=>'INTEGER','title'=>'Shipping location','required'=>true));
        $this->addVariable(array('id'=>'pay_option_id','type'=>'INTEGER','title'=>'Payment option','required'=>true));
        
        $this->addVariable(array('id'=>'user_email','type'=>'EMAIL','title'=>'Your email address','required'=>true));
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

            $output = Helpers::calcCartTotals($this->db,$this->table_prefix,$this->temp_token,$ship_option_id,$ship_location_id,$pay_option_id,$error_tmp);
            if($error_tmp !== '') {
               $error = 'Could not calculate cart totals. ';
               if($this->debug) $error .= $error_tmp; 
               $this->addError($error); 
            } else {
                $sql = 'SELECT name FROM '.$this->table_prefix.'ship_location WHERE location_id = "'.$this->db->escapeSql($ship_location_id).'" ';
                $this->data['ship_location'] = $this->db->readSqlValue($sql);
                $sql = 'SELECT name FROM '.$this->table_prefix.'ship_option WHERE option_id = "'.$this->db->escapeSql($ship_option_id).'" ';
                $this->data['ship_option'] = $this->db->readSqlValue($sql);
                $sql = 'SELECT name,type_id,config FROM '.$this->table_prefix.'pay_option WHERE option_id = "'.$this->db->escapeSql($pay_option_id).'" ';
                $this->data['pay'] = $this->db->readSqlRecord($sql);
                $this->data['pay_option'] = $this->data['pay']['name'];
                
                $this->data['totals'] = $output['totals'];
                $this->data['items'] = $output['items'];
                $this->data['order_id'] = $output['order_id'];
            }

        } 
        
        //PROCESS additional info required
        if($this->page_no == 2) {
            
        }  
        
        //address details and user register if not logged in
        if($this->page_no == 3) {
            
            //check if an existing user has not logged in
            if($this->user_id == 0) {
                $exist = $this->user->getUser('EMAIL_EXIST',$this->form['user_email']);
                if($exist !== 0 ) {
                    $this->addError('Your email address is already in use!');
                    $this->addMessage('Please <a href="/login">login</a> with that email, or use a different email address.');
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
                    //set user_id so wizard knows user created 
                    $this->user_id = $this->data['user_id'];

                    $mailer = $this->getContainer('mail');
                    $to = $email;
                    $from = ''; //default config email from used
                    $subject = SITE_NAME.' user checkout registration';
                    $body = 'Hi There '.$name."\r\n".
                            'You have been registered as a user with us. Please note your credentials below:'."\r\n".
                            'Login email: '.$email."\r\n".
                            'Login Password: '.$password."\r\n\r\n".
                            'Your are logged in for 30 days from device that you processed order from, unless you logout or delete site cookies.'."\r\n".
                            'You can at any point request a password reset or login token to be emailed to you from login screen.';

                    if($mailer->sendEmail($from,$to,$subject,$body,$error_tmp)) {
                        $this->addMessage('Success sending your registration details to['.$to.'] '); 
                    } else {
                        $this->addMessage('Could not email your registration details to['.$to.'] '); 
                        $this->addMessage('This is not a biggie. You are logged in from this device for 30 days, and you can always request a password reset or new login token from login screen using your email address.');
                    } 
                }

            }


            if(!$this->errors_found) {
                $table_extend = $this->table_prefix.'user_extend';  

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
                $table_order = $this->table_prefix.'order';
                $data = [];
                //NB: *** ASSIGN USER ID & REMOVE TEMP TOKEN *** this designates it as a valid order and not temp cart
                $data['user_id'] = $this->user_id;
                $data['date_create'] = date('Y-m-d H:i:s');
                $data['ship_address'] = $this->form['user_ship_address'];
                $data['status'] = 'ACTIVE';
                $data['temp_token'] = '';

                //$where = ['temp_token' => $this->temp_token];
                $where = ['order_id' => $this->data['order_id']];
                $this->db->updateRecord($table_order,$data,$where,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not update order details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                } 
            }

            //finally redirect to payment gateway if that option reuested
            if(!$this->errors_found) {
                if($this->data['pay']['type_id'] === 'EFT_TOKEN') {
                    //send user message with payment instructions
                    $param = ['cc_admin'=>true];
                    $subject = 'EFT Payment instructions';
                    $message = 'Please use payment Reference: Order-'.$this->data['order_id'].'<br/>'.
                               'We will ship your order once payment is received. <br/>'. 
                               'Our bank account details:<br/>'.nl2br($this->data['pay']['config']);

                    Helpers::sendOrderMessage($this->db,$this->table_prefix,$this->container,$this->data['order_id'],$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email you order details, but your order has been successfully processed. PLease check your account page for details.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } 
                }

                if($this->data['pay']['type_id'] === 'GATEWAY_FORM') {
                    //redirect based on json configuration
                    $config = json_decode($this->data['pay']['config'],true);


                }
            }    
        }  
    }

    public function setupPageData($no)
    {
        //if($no == 3) {}
        
        //setup user data ONCE only, if a user is logged in
        if($this->user_id != 0 and !isset($this->data['user_id'])) {
            $this->data['user_id'] = $this->user_id;    
            $this->data['user_name'] = $this->user->getName();
            $this->data['user_email'] = $this->user->getEmail();

            $this->saveData('data');

            //get extended user info
            $sql = 'SELECT * FROM '.$this->table_prefix.'user_extend WHERE user_id = "'.$this->user_id.'" ';
            $user_extend = $this->db->readSqlRecord($sql);
            
            if($user_extend != 0) {
                $this->form['user_email_alt'] = $user_extend['email_alt'];
                $this->form['user_cell'] = $user_extend['cell'];
                $this->form['user_ship_address'] = $user_extend['ship_address'];
                $this->form['user_bill_address'] = $user_extend['bill_address'];

                //NB: need to save $this->data as required in subsequent pages
                $this->saveData('form');
            }    
        }
        
    }

}

?>


