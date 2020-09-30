<?php 
namespace App\Shop;

use Exception;
use Seriti\Tools\Secure;
use Seriti\Tools\Crypt;
use Seriti\Tools\Validate;
use Seriti\Tools\Html;
use Seriti\Tools\Image;

use Seriti\Tools\MAIL_FROM;
use Seriti\Tools\BASE_URL;
use Seriti\Tools\TABLE_USER;
use Seriti\Tools\TABLE_SYSTEM;
use Seriti\Tools\SITE_NAME;

use Psr\Container\ContainerInterface;


//static functions for shop module
class Helpers {

    public static function getOrderStatusText($status)
    {
        $text = '';
        switch($status) {
            case 'NEW': $text = 'Order is still in shopping cart.'; break;
            case 'ACTIVE': $text = 'Order processed and awaiting payment'; break;
            case 'PAID': $text = 'Order payment received and awating shipment'; break;
            case 'SHIPPED': $text = 'Order has been shipped and awaiting confirmation of receipt'; break;
            case 'COMPLETED': $text = 'Order has been received and is completed.'; break;
            default: $text = 'Unrecognised order status['.$status.']';
        }

        return $text;
    }


    //called from payment module code after a transaction SUCCESSFULLY confirmed or notified
    public static function paymentGatewayOrderUpdate($db,$table_prefix,$order_id,$amount,&$error) 
    {
        $error = '';
        $table_order = $table_prefix.'order';
        $table_payment = $table_prefix.'payment';

        //check if payment exists
        $sql = 'SELECT payment_id,date_create,status '.
               'FROM '.$table_payment.' '.
               'WHERE order_id = "'.$db->escapeSql($order_id).'" AND amount = "'.$db->escapeSql($amount).'" ';
        $payment = $db->readSqlRecord($sql); 
        if($payment != 0) {
            $error .= 'Shop order['.$order_id.'] already has a payment amount['.$amount.'] @ '.$payment['date_create'];
        } else {
            $data = [];
            $data['order_id'] = $order_id;
            $data['date_create'] = date('Y-m-d H:i:s');
            $data['amount'] = $amount;
            $data['comment'] = $comment;
            $data['status'] = 'CONFIRMED';

            $payment_id = $db->insertRecord($table_payment,$data,$error);
            if($error === '') {
                //SEND SOME MESSAGE TO USER?
            }
        }  

        //update order status if all paid up
        if($error === '') {
            self::updateOrderStatus($db,$table_prefix,$order_id,$error);
        } 

    }   

    public static function updateOrderStatus($db,$table_prefix,$order_id,&$error)
    {
        $error = '';
        $table_order = $table_prefix.'order';
        $table_payment = $table_prefix.'payment';

        $sql = 'SELECT * FROM '.$table_order.' WHERE order_id = "'.$db->escapeSql($order_id).'" ';
        $order = $db->readSqlRecord($sql);
        if($order == 0) {
            $error .= 'Invalid order ID['.$order_id.']';
        } else {
            $sql = 'SELECT SUM(amount) FROM '.$table_payment.' WHERE order_id = "'.$db->escapeSql($order_id).'" AND status = "CONFIRMED" ';
            $total_confirmed = $db->readSqlValue($sql,0);
        }

        if($error === '') {
            if($total_confirmed - $order['total'] > -1.00) $paid_up = true; else $paid_up = false;
            if($paid_up and $order['status'] !== 'SHIPPED' and $order['status'] !== 'COMPLETED' ) {
                $sql = 'UPDATE '.$table_order.' SET status = "PAID" WHERE order_id = "'.$db->escapeSql($order_id).'" ';
                $db->executeSql($sql,$error);
                if($error === '') {
                    //SEND SOME MESSAGE TO USER?
                }
            }
        }
    }

    public static function checkOrderUpdateOk($db,$table_prefix,$order_id,&$error)
    {
        $error = '';
        $error_tmp = '';

        $table_order = $table_prefix.'order';

        $sql = 'SELECT T.order_id,T.date_create,T.date_update,T.status '.
               'FROM '.$table_order.' AS T '.
               'WHERE T.order_id = "'.$db->escapeSql($order_id).'" ';
        $data = $db->readSqlRecord($sql);       
        if($data == 0) {
            $error .= 'Could not find Order details.';
        } else {
            if($data['status'] === 'COMPLETED') $error .= 'You cannot modify a '.$data['status'].' Order ';
        }

        if($error === '') return true; else return false;
    }


