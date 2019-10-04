<?php
namespace App\Shop;

use Seriti\Tools\CURRENCY_ID;
use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

class Report extends ReportTool
{
     

    //configure
    public function setup() 
    {
        //$this->report_header = 'WTF';
        $param = [];
        $this->report_select_title = 'Select report';
        $this->always_list_reports = true;

        $param = ['input'=>['select_user','select_month_period']];
        $this->addReport('SALES_COMPLETE','Monthly completed sales',$param); 
        $this->addReport('SALES_NEW','Monthly created sales',$param); 
        
        //$param = ['input'=>['select_user','select_month_period']];
        //$this->addReport('SALES_NEW','Monthly performance over period',$param);
    
        $this->addInput('select_user','');
        $this->addInput('select_date_create','');
        $this->addInput('select_month_period',''); 
        $this->addInput('select_format','');
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        if($id === 'select_user') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $param['xtra'] = ['ALL'=>'All users'];
            $sql = 'SELECT user_id,CONCAT(name,":",email) FROM '.TABLE_USER.' WHERE zone = "PUBLIC" AND status <> "HIDE" ORDER BY name'; 
            if(isset($form['user_id'])) $user_id = $form['user_id']; else $user_id = 'ALL';
            $html .= Form::sqlList($sql,$this->db,'user_id',$user_id,$param);
        }

        if($id === 'select_date_create') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_create'])) $date_create = $form['date_create']; else $date_create = date('Y-m-d',mktime(0,0,0,date('m')-12,date('j'),date('Y')));
            $html .= Form::textInput('date_create',$date_create,$param);
        }

        if($id === 'select_month_period') {
            $past_years = 10;
            $future_years = 0;

            $param = [];
            $param['class'] = 'form-control input-small input-inline';
            
            $html .= 'From:';
            if(isset($form['from_month'])) $from_month = $form['from_month']; else $from_month = 1;
            if(isset($form['from_year'])) $from_year = $form['from_year']; else $from_year = date('Y');
            $html .= Form::monthsList($from_month,'from_month',$param);
            $html .= Form::yearsList($from_year,$past_years,$future_years,'from_year',$param);
            $html .= '&nbsp;&nbsp;To:';
            if(isset($form['to_month'])) $to_month = $form['to_month']; else $to_month = date('m');
            if(isset($form['to_year'])) $to_year = $form['to_year']; else $to_year = date('Y');
            $html .= Form::monthsList($to_month,'to_month',$param);
            $html .= Form::yearsList($to_year,$past_years,$future_years,'to_year',$param);
        }

        if($id === 'select_format') {
            if(isset($form['format'])) $format = $form['format']; else $format = 'HTML';
            $html.= Form::radiobutton('format','PDF',$format).'&nbsp;<img src="/images/pdf_icon.gif">&nbsp;PDF document<br/>';
            $html.= Form::radiobutton('format','CSV',$format).'&nbsp;<img src="/images/excel_icon.gif">&nbsp;CSV/Excel document<br/>';
            $html.= Form::radiobutton('format','HTML',$format).'&nbsp;Show on page<br/>';
        }

        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        //$options['format'] = $form['format'];
        
        if($id === 'SALES_COMPLETE') {
            $html .= Helpers::salesReport($this->db,'COMPLETE',$form['user_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'SALES_NEW') {
            $html .= Helpers::salesReport($this->db,'NEW',$form['user_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        
        return $html;
    }

}

?>