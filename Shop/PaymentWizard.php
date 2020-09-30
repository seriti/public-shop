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

use App\Payment\Helpers as PaymentHelpers;
use App\Payment\Gateway;

class PaymentWizard extends Wizard 
{
    protected $user;
   
    protected $order_id;
    protected $table_prefix;
    protected $table_prefix_pmt;
    
    //configure
    public function setup($param = []) 
    {
        $error = '';
        if(!defined('MODULE_SHOP')) $error .= 'Shop module not defined. ';
        if(!defined('MODULE_PAYMENT')) $error .= 'Payment module not defined. ';
        if($error !== '')  throw new Exception('CONSTANT_NOT_DEFINED: '.$error);
        
        $this->table_prefix = MODULE_SHOP['table_prefix'];
        $this->table_prefix_pmt = MODULE_PAYMENT['table_prefix'];

        $this->user = $this->getContainer('user');
        
        //for use in templates
        $this->data['user_id'] = $this->user->getId();
        $this->data['user_name'] = $this->user->getName();
        $this->data['user_email'] = $this->user->getEmail();

        $param = [];
        $param['bread_crumbs'] = true;
        $param['strict_var'] = false;
        //NB: Assumes user will always be logged in order to request payment
        $param['csrf_token'] = $this->user->getCsrfToken();
        parent::setup($param);

        //wizard variables
        $this->addVariable(array('id'=>'pay_option_id','type'=>'INTEGER','title'=>'Payment option','required'=>true));
                
        
        //pages and templates
        $this->addPage(1,'Select payment option','shop/payment_page1.php',['go_back'=>false]);
        $this->addPage(2,'Process payment','shop/payment_page2.php',['final'=>true]);
            

    }

    //tell wizard what order we are dealing with
    public function initialConfig() 
    {
        if(isset($_GET['order'])) {
            $this->data['order_id'] = Secure::clean('integer',$_GET['order']);
            $this->data['order'] = Helpers::getOrderDetails($this->db,$this->table_prefix,$this->data['order_id'],$error);
            if($error !== '') {
                throw new Exception('ACCOUNT_PAYMENT_ERROR: Could not make payment for unrecognised Order['.$this->data['order_id'].'].');
                exit;
            } 
            $this->data['pay_amount']  = $this->data['order']['order']['total'];
            $this->saveData('data');
            
            $this->form['pay_option_id'] = $this->data['order']['order']['pay_option_id'];
        }

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //process address details and user register if not logged in
        if($this->page_no == 1) {
            //get selected payment provider details
            $sql = 'SELECT name,provider_code FROM '.$this->table_prefix.'pay_option '.
                   'WHERE option_id = "'.$this->db->escapeSql($this->form['pay_option_id']).'" ';
            $this->data['pay'] = $this->db->readSqlRecord($sql);
            
            $provider = PaymentHelpers::getProvider($this->db,$this->table_prefix_pmt,'CODE',$this->data['pay']['provider_code']);
            if($provider == 0) {
                $this->addError('Payment provider not recognised');
            } else {
                $this->data['pay']['type_id'] = $provider['type_id'];
                $this->data['pay']['provider_id'] = $provider['provider_id'];
            }    

            //finally update order with payment details
            if(!$this->errors_found) {
                $table_order = $this->table_prefix.'order';
                $data = [];
                $data['pay_option_id'] = $this->form['pay_option_id'];
                $data['date_update'] = date('Y-m-d H:i:s');
                //$data['status'] = 'ACTIVE';
                
                $where = ['order_id' => $this->data['order_id']];
                $this->db->updateRecord($table_order,$data,$where,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not save order details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                } 
            }

            //finally SETUP payment gateway form if that option requested, or email EFT instructions
            if(!$this->errors_found) {
                
                if($provider['type_id'] === 'EFT_TOKEN') {
                    //send user message with payment instructions
                    $param = ['cc_admin'=>true];
                    $subject = 'EFT Payment instructions';
                    $message = 'Please use payment Reference: <strong>Order-'.$this->data['order_id'].'</strong><br/>'.
                               'We will ship your order once payment is received. <br/>'. 
                               'Our bank account details:<br/>'.nl2br($provider['config']);

                    Helpers::sendOrderMessage($this->db,$this->table_prefix,$this->container,$this->data['order_id'],$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email you order details, but your order has been successfully processed. PLease check your account page for details.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } 
                }

                if($provider['type_id'] === 'GATEWAY_FORM') {
                    $gateway = new Gateway($this->db,$this->container);
                    $gateway->setup('SHOP',$provider['provider_id']);

                    $reference = 'ORDER-'.$this->data['order_id'];
                    $reference_id =$this->data['order_id']; 
                    $amount = $this->data['pay_amount'];
                    $currency = CURRENCY_ID;                    
                    $gateway_form = $gateway->getGatewayForm($reference,$reference_id,$amount,$this->data['user_email'],$error_tmp);
                    if($error_tmp !== '') {
                        $error .= 'Could not setup payment gateway! Please try again later or select an alternative payment method.';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    } else {
                        $this->data['gateway_form'] = $gateway_form;
                    }
                }
            }
               
        } 

        //final page so no fucking processing possible moron
        if($this->page_no == 2) {

            

            

            
        } 
    }

    

}

?>


