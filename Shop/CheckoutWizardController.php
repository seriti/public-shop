<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\CheckoutWizard;

class CheckoutWizardController
{
    protected $container;
        

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $cache = $this->container->cache;

        $temp_token = $this->container->user->getTempToken();

        //use temp token to identify user for duration of wizard
        $user_specific = false;
        $cache_name = 'checkout_wizard'.$temp_token;
        $cache->setCache($cache_name,$user_specific);

        $wizard_template = new Template(BASE_TEMPLATE);
        
        $wizard = new CheckoutWizard($db,$this->container,$cache,$wizard_template);
        $wizard->setup();        

        $html = $wizard->process();

        $template['html'] = $html;
        $template['title'] = 'Checkout';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'public.php',$template);
    }
}