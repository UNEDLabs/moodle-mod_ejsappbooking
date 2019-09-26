<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');
require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_view_json.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...

$controller = new add_booking_controller($id);
$controller->dispatch();

class add_booking_controller{
      
    public function __construct($courseid){
        $this->model = new ejsappbooking_model($courseid, null);
    }
    
    public function dispatch(){
        $labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
        $practid = optional_param('practid',0, PARAM_INT); // 
        $date = optional_param('date', 0, PARAM_RAW);
        $time = optional_param('time',0, PARAM_RAW);
    
        $exit = $this->do(array('labid' => $labid, 'practid' =>  $practid, 'date'=>$date, 'time' => $time));

        $view = new ejsappbooking_json_view();
        $view->render($exit);
    }
    
    public function do($params){
        
        $labid = $params['labid']; 
        $practid = $params['practid'];
        $date = $params['date'];
        $time = $params['time'];
          
        $user_tz = $this->model->get_user_timezone();
        $server_tz = $this->model->get_default_timezone();

        $sdate = new DateTime();
            $sdate->setTimeZone($user_tz);
            $sdate->setDate(substr($date, 0, 4), substr($date, 5, 2), substr($date, 8, 2));
            $sdate->setTime(substr($time,0,2), substr($time,3,2));

        $exit = 0;
        $msg = get_string('submit-success', 'ejsappbooking');
        
        $daybooks = count($this->model->get_day_bookings($labid,$sdate));
        $weekbooks = count($this->model->get_week_bookings($labid,$sdate));
        $totalbooks = count($this->model->get_total_bookings($labid));
        
        // Check restrictions.
     
        $lab_conf = $this->model->get_lab_conf($labid);
        $slot_size = $this->model->get_slot_size($lab_conf->slotsduration);
        
        $edate = clone $sdate;
            $edate->modify('+'.$slot_size.' minutes'); // restar tiempo de recuperación
        
        if (  $this->model->get_current_server_time() >= $edate ){
            $exit = -5;
            $msg = "The requested time has expired";
        } else if ($this->model->booking_exists($labid,$practid,$sdate)) { // Check if the book exists.
            $msg = get_string('submit-error-exists', 'ejsappbooking'); 
            $exit = -4;
        } else if ( $daybooks >= $lab_conf->dailyslots) {
            $exit = -3;
            $msg = get_string('dailyslots', 'ejsappbooking')  . ': ' . $daybooks;
            $data['dayMax'] = $lab_conf->dailyslots;
        } else if ( $weekbooks >= $lab_conf->weeklyslots) {
            $exit = -2;
            $msg = get_string('weeklyslots', 'ejsappbooking') . ': ' . $weekbooks;
            $data['weekMax'] = $lab_conf->weeklyslots;
            /*
            $number = $lab_conf->weeklyslots - $weekbooks;
            if ($number > 0) {
                $msg .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            }*/
        } else if ( $totalbooks >= $lab_conf->totalslots ) {    
            $exit = -1;
            $number = $lab_conf->totalslots - $totalbooks;
            $msg = get_string('totalslots', 'ejsappbooking'). ': ' . $totalbooks;
            $data['totalMax'] = $lab_conf->totalslots;
            
            if ($number > 0) {
                $msg .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            }
        } 

        $data['exitCode']=$exit;
        $data['exitMsg']=$msg;

        if ( $exit < 0 ){ // Doesn't meet restrictions
            return $data;
        }
        
        $sdate->setTimeZone($server_tz);
        $edate->setTimeZone($server_tz);
        
        $starttime=$sdate->format("Y-m-d H:i:s");
        $endtime = $edate->format("Y-m-d H:i:s");
        
        $data['bookid'] = $this->model->save_booking($labid, $practid, $starttime, $endtime);
        
        $this->model->create_event($labid, $practid, $starttime, $endtime, $slot_size);
        $this->model->send_message($labid, $practid, $starttime, $endtime);
        
        $data['dayCount']= $daybooks +1 ;
        $data['weekCount']= $weekbooks +1 ;
        $data['totalCount']= $totalbooks +1;
        
        return $data;
    }
}