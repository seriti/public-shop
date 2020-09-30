<?php
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Html;

use App\Shop\Helpers;

$list_param['class'] = 'form-control edit_input';

$order = $data['order']['order'];
$payments = $data['order']['payments'];
$payment_total = $data['order']['payment_total'];
$payment_due = $order['total'] - $payment_total;

//NB: template for payments outside checkout wizard
?>

<div id="checkout_div">

  <p>
  <?php
  echo '<h2>Hi there <strong>'.$data['user_name'].'</strong>. please proceed with payment process for following order.</h2>';
  
  ?>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">
    <?php 
    $html = '';
    $item_href = "javascript:open_popup('order_item?id=".$order['order_id']."',600,600)";
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
             //'Payment method: '.$order['pay_option'].'<br/>'.
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
    $button_txt = 'Proceed with '.CURRENCY_SYMBOL.number_format($payment_due,2).' payment'; 
    echo '<h2>Payment option:</h2>';
    $sql = 'SELECT option_id, name FROM '.MODULE_SHOP['table_prefix'].'pay_option WHERE status = "OK" ORDER BY sort';
    echo Form::sqlList($sql,$db,'pay_option_id',$form['pay_option_id'],$list_param);
    echo '<input type="submit" name="Submit" value="'.$button_txt.'" class="btn btn-primary">';
    ?>
    </div>  
  </div>
  
</div>