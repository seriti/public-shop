<?php 
namespace App\Shop;

use Seriti\Tools\Upload;

class ProductImage extends Upload 
{
  //configure
    public function setup($param = []) 
    {
        $id_prefix = 'PRD'; 

        $param = ['row_name'=>'Image',
                  'pop_up'=>true,
                  'col_label'=>'file_name_orig',
                  'update_calling_page'=>true,
                  'upload_access'=>IMAGE_ACCESS,
                  'prefix'=>$id_prefix];
        parent::setup($param);

        //resize parameters
        $resize = ['original'=>true,'thumb_nail'=>true,'crop'=>false,
                   'width'=>600,'height'=>400, 
                   'width_thumb'=>120,'height_thumb'=>80];

        //thumbnail display parameters           
        $thumbnail = ['list_view'=>true,'edit_view'=>true,
                      'list_width'=>120,'list_height'=>0,'edit_width'=>0,'edit_height'=>0];

        parent::setupImages(['resize'=>$resize,'thumbnail'=>$thumbnail]);

        //limit to web viewable images
        $this->allow_ext = array('Images'=>array('jpg','jpeg','gif','png')); 

        $param = [];
        $param['table']     = TABLE_PREFIX.'product';
        $param['key']       = 'product_id';
        $param['label']     = 'name';
        $param['child_col'] = 'location_id';
        $param['child_prefix'] = $id_prefix ;
        $param['show_sql'] = 'SELECT CONCAT("Images for product: ",name) FROM '.TABLE_PREFIX.'product WHERE product_id = "{KEY_VAL}"';
        $this->setupMaster($param);

        $this->addAction(array('type'=>'edit','text'=>'edit details of','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R','icon_text'=>'delete'));

        $this->info['ADD'] = 'If you have Mozilla Firefox or Google Chrome you should be able to drag and drop files directly from your file explorer.'.
                             'Alternatively you can click [Add Images] button to select multiple images for upload using [Shift] or [Ctrl] keys. '.
                             'Finally you need to click [Upload selected images] button to upload images to server.';
        
        //NB: only need to add non-standard file cols here, or if you need to modify standard file col setup
        $this->addFileCol(array('id'=>'caption','type'=>'STRING','title'=>'Caption','upload'=>true,'required'=>false));
    }
}
?>
