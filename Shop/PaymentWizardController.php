<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\PaymentWizard;

class PaymentWizardController
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

        //user must already be logged in
        $user_specific = true;
        $cache_name = 'payment_wizard';
        $cache->setCache($cache_name,$user_specific);

        $wizard_template = new Template(BASE_TEMPLATE);
        
        $wizard = new PaymentWizard($db,$this->container,$cache,$wizard_template);
        $wizard->setup();        

        $html = $wizard->process();

        $template['html'] = $html;
        $template['title'] = 'Payment processing';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'public.php',$template);
    }
}