<?php
// require_once
require_once(dirname(dirname(dirname(dirname(__FILE__)))). '/config.php');
                
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php'); 
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile
             
global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$selectDay = optional_param('date', 0, PARAM_RAW); 
//$timestamp = optional_param('timestamp', 0, PARAM_RAW); 

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//  $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
    require_login($course, true, $cm);

    $context = context_module::instance($cm->id);
    $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));      
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
    profile_load_data($USER); // user profile load
}

$data="";

$server_tz = new DateTimeZone(date_default_timezone_get());

if( $USER->timezone == '99'){ // default tz
    $user_tz = $server_tz;
} else {
    $user_tz = new DateTimeZone($USER->timezone);
}

//$sdate=DateTime::createFromFormat('D, d M Y H:i:s T',$timestamp);

$sdate=DateTime::createFromFormat('Y-m-d H:i:s', $selectDay . ' 00:00:00');
    $sdate->setTimeZone($user_tz);
    
$edate= clone $sdate;
    $edate->add(new DateInterval('PT24H'));

// 1 Day = 24*60*60 = 86400
// $nextDay = date('Y-m-d', strtotime($selectDay) + 86400 );

// mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));

$slots_list = $DB->get_records_sql("
    SELECT  starttime
    FROM {ejsappbooking_remlab_access} 
    WHERE username = ? AND ejsappid = ? 
    AND starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) AND starttime <= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' )
    ORDER BY starttime ASC", 
    array($USER->username, $labid, $sdate->format("Y-m-d H:i"), $edate->format("Y-m-d H:i")));

//echo $user_tz->getName() .'<br>';
//echo $sdate->format("Y-m-d H:i") .'<br>';
//echo $edate->format("Y-m-d H:i");

$data['busy-slots'] = [];
foreach ($slots_list as $slot) {
    $time = substr($slot->starttime,11,5);
    array_push($data['busy-slots'], $time );
}

/*

$slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration', array('practiceintro' => $practiceintro));
$durations = array("0" => 60, "1"=>30, "2"=>15, "3"=>5, "4"=>2 );
$slotsize = $durations[$slotduration];

$date = clone $sdate;

while ( $date < $edate ){
    
    if ( array_search($date->format('Y-m-d H:i:s'), $busy_starttimes )){
        array_push( $data['busy-slots'], $date->format('H:i'));   
    } 
    
    $date->modify('+'.$slotsize.' minutes');   
}

*/

echo json_encode($data);