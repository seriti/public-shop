<?php 
namespace App\Shop;

use Seriti\Tools\Listing;

//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;
//use Seriti\Tools\Template;
//use Seriti\Tools\Image;
//use Seriti\Tools\Calc;
//use Seriti\Tools\Menu;

//use Seriti\Tools\DbInterface;
//use Seriti\Tools\IconsClassesLinks;
//use Seriti\Tools\MessageHelpers;
//use Seriti\Tools\ContainerHelpers;
//use Seriti\Tools\STORAGE;
//use Seriti\Tools\UPLOAD_DOCS;
//use Seriti\Tools\BASE_PATH;
//use Seriti\Tools\BASE_TEMPLATE;
use Seriti\Tools\BASE_URL;

use Seriti\Tools\STORAGE;
//use Seriti\Tools\BASE_UPLOAD_WWW;

use Psr\Container\ContainerInterface;

class ProductList extends Listing
{
    
    protected $table_prefix = MODULE_SHOP['table_prefix'];

    //configure
    public function setup($param = []) 
    {
        //Class accessed outside /App/Shop so cannot use TABLE_PREFIX constant
        $labels = MODULE_SHOP['labels'];
        $image_access = MODULE_SHOP['images']['access'];
        
        $currency = 'R';

        $image_popup = ['show'=>true,'width'=>600,'height'=>500];

        //javascript to process add to bid form button
        //cart_icon.innerHTML = "WTF";
        $action_callback = 'var cart_icon = document.getElementById("menu_cart");
                            cart_icon.style.display="inline";
                           ';

        $param = ['row_name'=>'Product','col_label'=>'name','show_header'=>false,'order_by'=>'name',
                  'image_pos'=>'LEFT','image_width'=>200,'no_image_src'=>BASE_URL.'images/no_image.png',
                  'col_options'=>'options','image_popup'=>$image_popup,'format'=>'MERGE_COLS', //'format'=>'MERGE_COLS' or 'STANDARD'
                  'action_route'=>BASE_URL.'public/ajax?mode=list_add',
                  'action_callback'=>$action_callback,
                  'action_button_text'=>'Add to '.$labels['order']]; 
        parent::setup($param);

        $this->addListCol(array('id'=>'product_id','type'=>'INTEGER','title'=>'Product ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addListCol(array('id'=>'category_id','type'=>'INTEGER','title'=>$labels['category'],'list'=>false,'tree'=>'CT'));
        $this->addListCol(array('id'=>'name','type'=>'STRING','title'=>'Name','class'=>'list_item_title'));
        $this->addListCol(array('id'=>'type_id','type'=>'INTEGER','title'=>$labels['type'],'join'=>'name FROM '.$this->table_prefix.'type WHERE type_id'));
        $this->addListCol(array('id'=>'description','type'=>'TEXT','title'=>'Description','class'=>'list_item_text'));
        $this->addListCol(array('id'=>'options','type'=>'TEXT','title'=>'Options','list'=>false));
        $this->addListCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price','prefix'=>$currency));
        
        //NB: must have to be able to search on products below category_id in tree
        $this->addSql('JOIN','JOIN '.$this->table_prefix.'category AS CT ON(T.category_id = CT.'.$this->tree_cols['node'].')');
        //only list products with status = OK 
        $this->addSql('WHERE','T.status = "OK"');
       //sort by primary category and then name
        $this->addSortOrder('CT.rank,T.name,T.description ',$labels['category'].', then Name then Description','DEFAULT');


        //$this->addListAction('edit',array('type'=>'edit','text'=>'edit','icon_text'=>'edit','pos'=>'R'));
        
        //these are UNIVERSAL TO ALL PRODUCTS, CAN BE OVERWRITTEN IN INDIVIDUAL PRODUCT OPTIONS 
        //NB: custom settings'list' are simple non associative array(default)
        //NB: if 'list'=>[] then only shows custom settings
        $this->addListAction('size',['type'=>'select','text'=>'Size:','list'=>[],'pos'=>'R','spacer'=>'<br/>']);
        $this->addListAction('colour',['type'=>'select','text'=>'Colour:','list'=>[],'pos'=>'R','spacer'=>'<br/>']);
        //$this->addListAction('size',['type'=>'select','text'=>'Size:','list'=>[10,11,12],'pos'=>'R']);
        //$this->addListAction('colour',['type'=>'select','text'=>'Colour:','list'=>['red','green','blue'],'pos'=>'R']);

        $sql_cat = 'SELECT id,CONCAT(IF(level > 1,REPEAT("--",level - 1),""),title) FROM '.$this->table_prefix.'category  ORDER BY rank';
        $this->addSelect('category_id',$sql_cat);
        
        $this->addSearch(array('name','description','category_id'),array('rows'=>2));

        $this->setupListImages(array('table'=>$this->table_prefix.'file','location'=>'PRD','max_no'=>100,'manage'=>false,
                                     'list'=>true,'list_no'=>1,'storage'=>STORAGE,'title'=>'Product','access'=>$image_access,
                                     'link_url'=>'not_used','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));

        /*                          
        $this->setupListFiles(array('table'=>$this->table_prefix.'files','location'=>'WPF','max_no'=>100,
                                'icon'=>'<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>&nbsp;&nbsp;manage',
                                'list'=>false,'list_no'=>1,'storage'=>STORAGE_WWW,
                                'link_url'=>'page_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
        */

        
    }

    protected function modifyRowFormatted($row_no,&$actions_left,&$actions_right,&$images,&$files,&$items)
    {
       $product_id = $items[$this->key['id']]['value'];

       $gallery_link = '<a href="javascript:open_popup(\'image_popup?id='.$product_id.'\','.$this->image_popup['width'].','.$this->image_popup['height'].')">'.
                        $this->icons['gallery'].'</a>';

       $items['name']['formatted'] .= '&nbsp;'.$gallery_link;
        
    }
}  

?>
