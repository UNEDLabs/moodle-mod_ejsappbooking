<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile
require_once($CFG->dirroot . '/filter/multilang/filter.php');

class ejsappbooking_model
{
    
    private $cm;
    private $course;
    private $ejsappbooking;
    private $context;
    private $multilang;
    private $remlabs;
    
    public function __construct($id, $n){
          
            global $DB, $USER, $CFG, $PAGE, $OUTPUT;
          
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
            //global $PAGE;
        
            //$PAGE->set_context($context);
            $this->multilang = new filter_multilang($this->context, array('filter_multilang_force_old' => 0));
          
            //$PAGE->set_context($context);   

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
        
        /*
        if ( !$this->remlabs ){
            
            $this->remlabs = $DB->get_records_sql("
                SELECT DISTINCT (a.id), a.name 
                FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} b ON a.id = b.ejsappid 
                WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1",
                array($USER->id, $this->course->id)
            );
        }
        */
        
        $query = 
            $DB->get_records('ejsapp', array('course' => $this->course->id, 'is_rem_lab' => 1));
        
        $this->remlabs = array();
        
        foreach ($query as $key => $remlab) {
            
            $ejsappcm = get_coursemodule_from_instance('ejsapp', $remlab->id, $this->course->id, false, MUST_EXIST);
            $modinfo = get_fast_modinfo($this->course);
            $ejsappcm = $modinfo->get_cm($ejsappcm->id);
            /*
            if (!$ejsappcm->uservisible) {
                unset($remlabs[$key]);
            }
            */
            if ( $ejsappcm->uservisible) {
                $item = new stdClass();
                $item->id = $remlab->id;
                $item->name = $remlab->name;
                array_push($this->remlabs, $item);
            }
        }
        
        return $this->remlabs;
      }
          
    public function get_practices($labid){
        global $DB;
    
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
        
           $tz_edit_url = $CFG->wwwroot . "/user/edit.php?id=".$USER->id."&returnto=profile"; 
           // $tz_edit_url = $CFG->wwwroot . "/user/editadvanced.php?id=".$USER->id; // ."#id_email"
            return $tz_edit_url;
      }
    
    public function get_current_server_time(){
         return new DateTime('NOW', $this->get_default_timezone());
        
      }
    
    public function translate($str){
        return $this->multilang->filter($str);
    }        
    