    public static function getOrderDetails($db,$table_prefix,$order_id,&$error)
    {
        $error = '';
        $output = [];
        
        $table_product = $table_prefix.'product';
        $table_order = $table_prefix.'order';
        $table_item = $table_prefix.'order_item';
        $table_ship_location = $table_prefix.'ship_location';
        $table_ship_option = $table_prefix.'ship_option';
        $table_pay_option = $table_prefix.'pay_option';
        $table_payment = $table_prefix.'payment';

        $sql = 'SELECT O.order_id,O.date_create,O.status,O.no_items,O.subtotal,O.item_discount,O.discount,O.tax,O.total,'.
                      'O.ship_address,O.ship_location_id,O.ship_option_id,O.ship_cost,O.pay_option_id, '.
                      'O.user_id,U.name AS user_name,U.email AS user_email, '.
                      'L.name AS ship_location,S.name AS ship_option,'.
                      'P.name AS pay_option '.
               'FROM '.$table_order.' AS O '.
                     'LEFT JOIN '.TABLE_USER.' AS U ON(O.user_id = U.user_id) '.
                     'LEFT JOIN '.$table_ship_location.' AS L ON(O.ship_location_id = L.location_id) '.
                     'LEFT JOIN '.$table_ship_option.' AS S ON(O.ship_option_id = S.option_id) '.
                     'LEFT JOIN '.$table_pay_option.' AS P ON(O.pay_option_id = P.option_id) '.
               'WHERE O.order_id = "'.$db->escapeSql($order_id).'" ';
        $order = $db->readSqlRecord($sql);
        if($order === 0) {
            $error .= 'Invalid Order ID['.$order_id.']. ';
        } else {
            $output['order'] = $order;
        }

        $sql = 'SELECT I.item_id,I.product_id,P.name,I.price,I.quantity,I.subtotal,I.tax,I.discount,I.total,I.options,P.weight,P.volume,I.status '.
               'FROM '.$table_item.' AS I LEFT JOIN '.$table_product.' AS P ON(I.product_id = P.product_id) '.
               'WHERE I.order_id = "'.$db->escapeSql($order_id).'" ';
        $items = $db->readSqlArray($sql);
        if($items === 0) {
            $error .= 'Invalid or no products for Order ID['.$order_id.']. ';
        } else {
            $output['items'] = $items;
        }

        $sql = 'SELECT  date_create,amount,status '.
               'FROM '.$table_payment.' WHERE order_id = "'.$db->escapeSql($order_id).'" ';
        $output['payments'] = $db->readSqlArray($sql);

        if($output['payments'] == 0) {
            $output['payment_total'] = 0;
        } else {    
            $sql = 'SELECT  SUM(amount) '.
                   'FROM '.$table_payment.' WHERE order_id = "'.$db->escapeSql($order_id).'" ';
            $output['payment_total'] = $db->readSqlValue($sql,0);    
        }
        

        
        if($error !== '') return false; else return $output;
    }

