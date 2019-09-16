<?php

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');
require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_view_json.class.php');

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...

$controller = new get_lab_info_controller($id);
$controller->dispatch();

class get_lab_info_controller
{
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null );
           
    }
    
    public function dispatch(){
        
        $labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
        
        $labinfo = $this->do(array('labid'=>$labid));

        $view = new ejsappbooking_json_view();
        $view->render($labinfo);

    }
    
    public function do($params){
        $labid=$params['labid'];
        
        $data = '';
        
        $conflab = $this->model->get_lab_conf($labid);
        
        if ( !$conflab ) return (object) null;
        
        $data['status']=$conflab->active;

        $data['slot-size'] = $this->model->get_slot_size($conflab->slotsduration);
        
        $data['maxDay'] = $conflab->dailyslots;
        $data['maxWeek'] = $conflab->weeklyslots;
        $data['maxTotal'] = $conflab->totalslots;

        $practices = $this->model->get_practices($labid);
        
        foreach ($practices as $practice) {
            $practices_loc[$practice->practiceid] = $this->model->translate($practice->practiceintro);
        }

        $data['practices'] = $practices_loc;
        
        return $data;
    }
    
}