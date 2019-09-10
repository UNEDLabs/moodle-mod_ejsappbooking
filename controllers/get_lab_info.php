<?php

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.

$controller = new get_lab_info_controller($id);
$labinfo = $controller->do( $labid);

header('Content-Type: application/json');
echo json_encode($labinfo);

class get_lab_info_controller
{
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null );
    }
    
    public function do($labid){
        
        $data = '';
        
        $conflabs = $this->model->get_lab_conf($labid);
        
        $data['status']=$conflabs->active;

        $data['slot-size'] = $this->model->get_slot_size($conflabs->slotsduration);

        $practices = $this->model->get_practices($labid);
        
        foreach ($practices as $practice) {
            $practices_loc[$practice->practiceid] = $this->model->translate($practice->practiceintro);
        }

        $data['practices'] = $practices_loc;
        
        return $data;
    }
    
}