    public static function sendOrderMessage($db,$table_prefix,ContainerInterface $container,$order_id,$subject,$message,$param=[],&$error)
    {
        $html = '';
        $error = '';
        $error_tmp = '';

        if(!isset($param['cc_admin'])) $param['cc_admin'] = true;

        $system = $container['system'];
        $mail = $container['mail'];

        //setup email parameters
        $mail_footer = $system->getDefault('SHOP_EMAIL_FOOTER','');
        $mail_param = [];
        $mail_param['format'] = 'html';
        if($param['cc_admin']) $mail_param['bcc'] = MAIL_FROM;
       
        $data = self::getOrderDetails($db,$table_prefix,$order_id,$error_tmp);
        if($data === false or $error_tmp !== '') {
            $error .= 'Could not get Order details: '.$error_tmp;
        } else {
            if($data['order']['user_id'] == 0 or $data['order']['user_email'] === '') $error .= 'No user data linked to Order';
        }    

        if($error === '') {
            $mail_from = ''; //will use default MAIL_FROM
            $mail_to = $data['order']['user_email'];
 
            $mail_subject = SITE_NAME.' Order ID['.$order_id.'] ';

            if($subject !== '') $mail_subject .= ': '.$subject;
            
            $mail_body = '<h1>Hi there '.$data['order']['user_name'].'</h1>';
            
            if($message !== '') $mail_body .= '<h3>'.$message.'</h3>';
            
            //do not want bootstrap class default
            $html_param = ['class'=>''];

            $mail_body .= '<h3>Order items:</h3>'.Html::arrayDumpHtml($data['items'],$html_param);

            if($data['payments'] !== 0) {
                $mail_body .= '<h3>Payments</h3>'.Html::arrayDumpHtml($data['payments'],$html_param);
            }
                
            $mail_body .= '<br/><br/>'.$mail_footer;
            
            $mail->sendEmail($mail_from,$mail_to,$mail_subject,$mail_body,$error_tmp,$mail_param);
            if($error_tmp != '') { 
                $error .= 'Error sending Order details to email['. $mail_to.']:'.$error_tmp; 
            }
        }

        if($error === '') return true; else return false;
    }
    
    public static function getProductSummary($db,$table_prefix,$s3,$product_id)
    {
        $html = '';

        if(!isset($param['access'])) $param['access'] = MODULE_SHOP['images']['access'];
        
        $no_image_src = BASE_URL.'images/no_image.png';

        $sql = 'SELECT product_id,name,description,status '.
               'FROM '.$table_prefix.'product '.
               'WHERE product_id = "'.$db->escapeSql($product_id).'" AND status <> "HIDE"';
        $product = $db->readSqlRecord($sql);
        if($product === 0) {
            $html = '<p>product no longer available.</p>';
            return $html;
        } else {
            $html .= '&nbsp;<strong>'.$product['name'].' (ID:'.$product['product_id'].')</strong>';
        }


        $location_id = 'PRD'.$product_id;
        $sql = 'SELECT file_id,file_name_tn AS file_name,file_name_orig AS name '.
               'FROM '.$table_prefix.'file WHERE location_id = "'.$db->escapeSql($location_id).'" '.
               'ORDER BY location_rank, file_date DESC LIMIT 1';
        $image = $db->readSqlRecord($sql);
        if($image != 0) {
            $url = $s3->getS3Url($image['file_name'],['access'=>$param['access']]);
            $title = $image['name'];
        } else {
            $url = $no_image_src;
            $title = 'No image available';
        } 

        $html = '<img class="list_image" src="'.$url.'" title="'.$title.'" align="left" height="50">'.$html;
        //$html = '<a href="javascript:open_popup(\'image_popup?id='.$product_id.'\',600,600)">'.$html.'</a>'; 

        return $html; 
    }


    public function getProductImageGallery($db,$table_prefix,$s3,$product_id,$param = [])
    {
        $html = '';

        if(!isset($param['access'])) $param['access'] = MODULE_SHOP['images']['access'];

        $sql = 'SELECT name,description,options '.
               'FROM '.$table_prefix.'product '.
               'WHERE product_id = "'.$db->escapeSql($product_id).'" AND status <> "HIDE"';
        $product = $db->readSqlRecord($sql);
        if($product === 0) {
            $html = '<h1>Product no longer available.</h1>';
            return $html;
        } else {
            $html .= '<h1>'.$product['name'].'</h1>';
        }


        $location_id = 'PRD'.$product_id;
        $sql = 'SELECT file_id,file_name,file_name_tn,caption AS title '.
               'FROM '.$table_prefix.'file WHERE location_id = "'.$db->escapeSql($location_id).'" ';
        $images = $db->readSqlArray($sql);
        if($images != 0) {
            //setup amazon links
            foreach($images as $id => $image) {
                $url = $s3->getS3Url($image['file_name'],['access'=>$param['access']]);
                $images[$id]['src'] = $url;
            }

            if(count($images) == 1) {
                foreach($images as $image) {
                    $html .= '<img src="'.$image['src'].'" class="img-responsive center-block">';    
                }  
            } else {  
                $options = array();
                $options['img_style'] = 'max-height:600px;';
                //$options['src_root'] = ''; stored on AMAZON
                $type = 'CAROUSEL'; //'THUMBNAIL'
                
                $html .= Image::buildGallery($images,$type,$options);
                
            }  
            
        } 

        return $html; 
    }

