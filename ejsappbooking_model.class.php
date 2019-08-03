<?php

defined('MOODLE_INTERNAL') || die();


class ejsappbooking_model {
    
      private $cm;
      private $course;
      private $ejsappbooking;
      private $context;
      private $multilang;
    
      private $remlabs;
    
      public function __construct($id, $n){
          
            global $DB, $USER, $CFG;   
          
            if ($id) {
                $this->cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
                $this->course = $DB->get_record('course', array('id' => $this->cm->course), '*', MUST_EXIST);
                $this->ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $this->cm->instance), '*', MUST_EXIST);
            } else if ($n) {
                $this->ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $n), '*', MUST_EXIST);
                $this->course = $DB->get_record('course', array('id' => $this->ejsappbooking->course), '*', MUST_EXIST);
                $this->cm = get_coursemodule_from_instance('ejsappbooking', 
                        $this->ejsappbooking->id, $this->course->id, false, MUST_EXIST);
            } else {
                print_error('You must specify a course_module ID or an instance ID');
            }
            
            require_login($this->course, true, $this->cm);
            profile_load_data($USER); // user profile load
    
            $this->context = context_module::instance($this->cm->id);
            $this->multilang = new filter_multilang($this->context, array('filter_multilang_force_old' => 0));

            if ($CFG->version < 2013111899) { // Moodle 2.6 or inferior.
                add_to_log($course->id, 'ejsappbooking', 'view', "view.php?id={$this->cm->id}", $this->ejsappbooking->name, $this->cm->id);
            } else {
                $event = \mod_ejsappbooking\event\ejsappbooking_viewed::create(array(
                    'objectid' => $this->ejsappbooking->id,
                    'context' => $this->context
                ));
                $event->add_record_snapshot('course_modules', $this->cm);
                $event->add_record_snapshot('course', $this->course);
                $event->add_record_snapshot('ejsappbooking', $this->ejsappbooking);
                $event->trigger();
            }
          
          if ($CFG->version < 2016090100) {
                $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'ejsappbooking'));
            }

            //TOFIX
            // Check if the user has the capability to view the page - used when an assignment is set to hidden.
            require_capability('mod/ejsappbooking:view', $this->context); 
          
      }
    
      public function get_context(){
          return $this->context;
      }
    
      public function get_mod_url (){
          return '/mod/ejsappbooking/view.php' . http_build_query(array('id' => $this->cm->id));
      }
    
      public function get_mod_name(){
          return format_string($this->ejsappbooking->name);
      }
    
      public function get_course_name(){
          return format_string($this->course->fullname);
      }
    
      public function get_mod_intro(){
     
          $intro = ""; 

          if ( isset($this->ejsappbooking->intro) && ($this->ejsappbooking->intro != null) ){
            $intro = format_module_intro('ejsappbooking', $this->ejsappbooking, $this->cm->id);
          }
          
          return $intro;
      }
    
      public function get_remlabs(){
          // Get the remote laboratories in which the user is authorized to make bookings.
        global $DB,$USER;
        
        if ( !$this->remlabs ){
            
            $this->remlabs = $DB->get_records_sql("
                SELECT DISTINCT (a.id), a.name 
                FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} b ON a.id = b.ejsappid 
                WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1",
                array($USER->id, $this->course->id)
            );
        }
        return $this->remlabs;
      }
          
      public function get_practices(){
        global $DB;
    
        $labid=0;
          
        // Select practices.
        $practices = $DB->get_records_sql(
            "SELECT id, ejsappid, practiceid, practiceintro 
             FROM {block_remlab_manager_exp2prc} 
             WHERE ejsappid = ? ", array($labid));

        return  $practices;
      }
    
      public function get_user_timezone_str(){
          global $USER;
          
          if ( $USER->timezone == '99' ){
            $tz_str = get_string('time_zone_default', 'ejsappbooking');
          } else {
            $tz_str = get_string('time_zone', 'ejsappbooking') . ' ' . $USER->timezone;
          }
          
          return $tz_str;
      }
    
      public function get_user_timezone(){
            global $USER;
          
            if( $USER->timezone == '99'){
                return $this->get_default_timezone();
            } else {
                return new DateTimeZone($USER->timezone);
            }
      }
    
      public function get_default_timezone(){
            return new DateTimeZone(date_default_timezone_get()); // server tz
      }
    
      public function get_timezone_edit_url(){
            global $CFG, $USER;
            $tz_edit_url = $CFG->wwwroot . "/user/editadvanced.php?id=".$USER->id; // ."#id_email"
            return $tz_edit_url;
      }
    
      function get_current_server_time(){
         return new DateTime('NOW', $this->get_default_timezone());
        
      }
    
    public function get_lab_info($labid){
        global $DB;
        
        $data = '';
        
        $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));
        
        $conflabs = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));
        
        $data['status']=$conflabs->active;
        
        $durations = array("0" => 60, "1"=>30, "2"=>15, "3"=>5, "4"=>2 );

        $data['slot-size'] = $durations[$conflabs->slotsduration];

        $practices= $DB->get_records_sql("
            SELECT id, ejsappid, practiceid, practiceintro FROM {block_remlab_manager_exp2prc} 
            WHERE ejsappid = ? ", 
            array($labid));
        
        foreach ($practices as $practice) {
            $practices_loc[$practice->practiceid] = $this->multilang->filter($practice->practiceintro);
        }

        $data['practices'] = $practices_loc;
        
        return $data;
        
    }
    
    public function get_booked_slots($labid, $selectDay){
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();

        $sdate=DateTime::createFromFormat('Y-m-d H:i:s', $selectDay . ' 00:00:00');
            $sdate->setTimeZone($user_tz);

        $edate= clone $sdate;
            $edate->add(new DateInterval('PT24H'));
        
        $slots_list = $DB->get_records_sql("
            SELECT  starttime
            FROM {ejsappbooking_remlab_access} 
            WHERE username = ? AND ejsappid = ? 
            AND starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) AND starttime <= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' )
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $sdate->format("Y-m-d H:i"), $edate->format("Y-m-d H:i")));

        $data['busy-slots'] = [];
        foreach ($slots_list as $slot) {
            $time = substr($slot->starttime,11,5);
            array_push($data['busy-slots'], $time );
        }
        
        return $data;
    }
    
    public function add_booking($labid, $practid, $date, $time){
        
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();
        $server_tz = $this->get_default_timezone();
        
        $sdate = new DateTime();
            $sdate->setTimeZone($user_tz);
            $sdate->setDate(substr($date, 0, 4), substr($date, 5, 2), substr($date, 8, 2));
            $sdate->setTime(substr($time,0,2), substr($time,3,2));
       //     $sdate->setTimeZone($server_tz);

        $exit = 0;
        $msg = get_string('submit-success', 'ejsappbooking');
        
        $daybooks = count($this->get_day_bookings($labid,$sdate));
        $weekbooks = count($this->get_week_bookings($labid,$sdate));
        $totalbooks = count($this->get_total_bookings($labid));

        // Check restrictions.
        
        $sdate->setTimeZone($server_tz);
        
        $lab_conf = $this->get_lab_conf($labid);

        if ( $sdate < $this->get_current_server_time()){
            $exit = -5;
            $msg = "The requested time has expired";
        } else if ($this->booking_exists($labid,$practid,$sdate)) { // Check if the book exists.
            $msg = get_string('submit-error-exists', 'ejsappbooking'); 
            $exit = -4;
        } else if ( $totalbooks >= $lab_conf->totalslots ) {    
            $exit = -1;
            $number = $lab_conf->totalslots - $totalbooks;
            $msg = get_string('totalslots', 'ejsappbooking'). ': ' . $lab_conf->totalslots;
            if ($number > 0) {
                $msg .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            }
        } else if ( $weekbooks >= $lab_conf->weeklyslots) {
            $exit = -2;
            $msg = get_string('weeklyslots', 'ejsappbooking') . '. ';
            $number = $conflabs->weeklyslots - $weekbooks;
            if ($number > 0) {
                $msg .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            }
        } else if ( $daybooks >= $lab_conf->dailyslots) {
            $exit = -3;
            $msg = get_string('dailyslots', 'ejsappbooking')  . ': ' . $lab_conf->dailyslots;
        }

   //    $slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration', array('practiceintro' => $practiceintro));
   //    $slotduration = $lab_conf->slotsduration;

        $data['exitCode']=$exit;
        $data['exitMsg']=$msg;
        $data['dayCount']= $daybooks;
        $data['weekCount']= $weekbooks;
        $data['totalCount']= $totalbooks;

        if ( $exit < 0 ){ // Doesn't meet restrictions
            return $data;
        }
        
        $slot_size = 60 / ( $lab_conf->slotsduration + 1);
        
        //$startdate = $sdate->format("Y-m-d H:i").':00';
        $starttime=$sdate->format("Y-m-d H:i:s");
            $sdate->modify('+'.$slot_size.' minutes');
        $endtime = $sdate->format("Y-m-d H:i").':59';
        
        $this->save_booking($labid, $practid, $starttime, $endtime);
        $this->create_event($labid, $practid, $starttime, $endtime, $slot_size);
        $this->send_message($labid, $practid, $starttime, $endtime);
        
        return $data;
        
    }
    
    function save_booking($labid, $practid, $starttime, $endtime){
        global $USER,$DB;
        
        $bk = new stdClass();
            $bk->username = $USER->username;
            $bk->ejsappid = $labid;
            $bk->practiceid = $practid;
            $bk->starttime = $starttime;
            $bk->endtime = $endtime;
            $bk->valid = 1;

        // Save in database.
        $identificador = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);
    }
    
    function create_event($labid, $practid, $starttime, $endtime, $slot_size){
        global $USER;
        
        // Booking information - Event.
            $inittime= new date($starttime);
            $finishtime = new date($endtime);
        
        $event = new stdClass();
            $event->name = get_string('book_message', 'ejsappbooking') . ' '. $labid . '. ' . $practid;
            $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
            $event->groupid = 0;
            $event->courseid = 0;
            $event->userid = $USER->id;
            $event->eventtype = 'user';                
            $event->timestart = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                $inittime->format('d'), $inittime->format('H'));
            $event->timeduration = $slot_size ;     
            $event->description = $event->description . get_string('plant', 'ejsappbooking') .
                ': ' . $this->multilang->filter($labid) . '. ' . $practid . '<br>';
            $event->description = $event->description . get_string('date', 'ejsappbooking') .
                ': ' . $inittime->format("Y-m-d"). '<br>';
            $event->description = $event->description . get_string('hour', 'ejsappbooking') .
                ': ' . $inittime->format("H:i") . '-' . $finishtime->format("H:i");

        // Create the event on the calendar.
        calendar_event::create($event);
    }
    
    function send_message($labid, $practid, $starttime, $endtime){
        global $DB, $USER, $CFG;

        // Check message delivery.
        
        $inittime = $starttime;
        $finishtime = $endtime;

        $msgbody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

        $msgbody = $msgbody . get_string('plant', 'ejsappbooking') . ': ' .
            $this->multilang->filter($labid) . '. ' . $practid . '<br>';
        $msgbody = $msgbody . get_string('date', 'ejsappbooking') . ': ' .
            $inittime->format("Y-m-d") . '<br>';
        $msgbody = $msgbody . get_string('hour', 'ejsappbooking') . ': ' .
            $inittime->format('H:i:s') . '-' . $finishtime->format('H:i:s') . '<br><br>';

        if (empty($CFG->messaging)) {
            // $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
            return;
        }
        
        echo $msgbody . '<br>';

        // Format and send the message by the Admin user.
        $format = FORMAT_HTML;
        $cuser = $DB->get_record('user', array('id' => 2));
        @message_post_message($cuser, $USER, $msgbody, $format);

    }
    
    function booking_exists($labid, $practid, $sdate){
        global $DB;
        
        return $DB->record_exists('ejsappbooking_remlab_access', array(
            'starttime' => $sdate->format("Y-m-d H:i:s"),
            'ejsappid' => $labid, 'practiceid' => $practid
            )
        );
    }
    
    function get_lab_conf($labid){
        
        global $DB;
        
        $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));
        
        $conflabs = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));
        
        return $conflabs;

    }
    
    function get_day_bookings($labid, $sdate){
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();
        $server_tz = $this->get_default_timezone();
        
        $day_start = clone $sdate;
            $day_start->setTime(0,0);
            $day_start->setTimeZone($server_tz);

        $day_end = clone $sdate;
            $day_end->setTime(23,59);
            $day_end->setTimeZone($server_tz);
        
        $dayaccesses = $DB->get_records_sql("
            SELECT starttime
            FROM {ejsappbooking_remlab_access} 
            WHERE username = ? AND ejsappid = ? 
            AND starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' )
            AND starttime <= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' )
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $day_start->format('Y-m-d H:i'), $day_end->format('Y-m-d H:i')));
        
        return $dayaccesses;
    }
    
    public function get_week_bookings($labid,$sdate){ // Determine user´s bookings of the week.
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();
        $server_tz = $this->get_default_timezone();        
        
         $day = $sdate->format('w');

        if ($day == 0) {
            $monday = 6;
            $sunday = 0;
        } else {
            $monday = $day - 1;
            $sunday = 7 - $day;
        }

        $dmonday = strtotime('-' . $monday . 'day', strtotime($sdate->format('Y-m-d')));
        $dsunday = strtotime('+' . $sunday . 'day', strtotime($sdate->format('Y-m-d')));

        $dmonday = date('Y-m-d', $dmonday);
        $dsunday = date('Y-m-d', $dsunday);

        if ($dmonday < $sdate->format('Y-m-d')) { // no se tienen en cuenta las reservas pasadas ?
            $dmonday = $sdate->format('Y-m-d');
        }

        $week_start =  DateTime::createFromFormat('Y-m-d H:i', $dmonday . ' 00:00', $user_tz);
            $week_start->setTimeZone($server_tz);

        $week_end =  DateTime::createFromFormat('Y-m-d H:i', $dsunday . ' 23:59', $user_tz);
            $week_end->setTimeZone($server_tz);

        $weekaccesses = $DB->get_records_sql("
            SELECT starttime FROM {ejsappbooking_remlab_access} 
            WHERE  username = ? AND ejsappid = ? 
            AND starttime >= to_timestamp( ? , 'YYYY-MM-DD HH24:MI')
            AND starttime <= to_timestamp( ? , 'YYYY-MM-DD HH24:MI')
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $week_start->format('Y-m-d H:i'), $week_end->format('Y-m-d H:i'))
        );
        
        return $weekaccesses;
    }
    
    function get_total_bookings($labid){ // Retrieving user´s bookings at the DB.
        global $USER, $DB;
        
        $start = $this->get_current_server_time();
        
        $useraccess = $DB->get_records_sql("
            SELECT starttime 
            FROM {ejsappbooking_remlab_access} 
            WHERE username = ? AND ejsappid = ? 
            AND starttime >= to_timestamp( ? , 'YYYY-MM-DD HH24:MI')
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $start->format('Y-m-d H:i')));
        
        return $useraccess;
    }
    
    public function get_mybookings(){
        
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();
        
        $sdate = new DateTime();
            $sdate->setTimeZone($user_tz);
        
      //  $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));  

        $events2 = $DB->get_records_sql("
            SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro 
            FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
            INNER JOIN {block_remlab_manager_exp2prc} c ON a.practiceid = c.practiceid  
            WHERE a.ejsappid = c.ejsappid AND a.username = ? 
            AND a.starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) 
            ORDER BY a.starttime",
            array( $USER->username, $sdate->format('Y-m-d H:i') ));

        $data['bookings-list'] = [];

        foreach ($events2 as $event) {

            $ts=DateTime::createFromFormat('Y-m-d H:i:s' , $event->starttime);
                $ts->setTimeZone($user_tz);

            array_push( $data['bookings-list'], Array(
                 'id' =>  $event->id,
                 'labname' => $this->multilang->filter($event->name) . '. ' . $event->practiceintro,         
                 'day' => $ts->format('Y-m-d'),
                 'time' => $ts->format('H:i')
             )); //'timestamp' => $ts->format('D, d M Y H:i:s T')
        }
        
        return $data;
    
    }
    
    public function delete_booking($bookid){ // Check and delete booking
        global $DB;
        
        $record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $bookid));

        if ( ! $record ){
            $data['exitCode']=-1;
            $data['exitMsg']="Booking id not found " . $bookid;
        } else { 
            $lab = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
            $prac = $DB->get_record('block_remlab_manager_exp2prc', array(
                'practiceid' => $record->practiceid,
                'ejsappid' => $record->ejsappid));

            $inittime = new DateTime($record->starttime);
            $error = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $bookid));

            if ($error < 0 ){
                $data['exitCode']=-2;
                $data['exitMsg']="Error deleting record" . $error;
            } else {
                $data['exitCode']=0;
                $data['exitMsg']="Success";
            }
        }
        
        return $data;
        
    }
    
}
