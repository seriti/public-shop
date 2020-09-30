<?php
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Html;

use App\Shop\Helpers;

$list_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$textarea_param['class'] = 'form-control edit_input';

$order = $data['order']['order'];
$payment_total = $data['order']['payment_total'];
$payment_due = $order['total'] - $payment_total;

$pay_type = $data['pay']['type_id'];
if($pay_type === 'EFT_TOKEN')  $button_text = 'Email me payment instructions.';
if($pay_type === 'GATEWAY_FORM')  $button_text = 'Proceed to '.$data['pay']['name'];

//NB: template for payments outside checkout wizard
?>

<div id="checkout_div">
  
  <div class="row">
    <div class="col-sm-3">
      <?php 
      $html = '';
      $html .= '<p>'.
               '<h2>Order details:</h2>'. 
               'Reference: <strong>Order-'.$order['order_id'].'</strong><br/>'.
               'Created on: <strong>'.Date::formatDate($order['date_create']).'</strong><br/> '.
               'Status: <strong>'.Helpers::getOrderStatusText($order['status']).'</strong><br/>'.
               'Items: '.$order['no_items'].' <a href="'.$item_href.'">(view items)</a><br/>'.
               'Ship method: '.$order['ship_option'].'<br/>'.
               'Ship location: '.$order['ship_location'].'<br/>'.
               'Ship address: '.$order['ship_address'].'<br/>'.
               'Sub total: '.CURRENCY_SYMBOL.$order['subtotal'].'<br/>'.
               'Shipping: '.CURRENCY_SYMBOL.$order['ship_cost'].'<br/>'.
               'Total: '.CURRENCY_SYMBOL.$order['total'].'<br/>'.
               '</p>'; 
      echo $html;    
      ?>
    </div>

    <div class="col-sm-3">
    <?php 
    $html = '';
    $html .= '<p>'.
             '<h2>Payment details:</h2>'. 
             'Total required: '.CURRENCY_SYMBOL.number_format($order['total'],2).'<br/>'.
             'Payments received: '.CURRENCY_SYMBOL.number_format($payment_total,2).'<br/>'.
             '<strong>Payment required: '.CURRENCY_SYMBOL.number_format($payment_due,2).'</strong><br/>'.
             //'Payment method: '.$order['pay_option'].'<br/>'.
             '</p>'; 
    echo $html;        
    ?>
    </div>
    
    <div class="col-sm-3">
      
      <?php 
     
      if($pay_type === 'GATEWAY_FORM') {
         echo '<h2>Payment gateway ready, click to proceed</h2>'; 
         echo $data['gateway_form']; 
      }
      
      if($pay_type === 'EFT_TOKEN') {
          echo '<h2>You have been emailed payment instructions.</h2>'; 
      } 
      
      ?>
    </div>

  </div>     

</div>