    public static function cleanUserData($db,&$error)
    {
        $error = '';

        $sql = 'DELETE E FROM '.TABLE_PREFIX.'user_extend AS E LEFT JOIN '.TABLE_USER.' AS U ON(E.user_id = U.user_id) '.
               'WHERE U.name is NULL ';
        $recs = $db->executeSql($sql,$error);

        return $recs;
    }

    //NB: Cart is a special case of an order
    //$table_prefix must be passed in as not always called within shop module
    public static function getCart($db,$table_prefix,$temp_token)  
    {
        $error = '';
        $table = $table_prefix.'order';
        
        $sql = 'SELECT * FROM '.$table.' '.
               'WHERE temp_token = "'.$db->escapeSql($temp_token).'" AND user_id = 0 AND status = "NEW" ';
        $cart = $db->readSqlRecord($sql);

        if($cart !==0 ) {
            $table = $table_prefix.'order_item';
            $sql = 'SELECT COUNT(*) FROM '.$table.' '.
                   'WHERE order_id = "'.$cart['order_id'].'" ';
            $cart['item_count'] = $db->readSqlValue($sql,0); 
        }
        
        return $cart;
    }


    public static function getCartItemTotals($db,$table_prefix,$order_id)  
    {
        $error = '';

        $table = $table_prefix.'order_item';
        $sql = 'SELECT SUM(subtotal) AS subtotal,SUM(tax) AS tax,SUM(discount) AS discount,SUM(total) AS total,'.
                      'SUM(weight) AS weight,SUM(volume) AS volume,COUNT(*) AS no_items '.
               'FROM '.$table.' '.
               'WHERE order_id = "'.$db->escapeSql($order_id).'" ';
        $totals = $db->readSqlRecord($sql);
        
        if($totals === 0) {
            unset($totals);
            $totals['subtotal'] = 0.00;
            $totals['discount'] = 0.00;
            $totals['tax'] = 0.00;
            $totals['total'] = 0.00;
            $totals['weight'] = 0.00;
            $totals['volume'] = 0.00;
            $totals['no_items'] = 0;
        }

        return $totals;
    }

