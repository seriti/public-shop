<?php
namespace App\Shop;

use Seriti\Tools\Task as SeritiTask;

class Task extends SeritiTask
{
    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 

        if($login_user->getAccessLevel() === 'GOD') {
            $this->addBlock('SHIP',1,1,'Setup shipping');
            $this->addTask('SHIP','SHIP_OPTIONS','Shipping options');
            $this->addTask('SHIP','SHIP_LOCATIONS','Shipping locations');
            $this->addTask('SHIP','SHIP_COSTS','Shipping costs');

            $this->addBlock('PAYMENT',2,1,'Setup payment');
            $this->addTask('PAYMENT','PAY_OPTIONS','Payment options');

            $this->addBlock('USER',1,2,'User setup');
            $this->addTask('USER','USER_CLEAR','Remove orphaned user settings');

            //$this->addBlock('IMPORT',1,2,'Import products');
            //$this->addTask('IMPORT','IMPORT_PRODUCT','Import product data');
        }    
        
    }

    public function processTask($id,$param = []) {
        $error = '';
        $error_tmp = '';
        $message = '';
        $n = 0;
        
        
        if($id === 'SHIP_OPTIONS') {
            $location = 'ship_option';
            header('location: '.$location);
            exit;
        }

        if($id === 'SHIP_LOCATIONS') {
            $location = 'ship_location';
            header('location: '.$location);
            exit;
        }

        if($id === 'SHIP_COSTS') {
            $location = 'ship_cost';
            header('location: '.$location);
            exit;
        }
        
        if($id === 'PAY_OPTIONS') {
            $location = 'pay_option';
            header('location: '.$location);
            exit;
        }

        if($id === 'USER_CLEAR') {
            if(!isset($param['process'])) $param['process'] = false;  
                    
            if($param['process'] === 'clear') {
                $recs = Helpers::cleanUserData($this->db,$error_tmp);
                if($error_tmp === '') {
                    $this->addMessage('SUCCESSFULY removed '.$recs.' orphaned user setting records!');
                } else {
                    $error = 'Could not remove orphaned user data';
                    if($this->debug) $error .= ': '.$error_tmp;
                    $this->addError($error);   
                }     
            } else {
                $html = '';
                $class = 'form-control input-small';
                $html .= 'Please confirm that you want to remove all user settings where no valid user exists.<br/>'.
                         '<form method="post" action="?mode=task&id='.$id.'" enctype="multipart/form-data">'.
                         '<input type="hidden" name="process" value="clear"><br/>'.
                         '<input type="submit" name="submit" value="CLEAR ORPHANED RECORDS" class="'.$this->classes['button'].'">'.
                         '</form>';

                //display form in message box       
                $this->addMessage($html);      
            }
        }
           
    }

}

?>