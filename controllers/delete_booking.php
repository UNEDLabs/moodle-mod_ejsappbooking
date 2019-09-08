<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$bookid = optional_param('bookid', 0, PARAM_INT); // Book ID

$controller = new delete_booking_controller($id);
$exit = $controller->do($bookid);
echo json_encode($exit);

class delete_booking_controller {
    
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null);
    }
    
    public function do($bookid){
        
        $data['exitCode']=$this->model->delete_booking($bookid);
        
        switch ($data['exitCode']) {
            case 0:
                $data['exitMsg']="Success";
                break;
            case -1:
                $data['exitMsg']="Booking id not found " . $bookid;
                break;
            case -2:
                $data['exitMsg']="Error deleting record" ;
                break;

        }
        
        return $data;
    }
    
}