    //NB: recalculates all item and cart totals based on latest product data, ONLY call BEFORE order finalised.
    public static function calcCartTotals($db,$table_prefix,$temp_token,$ship_option_id,$ship_location_id,$pay_option_id,&$error)  
    {
        $error = '';
        $error_tmp = '';
        $output = [];

        $table_cart = $table_prefix.'order';
        $table_ship = $table_prefix.'ship_cost';
        $table_item = $table_prefix.'order_item';
        $table_product = $table_prefix.'product';
        
        $cart = Helpers::getCart($db,$table_prefix,$temp_token);
        if($cart === 0) {
            $error .= 'Cart has expired';
        } else { 
            $order_id = $cart['order_id'];

            $sql = 'SELECT I.item_id,I.quantity,I.options,P.name,P.price,P.discount,P.tax,P.weight,P.volume '.
                   'FROM '.$table_item.' AS I LEFT JOIN '.$table_product.' AS P ON(I.product_id = P.product_id) '.
                   'WHERE order_id = "'.$db->escapeSql($order_id).'" ';
            $items = $db->readSqlArray($sql);
            if($items === 0) {
                $error .= 'Cart no longer exists.';
            } else {
                foreach($items as $item_id => $item) {
                    self::calcOrderItemTotals($item);
                    $items[$item_id] = $item;
                    //update database;
                    $where = ['item_id'=>$item_id];
                    //remove item fields not in table or required for update
                    unset($item['options']);
                    unset($item['name']);
                    $db->updateRecord($table_item,$item,$where,$error_tmp);
                    if($error_tmp !== '') $error .= 'Could not update cart item totals: '.$error_tmp;
                }
            }
        }    

        //get shipping costs
        if($error === '') {
            $sql = 'SELECT  option_id,cost_free,cost_max,cost_base,cost_weight,cost_volume,cost_item FROM '.$table_ship .' '.
                   'WHERE option_id = "'.$db->escapeSql($ship_option_id).'" AND location_id = "'.$db->escapeSql($ship_location_id).'" ';
            $ship_setup = $db->readSqlRecord($sql);
            if($ship_setup === 0) $error .= 'There is no valid shipping costs setup for your location and shipping option.'; 
        }


        //calculate cart totals and update options
        if($error === '') {
            $totals = self::getCartItemTotals($db,$table_prefix,$order_id);

            $cart_update = [];
            $cart_update['pay_option_id'] = $pay_option_id;
            $cart_update['ship_location_id'] = $ship_location_id;
            $cart_update['ship_option_id'] = $ship_option_id;
            $cart_update['subtotal'] = $totals['subtotal'];
            $cart_update['tax'] = $totals['tax'];
            //discount included in price
            $cart_update['item_discount'] = $totals['discount'];
            //global discount for coupons etc
            $cart_update['discount'] = 0.00;
            
            $cart_update['no_items'] = $totals['no_items'];
            $cart_update['weight'] = $totals['weight'];
            $cart_update['volume'] = $totals['volume'];

            if($ship_setup['cost_free'] > 0.1 and $totals['total'] > $ship_setup['cost_free']) {
                $ship_cost = 0.00;
            }  else {
                $ship_cost = $ship_setup['cost_base'] + 
                             $totals['no_items']*$ship_setup['cost_item'] +
                             $totals['weight']*$ship_setup['cost_weight'] +
                             $totals['volume']*$ship_setup['cost_volume'];

                if($ship_setup['cost_max'] > 0.1 and $ship_cost > $ship_setup['cost_max']) $ship_cost = $ship_setup['cost_max'];
            }          

            $cart_update['ship_cost'] = $ship_cost;
            //discount ignored as already in subtotal
            $cart_update['total'] = $cart_update['subtotal']+$cart_update['tax']+$cart_update['ship_cost'];

            $where = ['order_id'=>$order_id];
            $db->updateRecord($table_cart,$cart_update,$where,$error_tmp);
            if($error_tmp !== '') $error .= 'Could not update cart totals: '.$error_tmp;
        }


        if($error === '') {
            $output['order_id'] = $order_id;
            $output['items'] = $items;
            $output['totals'] = $cart_update;
                        
            return $output;
        } else {
            return false;
        }    
    }


    public static function setupOrder($db,$table_prefix,$temp_token,&$error)  
    {
        $error = '';
        $table = $table_prefix.'order';
        
        $sql = 'SELECT order_id FROM '.$table.' WHERE temp_token = "'.$db->escapeSql($temp_token).'" ';
        $order_id = $db->readSqlValue($sql,0);
        if($order_id === 0) {
            $data = [];
            $data['date_create'] = date('Y-m-d H:i:s');
            $data['status'] = 'NEW';
            $data['temp_token'] = $temp_token;

            $order_id = $db->insertRecord($table,$data,$error); 
        }

        return $order_id;
    }