    public function get_lab_conf($labid){
        
        global $DB;
        
        $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));
        
        $labconf = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));

        return $labconf;

    }
        
    public function save_booking($labid, $practid, $starttime, $endtime){
        global $USER,$DB;
        
        $bk = new stdClass();
            $bk->username = $USER->username;
            $bk->ejsappid = $labid;
            $bk->practiceid = $practid;
            $bk->starttime = $starttime;
            $bk->endtime = $endtime;
            $bk->valid = 1;

        // Save in database.
        $bookid = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);
        
        return $bookid;
    }
    
    public function create_event($labid, $practid, $starttime, $endtime, $slot_size){
        global $DB, $USER;
        
        // Booking information - Event.
        $inittime = DateTime::createFromFormat('Y-m-d H:i:s', $starttime, $this->get_user_timezone());
        $finishtime = DateTime::createFromFormat('Y-m-d H:i:s', $endtime, $this->get_user_timezone());

        $lab = $DB->get_record('ejsapp', array('id' => $labid));
        $prac = $DB->get_record('block_remlab_manager_exp2prc', array('practiceid' => $practid,
            'ejsappid' => $labid));
        
        $event = new stdClass();
            $event->name = get_string('book_message', 'ejsappbooking') . ' ' . $lab->name .
                '. ' . $prac->practiceintro;
            $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
            $event->groupid = 0;
            $event->courseid = 0;
            $event->userid = $USER->id;
            $event->eventtype = 'user';                
            $event->timestart = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                $inittime->format('d'), $inittime->format('H'));
            $event->timeduration = $slot_size ;     
            $event->description = $event->description . get_string('plant', 'ejsappbooking') .
                ': ' . $this->multilang->filter($lab->name) . '. ' . $prac->practiceintro . '<br>';
            $event->description = $event->description . get_string('date', 'ejsappbooking') .
                ': ' . $inittime->format("Y-m-d"). '<br>';
            $event->description = $event->description . get_string('hour', 'ejsappbooking') .
                ': ' . $inittime->format("H:i") . '-' . $finishtime->format("H:i");

        // Create the event on the calendar.
        calendar_event::create($event);
    }
    
    public function send_message($labid, $practid, $starttime, $endtime){
        global $DB, $USER, $CFG;

        // Check message delivery.
        $init = explode(" ",$starttime);
        $finish = explode(" ",$endtime);
        

        $msgbody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

        $msgbody = $msgbody . get_string('plant', 'ejsappbooking') . ': ' .
            $this->multilang->filter($labid) . '. ' . $practid . '<br>';
        $msgbody = $msgbody . get_string('date', 'ejsappbooking') . ': ' .
            $init[0] . '<br>';
        $msgbody = $msgbody . get_string('hour', 'ejsappbooking') . ': ' .
            $init[1] . '-' . $finish[1] . '<br><br>';

        if (empty($CFG->messaging)) {
            // $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
            return;
        }

        // Format and send the message by the Admin user.
        $format = FORMAT_HTML;
        $cuser = $DB->get_record('user', array('id' => 2));
        @message_post_message($cuser, $USER, $msgbody, $format);

    }
    
    public function booking_exists($labid, $practid, $date){
        global $DB;
        
        $server_tz = $this->get_default_timezone();
        
        $sdate = clone $date;
            $sdate->setTimeZone($server_tz);
        
        $query = $DB->record_exists('ejsappbooking_remlab_access', array(
            'starttime' => $sdate->format("Y-m-d H:i:s"),
            'ejsappid' => $labid, 'practiceid' => $practid
            )
        );
        
        return ($query != null );
    }
    
    public function delete_booking($bookid){ // Check and delete booking
        global $DB, $USER;
        
        $record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $bookid));
        
        if ( ! $record ){
            return -1; // not found
        } else {
            $lab = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
            $prac = $DB->get_record('block_remlab_manager_exp2prc', array('practiceid' => $record->practiceid,
                'ejsappid' => $record->ejsappid));
            $name = get_string('book_message', 'ejsappbooking') . ' ' . $lab->name .
                '. ' . $prac->practiceintro;
            $inittime = DateTime::createFromFormat('Y-m-d H:i:s', $record->starttime, $this->get_user_timezone());
            $time = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                $inittime->format('d'), $inittime->format('H'));
            $event = $DB->get_record_sql('SELECT * FROM {event} WHERE userid = ? AND name = ? AND timestart = ?',
                array($USER->id, $name, $time));
            // Delete calendar´s event.
            if ($event) {
                $event = calendar_event::load($event->id);
                $event->delete($deleterepeated = false);
            }
            $success = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $bookid));
            if ( $success ){
                return 0; // success
            } else {
                return -2; // error deleting
            }
        }
        
    }
    
    public function get_sql_str_to_date_query(){
        global $CFG;
        
        if ( $CFG->dbtype == 'pgsql'){
            return "to_timestamp( ?, 'YYYY-MM-DD HH24:MI:SS' )";
        } if ( $CFG->dbtype == 'mysql' ) {
            return "STR_TO_DATE( ?, '%Y-%m-%d %T')";
        }
        
        return null;
    }
    
    public function get_day_bookings($labid, $date){
        global $USER, $DB;
        
        $user_tz = $this->get_user_timezone();
        $server_tz = $this->get_default_timezone();
        
        $sdate = clone $date;
            $sdate->setTime(0,0,0);
            $sdate->setTimeZone($server_tz);
        
        $edate= clone $sdate;
            $edate->add(new DateInterval('PT24H'));
        
        $dayaccesses = $DB->get_records_sql("
            SELECT starttime
            FROM {ejsappbooking_remlab_access} 
            WHERE username = ? AND ejsappid = ? 
            AND starttime >= ?
            AND starttime <=  ?
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $sdate->format('Y-m-d H:i:s'), $edate->format('Y-m-d H:i:s'),
                $this->get_sql_str_to_date_query(), $this->get_sql_str_to_date_query()));
        
         $list = [];
        
         foreach ($dayaccesses as $slot) {
             
            $date=DateTime::createFromFormat('Y-m-d H:i:s', $slot->starttime, $server_tz);
                $date->setTimeZone($user_tz);
             
            array_push($list, $date->format("H:i"));
        }
        
        return $list;
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
          
        $week_start =  DateTime::createFromFormat('Y-m-d H:i:s', $dmonday . ' 00:00:00', $user_tz);
            $week_start->setTimeZone($server_tz);
        
        $week_end =  DateTime::createFromFormat('Y-m-d H:i:s', $dsunday . ' 23:59:59', $user_tz);
            $week_end->setTimeZone($server_tz);
        
        $weekaccesses = $DB->get_records_sql("
            SELECT starttime FROM {ejsappbooking_remlab_access} 
            WHERE  username = ? AND ejsappid = ? 
            AND starttime >= ?
            AND starttime <= ?
            ORDER BY starttime ASC", 
            array($USER->username,$labid,$week_start->format('Y-m-d H:i:s'),
                  $week_end->format('Y-m-d H:i:s'), $this->get_sql_str_to_date_query(), $this->get_sql_str_to_date_query())
        );
        
        return $weekaccesses;
    }
    
    public function get_total_bookings($labid){ // Retrieving user´s bookings at the DB.
        global $USER, $DB;
        
        $start = $this->get_current_server_time();
        
        $useraccess = $DB->get_records_sql("
            SELECT starttime 
            FROM {ejsappbooking_remlab_access} 
            WHERE username = ? AND ejsappid = ? 
            AND starttime >= ?
            ORDER BY starttime ASC", 
            array($USER->username, $labid, $start->format('Y-m-d H:i:s'), $this->get_sql_str_to_date_query()));
        
        return $useraccess;
    }
    
    public function get_slot_size($slotsduration){
        
        $durations = array( "0" => 60, "1"=>30, "2"=>15, "3"=>5, "4"=>2 );
        
        return $durations[$slotsduration];
    }

    public function get_current_user_active_bookings($sdate){
        
        global $USER, $DB;
        
        $bookings = $DB->get_records_sql("
            SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro 
            FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
            INNER JOIN {block_remlab_manager_exp2prc} c ON a.practiceid = c.practiceid  
            WHERE a.ejsappid = c.ejsappid AND a.username = ? 
            AND a.starttime >= ?
            ORDER BY a.starttime",
            array( $USER->username, $sdate->format('Y-m-d H:i:s'), $this->get_sql_str_to_date_query()));
        
        return $bookings;
        
    }

}