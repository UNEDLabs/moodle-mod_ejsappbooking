<?php

// This file is part of the Moodle module "EJSApp booking system"
//
// EJSApp booking system is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// EJSApp booking system is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License is available on <http://www.gnu.org/licenses/>
//
// EJSApp booking system has been developed by:
//  - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain


/**
 * File that sends an informative message to users when they receive permissions for booking a remote lab by (1) email and (2) moodle message
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once('../../config.php');
require_login();

global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION;

require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/filter/multilang/filter.php');

$mycourseid = required_param('courseid', PARAM_RAW);
$id = required_param('id', PARAM_RAW);
$labid = required_param('labid', PARAM_RAW);
$bookingid = required_param('bookingid', PARAM_RAW);

$context = context_module::instance($id);

$send = true;
$preview = false;
$edit = false;
$format = FORMAT_PLAIN;

$lab_record = $DB->get_record('ejsapp', array('id'=>$labid));
$multilang = new filter_multilang($context, array('filter_multilang_force_old'=>0));
$lab_name = $multilang->filter($lab_record->name);
$messagebody = get_string('allow_remlabaccess', 'ejsappbooking') . $lab_name;

// <\getting the message body>

$url = new moodle_url('/mod/ejsappbooking/send_messages.php', array('id'=>$mycourseid));
$url->param('messagebody', $messagebody);
$url->param('format', $format);

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

if (!$course = $DB->get_record('course', array('id'=>$mycourseid))) {
  print_error('invalidcourseid');
}

require_login();

$coursecontext = context_course::instance($mycourseid);   // Course context
$systemcontext = context_system::instance();   // SYSTEM context

$SESSION->emailto = array();
$SESSION->emailto[$mycourseid] = array();
$SESSION->emailselect[$mycourseid] = array('messagebody' => $messagebody);

$count = 0;

foreach ($_POST as $k => $v) { 
	if (preg_match('/^(user|teacher)(\d+)$/',$k,$m)) {
    if (!array_key_exists($m[2],$SESSION->emailto[$mycourseid])) {
      if ($user = $DB->get_record_select('user', "id = ?", array($m[2]), 'id,firstname,lastname,idnumber,email,mailformat,lastaccess,lang')) {
        $SESSION->emailto[$mycourseid][$m[2]] = $user;
        $count++;
      }
    }
  }
}

$title = get_string('sending_message', 'ejsappbooking');

require_login($mycourseid);

$PAGE->set_context($coursecontext);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($title);

echo $OUTPUT->header();

// if messaging is disabled on site, we can still allow users with capabilities to send emails instead
if (empty($CFG->messaging)) {
  echo $OUTPUT->notification(get_string('messagingdisabled','message'));
}


// <UPDATE BOOKING RIGHTS AND ALREADY MADE BOOKINGS>
if (count($SESSION->emailto[$mycourseid])) {
  $good = true;
  if (!empty($CFG->noemailever)) {
    $temp_cfg = $CFG->noemailever;
    $CFG->noemailever = true;
  }

  $allrecords = $DB->get_records('ejsappbooking_usersaccess');
  foreach ($allrecords as $onerecord) {
    $allusers[] = $onerecord->userid;
  }
  foreach ($SESSION->emailto[$mycourseid] as $user) { 
    $selectedusers[] =  $user->id;
    $update_conditions = array('bookingid'=>$bookingid,'userid'=>$user->id,'ejsappid'=>$labid);
    $prev_access = $DB->get_field('ejsappbooking_usersaccess','allowremaccess',$update_conditions);
    if ($prev_access == 0) {
      $good = $good && @message_post_message($USER,$user,$messagebody,$format);
    }
    // Grant booking rights to the selected users
  	$update_id = $DB->get_field('ejsappbooking_usersaccess','id',$update_conditions);
  	if ($update_id != null) {
  	  $allowremaccess = array('id'=>$update_id, 'allowremaccess'=>'1');
  	  $DB->update_record('ejsappbooking_usersaccess',$allowremaccess);
    } else {
      $allowremaccess = array('bookingid'=>$bookingid,'userid'=>$user->id,'ejsappid'=>$labid,'allowremaccess'=>'1');
  	  $DB->insert_record('ejsappbooking_usersaccess',$allowremaccess);
    }	
  }
  
  // Create the list of non-selected users in order to delete their booking rights
  $users_no_remaccess = array_diff($allusers,$selectedusers);
  $users_no_remaccess = array_unique($users_no_remaccess);
} else {
  // Create the list of non-selected users in order to delete their booking rights
  $allrecords = $DB->get_records('ejsappbooking_usersaccess');
  foreach ($allrecords as $onerecord) {
    $allusers[] = $onerecord->userid;
  }
  $users_no_remaccess = $allusers;
}

// Delete booking rights and already made bookings of non-selected users
foreach ($users_no_remaccess as $user_no_remaccess) {
  if (has_capability('moodle/course:viewhiddensections', $context, $user_no_remaccess, true) == false) {
    $update_conditions = array('bookingid'=>$bookingid,'userid'=>$user_no_remaccess,'ejsappid'=>$labid);      
  	$update_id = $DB->get_field('ejsappbooking_usersaccess','id',$update_conditions);
   	if ($update_id != null) {
      $forbidremaccess = array('id'=>$update_id, 'allowremaccess'=>'0');
      $DB->update_record('ejsappbooking_usersaccess',$forbidremaccess);
    } else {
      $forbidremaccess = array('bookingid'=>$bookingid,'userid'=>$user_no_remaccess,'ejsappid'=>$labid,'allowremaccess'=>'0');
	  $DB->insert_record('ejsappbooking_usersaccess',$forbidremaccess);
    }
    $username = $DB->get_field('user', 'username', array('id'=>$user_no_remaccess));
    if ($username != null) {
      $DB->delete_records('ejsappbooking_remlab_access', array('username'=>$username,'ejsappid'=>$labid));
    }
  }
}
// </UPDATE BOOKING RIGHTS AND ALREADY MADE BOOKINGS>

  $good = true;
  if ($good) {
  $contextid = $context->id;
	$redirection = <<<EOD
<center>
<script>
	location.href='{$CFG->wwwroot}/mod/ejsappbooking/set_permissions.php?id=$id&courseid=$mycourseid&contextid=$contextid';
</script>
EOD;
 	echo $redirection;
  } else {
    echo $OUTPUT->heading(get_string('messagedselectedusersfailed'));
  }

  if (!empty($CFG->noemailever)) {
    $CFG->noemailever = $temp_cfg;
  }

echo $OUTPUT->footer();