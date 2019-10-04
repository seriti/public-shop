<?php
namespace App\Shop;

use App\Shop\Category;
use Psr\Container\ContainerInterface;

class CategoryController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $template['title'] = MODULE_LOGO.'Product categories';
        
        if($this->container->user->getAccessLevel() !== 'GOD') {
            $template['html'] = '<h1>Insufficient access rights!</h1>';
        } else {  
                    
            $table = TABLE_PREFIX.'category';

            $tree = new Category($this->container->mysql,$this->container,$table);

            $param = ['row_name'=>'Category','col_label'=>'title'];
            $tree->setup($param);
            $html = $tree->processTree();
            
            $template['html'] = $html;
            
            //$template['javascript'] = $tree->getJavascript();
        }    
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}