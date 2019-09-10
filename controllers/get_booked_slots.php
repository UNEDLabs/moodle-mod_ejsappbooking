<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php'); 
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');
             
global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$selectDay = optional_param('date', 0, PARAM_RAW); 

$controller = new get_booked_slots_controller($id);
$bookings = $controller->do($labid,$selectDay);

header('Content-Type: application/json');
echo json_encode($bookings);

class get_booked_slots_controller
{
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null);
    }
    
    public function do($labid, $selectDay){
        
        $sdate=DateTime::createFromFormat('Y-m-d H:i:s',  $selectDay . ' 00:00:00', 
            $this->model->get_default_timezone());
        
        return $this->model->get_day_bookings($labid, $sdate);
        
    }
}