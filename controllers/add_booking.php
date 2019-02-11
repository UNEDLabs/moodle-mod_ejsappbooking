<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$practid = optional_param('practid',0, PARAM_INT); // 
$date = optional_param('date', 0, PARAM_RAW);
$time = optional_param('time',0, PARAM_RAW);
// $timestamp = optional_param('timestamp',0, PARAM_RAW); // UTC format: Wed, 14 Jun 2017 07:00:00 GMT

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//  $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
    
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));      
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0)); 
    $conflabs = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));
}

profile_load_data($USER); // user profile load

$server_tz = new DateTimeZone(date_default_timezone_get());
$user_tz = new DateTimeZone($USER->timezone);


$sdate = new DateTime();
    $sdate->setTimeZone($user_tz);
    $sdate->setDate(substr($date, 0, 4), substr($date, 5, 2), substr($date, 8, 2));
    $sdate->setTime(substr($time,0,2), substr($time,3,2));
    $sdate->setTimeZone($server_tz);

$exit = 0;
$msg = get_string('submit-success', 'ejsappbooking');

    
// Retrieving user´s bookings at the DB.
$useraccess = $DB->get_records_sql("
    SELECT starttime 
    FROM {ejsappbooking_remlab_access} 
    WHERE username = ? AND ejsappid = ? 
    ORDER BY starttime ASC", 
    array($USER->username, $labid));

$userbooks = count($useraccess);

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

// Determine user´s bookings of the week.
$weekaccesses = $DB->get_records_sql("
    SELECT starttime FROM {ejsappbooking_remlab_access} 
    WHERE  username = ? AND ejsappid = ? 
    AND starttime >= to_timestamp( ? , 'YYYY-MM-DD HH24:MI')
    AND starttime <= to_timestamp( ? , 'YYYY-MM-DD HH24:MI')
    ORDER BY starttime ASC", 
    array($USER->username, $labid, $week_start->format('Y-m-d H:i'), $week_end->format('Y-m-d H:i'))
);

$weekbooks = count($weekaccesses);

//echo $weekbooks .'<br>';

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

$daybooks = count($dayaccesses);

// Check restrictions.
if ($userbooks >= $conflabs->totalslots) {
    $exit = -1;
    $number = $conflabs->totalslots - $userbooks;
    $msg = get_string('totalslots', 'ejsappbooking'). ': ' . $conflabs->totalslots;
    if ($number > 0) {
        $msg .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
    }
} else if ($weekbooks >= $conflabs->weeklyslots) {
    $exit = -2;
    $msg = get_string('weeklyslots', 'ejsappbooking') . '. ';
    $number = $conflabs->weeklyslots - $weekbooks;
    if ($number > 0) {
        $msg .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
    }
} else if ( $daybooks >= $conflabs->dailyslots) {
    $exit = -3;
    $msg = get_string('dailyslots', 'ejsappbooking')  . ': ' . $conflabs->dailyslots;
}

$slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration',
    array('practiceintro' => $practiceintro) );

$slotsperhour = $slotduration + 1;
$min = 60 / $slotsperhour;

$startdate = $sdate->format("Y-m-d H:i").':00';
    $sdate->modify('+'.$min.' minutes');
$enddate = $sdate->format("Y-m-d H:i").':59';

$bk = new stdClass();
    $bk->username = $USER->username;
    $bk->ejsappid = $labid;
    $bk->practiceid = $practid;
    $bk->starttime = date("Y-m-d H:i:s", strtotime($startdate));
    $bk->endtime = date("Y-m-d H:i:s", strtotime($enddate));
    $bk->valid = 1;

// Check if the book exists.

if ($DB->record_exists('ejsappbooking_remlab_access', 
        array('starttime' => $bk->starttime, 'ejsappid' => $labid, 'practiceid' => $practid))) {
    $msg=get_string('submit-error-exists', 'ejsappbooking'); 
    $exit=-4;
}

$data['exitCode']=$exit;
$data['exitMsg']=$msg;
$data['dayCount']= $daybooks;
$data['weekCount']= $weekbooks;
$data['totalCount']= $userbooks;

echo json_encode($data);

if ( $exit < 0 ){ // Doesn't meet restrictions
    exit ;
}

// Save in database.
$identificador = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);

// Booking information - Event.
$inittime = new DateTime($bk->starttime);
$finishtime = new DateTime($bk->endtime);

$event = new stdClass();
    $event->name = get_string('book_message', 'ejsappbooking') . ' '. $labid . '. ' . $practid;
    $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
    $event->groupid = 0;
    $event->courseid = 0;
    $event->userid = $USER->id;
    $event->eventtype = 'user';                
    $event->timestart = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                    $inittime->format('d'), $inittime->format('H'));
    $event->timeduration = $min ;     
    $event->description = $event->description . get_string('plant', 'ejsappbooking') .
        ': ' . $multilang->filter($labid) . '. ' . $practid . '<br>';
    $event->description = $event->description . get_string('date', 'ejsappbooking') .
        ': ' . $inittime->format("Y-m-d") . '<br>';
    $event->description = $event->description . get_string('hour', 'ejsappbooking') .
        ': ' . $inittime->format('H:i:s') . '-' . $finishtime->format('H:i:s');

// Create the event on the calendar.
calendar_event::create($event);

// Check message delivery.

// Booking information - Message.

$msgbody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

$msgbody = $msgbody . get_string('plant', 'ejsappbooking') . ': ' .
    $multilang->filter($labid) . '. ' . $practid . '<br>';
$msgbody = $msgbody . get_string('date', 'ejsappbooking') . ': ' .
    $inittime->format("Y-m-d") . '<br>';
$msgbody = $msgbody . get_string('hour', 'ejsappbooking') . ': ' .
    $inittime->format('H:i:s') . '-' . $finishtime->format('H:i:s') . '<br><br>';

if (empty($CFG->messaging)) {
    // $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
    return;
}

// Format and send the message by the Admin user.
$format = FORMAT_HTML;
$cuser = $DB->get_record('user', array('id' => 2));
@message_post_message($cuser, $USER, $msgbody, $format);