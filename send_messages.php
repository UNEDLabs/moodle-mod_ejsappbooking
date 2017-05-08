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
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// EJSApp booking system has been developed by:
// - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
// - Luis de la Torre: ldelatorre@dia.uned.es
// - Ruben Heradio: rheradio@issi.uned.es
//
// at the Computer Science and Automatic Control, Spanish Open University
// (UNED), Madrid, Spain.

/**
 * Sends an informative message (email and moodle message) to users when they receive permissions for booking a remote lab
 *
 * @package    mod_ejsappbooking
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

$labrecord = $DB->get_record('ejsapp', array('id' => $labid));
$multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
$labname = $multilang->filter($labrecord->name);
$messagebody = get_string('allow_remlabaccess', 'ejsappbooking') . $labname;

// Getting the message body.
$url = new moodle_url('/mod/ejsappbooking/send_messages.php', array('id' => $mycourseid));
$url->param('messagebody', $messagebody);
$url->param('format', $format);

require_login($mycourseid);

$title = get_string('sending_message', 'ejsappbooking');

$PAGE->set_url($url);
$PAGE->set_context(context_course::instance($mycourseid));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($title);

if (!$course = $DB->get_record('course', array('id' => $mycourseid))) {
    print_error('invalidcourseid');
}

$SESSION->emailto = array();
$SESSION->emailto[$mycourseid] = array();
$SESSION->emailselect[$mycourseid] = array('messagebody' => $messagebody);

$count = 0;
$userlist = array();

foreach ($_POST as $k => $v) {
    if (preg_match('/^(user|teacher)(\d+)$/', $k, $m)) {
        if (!array_key_exists($m[2], $SESSION->emailto[$mycourseid])) {
            if ($user = $DB->get_record('user', array('id' => $m[2]))) {
                $userlist[] = $user;
                $SESSION->emailto[$mycourseid][$m[2]] = $user;
                $count++;
            }
        }
    }
}

echo $OUTPUT->header();

// If messaging is disabled on site, we can still allow users with capabilities to send emails instead.
if (empty($CFG->messaging)) {
    echo $OUTPUT->notification(get_string('messagingdisabled', 'message'));
}

$usersid = unserialize($_SESSION['encoded_listed_users']);

// INIT OF UPDATE BOOKING RIGHTS AND ALREADY MADE BOOKINGS.
if (count($SESSION->emailto[$mycourseid])) {
    $good = true;
    if (!empty($CFG->noemailever)) {
        $tempcfg = $CFG->noemailever;
        $CFG->noemailever = true;
    }

    $selectedusers = array();
    for ($i = 0; $i < count($SESSION->emailto[$mycourseid]); $i++) {
        $selectedusers[] = $userlist[$i]->id;
        $updateconditions = array('bookingid' => $bookingid, 'userid' => $userlist[$i]->id, 'ejsappid' => $labid);
        if ($DB->get_field('ejsappbooking_usersaccess', 'allowremaccess', $updateconditions) == 0) {
            $good = $good && @message_post_message($USER, $userlist[$i], $messagebody, $format);
        }
        // Grant booking rights to the selected users.
        $updateid = $DB->get_field('ejsappbooking_usersaccess', 'id', $updateconditions);
        if ($updateid != null) {
            $allowremaccess = array('id' => $updateid, 'allowremaccess' => '1');
            $DB->update_record('ejsappbooking_usersaccess', $allowremaccess);
        } else {
            $allowremaccess = array('bookingid' => $bookingid, 'userid' => $userlist[$i]->id, 'ejsappid' => $labid,
                'allowremaccess' => '1');
            $DB->insert_record('ejsappbooking_usersaccess', $allowremaccess);
        }
    }

    // Create the list of non-selected users in order to delete their booking rights.
    $deleteremaccesses = array_diff($usersid, $selectedusers);
    $deleteremaccesses = array_unique($deleteremaccesses);
} else {
    // Create the list of non-selected users in order to delete their booking rights.
    $deleteremaccesses = $usersid;
}

// Delete booking rights and already made bookings of non-selected users.
foreach ($deleteremaccesses as $deleteremaccess) {
    if (has_capability('moodle/course:viewhiddensections', $context, $deleteremaccess, true) == false) {
        $updateconditions = array('bookingid' => $bookingid, 'userid' => $deleteremaccess, 'ejsappid' => $labid);
        $updateid = $DB->get_field('ejsappbooking_usersaccess', 'id', $updateconditions);
        if ($updateid != null) {
            $forbidremaccess = array('id' => $updateid, 'allowremaccess' => '0');
            $DB->update_record('ejsappbooking_usersaccess', $forbidremaccess);
        } else {
            $forbidremaccess = array('bookingid' => $bookingid, 'userid' => $deleteremaccess, 'ejsappid' => $labid,
                'allowremaccess' => '0');
            $DB->insert_record('ejsappbooking_usersaccess', $forbidremaccess);
        }
        $username = $DB->get_field('user', 'username', array('id' => $deleteremaccess));
        if ($username != null) {
            $DB->delete_records('ejsappbooking_remlab_access', array('username' => $username, 'ejsappid' => $labid));
        }
    }
}
// END OF UPDATE BOOKING RIGHTS AND ALREADY MADE BOOKINGS.

  $good = true;
  if ($good) {
      $contextid = $context->id;
      $redirection = <<<EOD
<script>
	location.href = '{$CFG->wwwroot}/mod/ejsappbooking/set_permissions.php?id=$id&courseid=$mycourseid&contextid=$contextid';
</script>
EOD;
      echo $redirection;
  } else {
      echo $OUTPUT->heading(get_string('messagedselectedusersfailed'));
  }

  if (!empty($CFG->noemailever)) {
      $CFG->noemailever = $tempcfg;
  }

echo $OUTPUT->footer();