<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$textarea_param['class'] = 'form-control edit_input';

$totals = $data['totals']
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
          if(isset($data['user_created']) and $data['user_created']) {
            echo '<h2>You are now registered with us and logged in. You have been emailed your password.</h2>';
          } 

          echo '<h2>Your order has been processed and will be completed once payment is confirmed</h2>';

          
          ?>
          <input type="submit" name="Submit" value="Proceed to payment gateway" class="btn btn-primary">
        </div>
      </div>
    </div>

  </div>     

</div>