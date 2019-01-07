<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$now = optional_param('now',0, PARAM_RAW); // UTC format: Wed, 14 Jun 2017 07:00:00 GMT
   
$sdate=DateTime::createFromFormat('D, d M Y H:i:s T', $now);

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//  $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
}

$practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));      

// $baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));

// Show user´s bookings.
/*
$events = $DB->get_records_sql(
    "SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name 
    FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
    WHERE a.username = ? AND a.starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) 
    ORDER BY a.starttime ASC", 
    array( $USER->username, date("Y-m-d H:i")));

$result = count($events);
*/

//print_r($DB->get_records_sql("SELECT * FROM {ejsappbooking_remlab_access}"));
// exit;

// Get user bookings
$events2 = $DB->get_records_sql("
    SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro 
    FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
    INNER JOIN {block_remlab_manager_exp2prc} c ON a.practiceid = c.practiceid  
    WHERE a.ejsappid = c.ejsappid AND a.username = ? 
    AND a.starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) 
    ORDER BY a.starttime",
    array( $USER->username, $sdate ));

$data['bookings-list'] = [];

foreach ($events2 as $event) {
    
    $ts=DateTime::createFromFormat('Y-m-d H:i:s' , $event->starttime);

    array_push( $data['bookings-list'], Array(
         'id' =>  $event->id,
         'labname' => $multilang->filter($event->name) . '. ' . $event->practiceintro,
         'timestamp' => $ts->format('D, d M Y H:i:s T')
     ));
}
    
echo json_encode($data);