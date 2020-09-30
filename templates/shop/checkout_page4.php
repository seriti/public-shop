<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$textarea_param['class'] = 'form-control edit_input';

$totals = $data['totals'];
$pay_type = $data['pay']['type_id'];

if(isset($data['user_created']) and $data['user_created']) $user_created = true; else $user_created = false;

$button_text = 'Confirm Order ';
if($pay_type === 'EFT_TOKEN')  $button_text .= '& Email me payment instructions.';
if($pay_type === 'GATEWAY_FORM')  $button_text .= '& Proceed to '.$data['pay']['name'];

//************** paymemt gateway form need to be constructed already!!!!!!
?>

<div id="checkout_div">
  
  <div class="row">
    <div class="col-sm-6">
      <div class="row">
        <div class="col-sm-6">Ship to location:</div>
        <div class="col-sm-6"><?php echo $data['ship_location']; ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6">Shipping option:</div>
        <div class="col-sm-6"><?php echo $data['ship_option']; ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6">Payment option:</div>
        <div class="col-sm-6"><?php echo $data['pay_option']; ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6"><strong>Total amount due:</strong></div>
        <div class="col-sm-6"><strong><?php echo  CURRENCY_SYMBOL.number_format($totals['total'],2); ?></strong></div>
      </div>  

      <div class="row">
        <div class="col-sm-6">Your email address:</div>
        <div class="col-sm-6"><?php echo $data['user_email']; ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6">Your name:</div>
        <div class="col-sm-6"><?php echo $data['user_name']; ?></div>
      </div>

      <div class="row">
        <div class="col-sm-6">Your Cell:</div>
        <div class="col-sm-6"><?php echo $form['user_cell']; ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6">Ship to address:</div>
        <div class="col-sm-6"><?php echo nl2br($form['user_ship_address']); ?></div>
      </div>
      <div class="row">
        <div class="col-sm-6">Bill to address:</div>
        <div class="col-sm-6"><?php echo nl2br($form['user_bill_address']); ?></div>
      </div>
    </div>
    
    <div class="col-sm-6">
      <div class="row">
        <div class="col-sm-12">
          <?php 
          if($user_created) {
            echo '<h2>You are now registered with us and logged in. You have been emailed your password.</h2>';
          } 

          if($pay_type === 'GATEWAY_FORM') {
             echo '<h2>Click to proceed to payment gateway</h2>'; 
             echo $data['gateway_form']; 
          }
          
          if($pay_type === 'EFT_TOKEN') {
              echo '<h2>You have been emailed payment instructions.</h2>'; 
          } 
          
          ?>
        </div>
      </div>
    </div>

  </div>     

</div>