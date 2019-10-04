<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';

$totals = $data['totals']
?>

<div id="checkout_div">
  
  <div class="row">
    <div class="col-sm-3">Ship to location:</div>
    <div class="col-sm-3"><?php echo $data['ship_location']; ?></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Shipping option:</div>
    <div class="col-sm-3"><?php echo $data['ship_option']; ?></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Payment option:</div>
    <div class="col-sm-3"><?php echo $data['pay_option']; ?></div>
  </div>


  <br/>
  <div class="row">
    <div class="col-sm-6">
    <?php 
    echo '<table class="table  table-striped table-bordered table-hover table-condensed">'.
         '<tr><th>Product</th><th>Quantity</th><th>Options</th><th>Price</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th></tr>';
    foreach($data['items'] as $item) {
        echo '<tr><td>'.$item['name'].'</td><td>'.$item['quantity'].'</td><td>'.$item['options'].'</td>'.
                 '<td>'.$item['price'].'</td><td>'.$item['subtotal'].'</td><td>'.$item['discount'].'</td><td>'.$item['tax'].'</td><td>'.$item['total'].'</td></tr>';
    }
    echo '</table>';
    
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Subtotal:</div>
    <div class="col-sm-3"><?php echo CURRENCY_SYMBOL.number_format($totals['subtotal'],2); ?></div>
  </div>
  <div class="row">
    <div class="col-sm-3">Subtotal includes item discounts:</div>
    <div class="col-sm-3"><?php echo  CURRENCY_SYMBOL.number_format($totals['item_discount'],2); ?></div>
  </div>  
  <div class="row">
    <div class="col-sm-3">Other discount:</div>
    <div class="col-sm-3"><?php echo  CURRENCY_SYMBOL.number_format($totals['discount'],2); ?></div>
  </div>  
  <div class="row">
    <div class="col-sm-3">Tax:</div>
    <div class="col-sm-3"><?php echo  CURRENCY_SYMBOL.number_format($totals['tax'],2); ?></div>
  </div> 
  <div class="row">
    <div class="col-sm-3">Shipping:</div>
    <div class="col-sm-3"><?php echo  CURRENCY_SYMBOL.number_format($totals['ship_cost'],2); ?></div>
  </div>  
  <div class="row">
    <div class="col-sm-3"><strong>Total amount due:</strong></div>
    <div class="col-sm-3"><strong><?php echo  CURRENCY_SYMBOL.number_format($totals['total'],2); ?></strong></div>
  </div>  

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

</div>