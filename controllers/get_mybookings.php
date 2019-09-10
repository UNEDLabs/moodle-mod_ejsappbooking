<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...

$controller = new get_mybookings_controller($id);
$bookings = $controller->do();

header('Content-Type: application/json');
echo json_encode($bookings);

class get_mybookings_controller {
    
    public function __construct($id){
        $this->model = new ejsappbooking_model($id, null);
    }
    
    public function do(){
        
        $user_tz = $this->model->get_user_timezone();
        $server_tz = $this->model->get_default_timezone();
                
        $sdate=DateTime::createFromFormat('Y-m-d H:i:s' , date('Y-m-d H:i:s'), $server_tz);

        $bookings = $this->model->get_current_user_active_bookings($sdate);

        $data['bookings-list'] = [];

        foreach ($bookings as $bk){

            $ts=DateTime::createFromFormat('Y-m-d H:i:s' , $bk->starttime, $server_tz);
                $ts->setTimeZone($user_tz);

            array_push($data['bookings-list'], Array(
                 'id' =>  $bk->id,
                 'labname' => $this->model->translate($bk->name) . '. ' . $bk->practiceintro,         
                 'day' => $ts->format('Y-m-d'),
                 'time' => $ts->format('H:i')
             )); //'timestamp' => $ts->format('D, d M Y H:i:s T')
        }
        
        return $data;
    }
}
          