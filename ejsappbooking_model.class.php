<?php

defined('MOODLE_INTERNAL') || die();

class ejsappbooking_model {
    
      private $cm;
      private $course;
      private $ejsappbooking;
      private $context;
    
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
    
      public function get_user_timezone(){
          global $USER;
          
          if ( $USER->timezone == '99'){
            $tz_str = get_string('time_zone_default', 'ejsappbooking');
          } else {
            $tz_str = get_string('time_zone', 'ejsappbooking') . ' ' . $USER->timezone;
          }
          
          return $tz_str;
      }
    
      public function get_timezone_edit_url(){
            global $CFG, $USER;
            $tz_edit_url = $CFG->wwwroot . "/user/editadvanced.php?id=".$USER->id; // ."#id_email"
            return $tz_edit_url;
      }

}