    public static function addOrderItem($db,$table_prefix,$temp_token,$form,&$error) 
    {
        $error_tmp = '';
        $error = '';
        $submit = '';
        $options = '';
        //message for user, errors for marker/debug
        $message = '';

        //require product id and quantity at a minimum
        if(isset($form['product_id'])) {
            $product_id = Secure::clean('integer',$form['product_id']);
            unset($form['product_id']);
        } else {
            $error .= 'NO product ID specified.';
            $message .= 'Product not recognised. ';
        } 

        if(isset($form['quantity'])) {
            $quantity = Secure::clean('integer',$form['quantity']);
            unset($form['quantity']);
        } else {
            $quantity = 1;
        }  

        //submit button text
        if(isset($form['submit'])) {
            $submit = Secure::clean('integer',$form['submit']);
            unset($form['submit']);
        }  

        //validate product setup, options and extract options into simple text
        if($error === '') {
            $sql = 'SELECT product_id,name,status,options,quantity,price,discount,tax,weight,volume '.
                   'FROM '.$table_prefix.'product '.
                   'WHERE product_id = "'.$db->escapeSql($product_id).'" ';
            $product = $db->readSqlRecord($sql);
            if($product == 0 ) {
                $error .= 'Invalid product ID['.$product_id.']';
            } else {
                if($product['status'] !== 'OK') {
                    $error .= 'Product status['.$product['status'].'] invalid. ';
                    $message .= 'Product no longer available. ';
                }    
                if($product['quantity'] < $quantity) {
                    $error .= 'Product quantity['.$product['quantity'].'] insufficient. ';
                    $message .= 'Product quantity in stock insufficient. ';
                }  
                //NB: Only validates when custom product options set for product  
                if($product['options'] === '') {
                    foreach($form as $key => $value) {
                        $options .= $key.':'.$value."\r\n";
                    }
                } else {    
                    $product_options = self::parseOptions($product['options']);
                    foreach($product_options as $key => $values) {
                        if(!isset($form[$key])) {
                            $error .= 'Product option['.$key.'] not specified. ';
                            $message .= 'Product '.$key.' not specified. ';
                        } else {
                            if(!in_array($form[$key],$values)) {
                                $error .= 'Product option['.$key.'] selection['.$form[$key].'] not available. ';
                                $message .= 'Product '.$key.' '.$form[$key].' not available. ';
                            } else {
                                $options .= $key.':'.$form[$key]."\r\n";
                            }    
                        }
                    }
                } 
            }
        }    

        if($error === '') {
            $order_id = self::setupOrder($db,$table_prefix,$temp_token,$error_tmp);
            if($error_tmp !== '') {
                $error .= 'Could not setup order:'.$error_tmp;
                $message .= 'Could not setup order. ';
            }    
            
            if($error === '') {
                $sql = 'SELECT item_id,quantity FROM '.$table_prefix.'order_item '.
                       'WHERE order_id = "'.$db->escapeSql($order_id).'" AND '.
                             'product_id = "'.$db->escapeSql($product_id).'" AND '.
                             'options = "'.$db->escapeSql($options).'" ';
                $item_exist = $db->readSqlRecord($sql);

                $item = [];
                if($item_exist === 0) {
                    $item['order_id'] = $order_id;
                    $item['product_id'] = $product_id;
                    $item['quantity'] = $quantity;
                    $item['options'] = $options;
                } else {
                    $item['quantity'] = $item_exist['quantity']+$quantity;
                } 

                $item['price'] = $product['price'];
                //product discount and tax are text fields which need to be converted to a numerical format
                $item['discount'] = $product['discount'];
                $item['tax'] = $product['tax'];
                $item['weight'] = $product['weight'];
                $item['volume'] = $product['volume'];
                self::calcOrderItemTotals($item);

                $table = $table_prefix.'order_item';
                if($item_exist === 0) {
                    $db->insertRecord($table,$item,$error_tmp);
                } else {
                    $where = ['item_id'=>$item_exist['item_id']];
                    $db->updateRecord($table,$item,$where,$error_tmp);
                }    
                if($error_tmp !== '') {
                    $error .= 'Could not update order item: '.$error_tmp;
                    $message .= 'Could not save order item. ';
                }  else {
                    $message .= '#'.$quantity.' '.$product['name']."\r\n".$options.'Successfuly added to your order. ';
                }  
            }
        }    

        //message for user, error for debug
        return $message;
    }
    
