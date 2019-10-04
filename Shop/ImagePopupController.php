<?php
namespace App\Shop;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Shop\Helpers;

class ImagePopupController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $user = $this->container->user;
        $s3 = $this->container->s3;

        //NB: TABLE_PREFIX constant not applicable as not called within admin module
        $module = $this->container->config->get('module','shop');
        $table_prefix = $module['table_prefix'];
        
        $product_id = $_GET['id'];
        $html = Helpers::getProductImageGallery($db,$table_prefix,$s3,$product_id);

        $template['html'] = $html;
        //$template['title'] = $title;
        //$template['javascript'] = $dashboard->getJavascript();
        
        return $this->container->view->render($response,'public_popup.php',$template);
    }
}