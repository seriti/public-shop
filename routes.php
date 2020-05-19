<?php  
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/routes.php file within this framework
copy the "/shop" group into the existing "/admin" group within existing "src/routes.php" file 
*/

//*** BEGIN admin access ***
$app->group('/admin', function () {

    $this->group('/shop', function () {
        $this->any('/category', \App\Shop\CategoryController::class);
        $this->any('/product', \App\Shop\ProductController::class);
        $this->any('/product_image', \App\Shop\ProductImageController::class);
        $this->any('/dashboard', \App\Shop\DashboardController::class);
        $this->any('/setup', \App\Shop\SetupController::class);
        $this->get('/setup_data', \App\Shop\SetupDataController::class);
        $this->any('/report', \App\Shop\ReportController::class);
        $this->any('/seller', \App\Shop\SellerController::class);
        $this->any('/task', \App\Shop\TaskController::class);
        $this->any('/type', \App\Shop\TypeController::class);
        $this->any('/order', \App\Shop\OrderController::class);
        $this->any('/order_item', \App\Shop\OrderItemController::class);
        $this->any('/order_message', \App\Shop\OrderMessageController::class);
        $this->any('/order_file', \App\Shop\OrderFileController::class);
        $this->any('/order_payment', \App\Shop\OrderPaymentController::class);
        $this->any('/payment', \App\Shop\PaymentController::class);
        $this->any('/pay_option', \App\Shop\PayOptionController::class);
        $this->any('/ship_option', \App\Shop\ShipOptionController::class);
        $this->any('/ship_location', \App\Shop\ShipLocationController::class);
        $this->any('/ship_cost', \App\Shop\ShipCostController::class);
        $this->any('/user_extend', \App\Shop\UserExtendController::class);
    })->add(\App\Shop\Config::class);

})->add(\App\User\ConfigAdmin::class);
//*** END admin access ***

/*
The code snippet below is for use within an existing src/routes.php file within "seriti/slim3-skeleton" framework
replace the existing public access section with this code, or just replace the "shop specific routes" within your existing /public route .  
*/


//*** BEGIN public access ***
$app->redirect('/', '/public/home', 301);
$app->group('/public', function () {
    $this->redirect('', '/public/home', 301);
    $this->redirect('/', 'home', 301);
 
    $this->any('/register', \App\Website\RegisterWizardController::class);
    $this->any('/logout', \App\Website\LogoutController::class);

    //BEGIN shop sepcific routes
    $this->any('/ajax', \App\Shop\Ajax::class);
    $this->any('/cart', \App\Shop\CartController::class);
    $this->any('/checkout', \App\Shop\CheckoutWizardController::class);
    $this->get('/image_popup', \App\Shop\ImagePopupController::class);
    
    $this->group('/account', function () {
        $this->get('/dashboard', \App\Shop\AccountDashboardController::class);
        $this->get('/order', \App\Shop\AccountOrderController::class);
        $this->get('/order_item', \App\Shop\AccountOrderItemController::class);
        $this->get('/order_payment', \App\Shop\AccountOrderPaymentController::class);
        $this->any('/profile', \App\Shop\AccountProfileController::class);
    })->add(\App\Shop\ConfigAccount::class);
    //END shop sepcific routes

    //NB: this must come last in group
    $this->any('/{link_url}', \App\Website\WebsiteController::class);
})->add(\App\Website\ConfigPublic::class);
//*** END public access ***