    public static function calcOrderItemTotals(&$item)
    {
        //get discount per item BEFORE tax calculated
        if($item['discount'] === '') {
            $discount_per_item = 0;
        } else {
            $discount = self::parseDiscount($item['discount']);
            if($discount['type'] === 'percentage') {
                $discount_per_item = round(($item['price'] * $discount['rate']),2);
            } else {
                $discount_per_item = $discount['rate'];
            }
        }
        
        //tax calculated on discounted price
        $item['price'] = $item['price'] - $discount_per_item;

        //get tax per item AFTER discount if % based
        if($item['tax'] === '') {
            $tax_per_item = 0;
        } else {
            $tax = self::parseTax($item['tax']);

            if($tax['inclusive']) {
                $price_incl = $item['price'];
                if($tax['type'] === 'percentage') {
                    $item['price'] = round(($price_incl / (1 + $tax['rate'])),2);
                } else {
                    $item['price'] = $price_incl - $tax['rate'];
                }
                $tax_per_item = $price_incl - $item['price'];
            } else {
                if($tax['type'] === 'percentage') {
                    $tax_per_item = round(($item['price'] * $tax['rate']),2);
                } else {
                    $tax_per_item = $tax['rate'];
                }
            }
        }
                
        //finally calculate totals
        $item['subtotal'] = round($item['quantity']*$item['price'],2);
        $item['discount'] = round($item['quantity']*$discount_per_item,2);
        $item['tax'] = round($item['quantity']*$tax_per_item,2);
        //NB discount already included in price subtotal 
        $item['total'] = $item['subtotal']+$item['tax'];
        $item['weight'] = round($item['quantity']*$item['weight'],2);
        $item['volume'] = round($item['quantity']*$item['volume'],2);
    } 

    public static function parseTax($string) 
    {
        if($string === '') $string = '0';

        $tax = [];
        $tax['inclusive'] = true;
        $arr = explode(':',$string);
        if(count($arr) === 1) {
            $rate = $arr[0];
        } else {
            $type = strtolower($arr[0]);
            if(strpos($type,'excl') !== false) $tax['inclusive'] = false;
            $rate = $arr[1];
        }

        if(strpos($rate,'%') !== false) {
            $tax['type'] = 'percentage';
            $tax['rate'] = floatval(str_replace('%','',$rate)) / 100;
        } else { 
            $tax['type'] = 'flat';   
            $tax['rate'] = floatval($rate);
        }

        return $tax;
    }

    public static function parseDiscount($string) 
    {
        if($string === '') $string = '0';

        $discount = [];
        if(strpos($string,'%') !== false) {
            $discount['type'] = 'percentage';
            $discount['rate'] = floatval(str_replace('%','',$string)) / 100;
        } else {  
            $discount['type'] = 'flat';  
            $discount['rate'] = floatval($string);
        }

        return $discount;
    }


    public static function parseOptions($text) 
    {
        $options = [];

        if($text !== '') {
            $lines = explode("\r",$text); 
            foreach($lines as $line) {
                $param = explode(':',trim($line)); 
                if(count($param)>1) {
                    $key = trim($param[0]);
                    $values = explode(',',$param[1]);
                    $options[$key] = $values; 
                }
            }
        }

        return $options;
    }
    
    public static function salesReport($db,$status,$user_id,$from_month,$from_year,$to_month,$to_year,$options = [],&$error)  {
        $error = '';
        $error_tmp = '';
        $html = '';
        
        if(!isset($options['output'])) $options['output'] = 'HTML';
        
        Validate::monthInterval($from_month,$from_year,$to_month,$to_year,$error_tmp);
        if($error_tmp !== '') $error .= $error_tmp;

        if($error !== '') return false;

        //dates from first day of start month to last day of end month
        $date_from = date('Y-m-d',mktime(0,0,0,$from_month,1,$from_year));
        $date_to = date('Y-m-d',mktime(0,0,0,$to_month+1,0,$to_year));

        $sql = 'SELECT order_id, user_id, date_create, no_items, subtotal, tax, discount, total '.
               'FROM '.TABLE_PREFIX.'order  '.
               'WHERE status = "'.$db->escapeSql($status).'" AND ';
        if($status === 'COMPLETE') $sql .= 'date_update >= "'.$date_from.'" AND date_update <= "'.$date_to.'" ';
        if($status === 'NEW') $sql .= 'date_create >= "'.$date_from.'" AND date_create <= "'.$date_to.'" ';
        if($user_id === 'ALL') {
            //this excludes all unprocessed shopping carts
            $sql .= 'AND user_id <> 0 '; 
        } else {
            $sql .= 'AND user_id = "'.$db->escapeSql($user_id).'" '; 
        }    
        $sql .= 'ORDER BY order_id DESC ';

        $orders = $db->readSqlArray($sql);
        if($orders == 0) {
            $error .= 'No sales found matching your criteria';
        } else {
            if($options['output'] === 'HTML') {
                $html = Html::arrayDumpHtml($orders);
            }
        }                 

        return $html;
    }    

}


?>
