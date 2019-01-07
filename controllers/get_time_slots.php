<?php
// require_once
require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');
                
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');           
             
global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
//$selectDay = optional_param('date', 0, PARAM_RAW); 
$timestamp = optional_param('timestamp', 0, PARAM_RAW); 

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//  $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
    require_login($course, true, $cm);

    $context = context_module::instance($cm->id);
    $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));      
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
}

if ($CFG->dbtype == "mysql")
    $date_convert_func="DATE_FORMAT";
else if ($CFG->dbtype == "pgsql")
    $date_convert_func="TO_CHAR";

$data="";


$sdate=DateTime::createFromFormat('D, d M Y H:i:s T',$timestamp);
$edate= clone $sdate;
    $edate->add(new DateInterval('PT24H'));

//1 Day = 24*60*60 = 86400
// $nextDay = date('Y-m-d', strtotime($selectDay) + 86400 );

// mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));

$slots_list = $DB->get_records_sql("
    SELECT  starttime
    FROM {ejsappbooking_remlab_access} 
    WHERE username = ? AND ejsappid = ? 
    AND starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) AND starttime <= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' )
    ORDER BY starttime ASC", 
    array($USER->username, $labid, $sdate->format("Y-m-d H:i"), $edate->format("Y-m-d H:i")));


$busy_starttimes = [];

foreach ($slots_list as $slot) {
    $time = substr($slot->starttime,11,5);
    array_push($busy_starttimes, $slot->starttime );
}

$slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration', array('practiceintro' => $practiceintro));

$slotsize = 60 / ( $slotduration + 1);

// $data['slot-duration']=$minsperslot;

$data['time-slots'] = [];

$date = clone $sdate;

while ( $date < $edate ){
    $stime = $date->format('Y-m-d H:i:s');
    
    if ( array_search($stime, $busy_starttimes ) !== false ){
        $status = "busy";
    } else {
        $status = "free";    
    }

    $item =  [];
        $item['timestamp'] = $date->format('D, d M Y H:i:s T');
        $item['status'] = $status;
        
   array_push( $data['time-slots'], $item );
    
   $date->modify('+'.$slotsize.' minutes');    
}

echo json_encode($data);