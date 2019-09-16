<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');
require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_view_json.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...

$controller = new delete_booking_controller($id);
$controller->dispatch();

class delete_booking_controller {
    
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null);
    }
    
    public function dispatch(){
           
        $bookid = optional_param('bookid', 0, PARAM_INT); // Book ID
        
        $exit = $this->do(array('bookid'=>$bookid));

        $view = new ejsappbooking_json_view();
        $view->render($exit);
    }
    
    public function do($params){
        
        $bookid=$params['bookid'];
        
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