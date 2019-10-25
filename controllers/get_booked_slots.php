<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');
require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_view_json.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT);

$controller = new get_booked_slots_controller($id);
$controller->dispatch();

class get_booked_slots_controller
{
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null);
    }
    
    public function dispatch(){
        $labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
        $selectDay = optional_param('date', 0, PARAM_RAW); 
        
        $bookings = $this->do(array('labid'=>$labid, 'selectDay' =>$selectDay));
        $view = new ejsappbooking_json_view();
        $view->render($bookings);

    }
    
    public function do($params){
        
        $labid=$params['labid'];
        $selectDay=$params['selectDay'];
        
        $sdate=DateTime::createFromFormat('Y-m-d H:i:s',  $selectDay . ' 00:00:00', 
            $this->model->get_default_timezone());
        
        $data['busy-slots'] = $this->model->get_day_bookings($labid, $sdate);
        
        return $data;
        
    }
}