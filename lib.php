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
//  - Javier Pavon: javi.pavon@gmail.com
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain


/**
 * Library of interface functions and constants for module ejsappbooking
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle are here.
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Javier Pavón
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('ejsappbooking_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function ejsappbooking_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:             return true;
        case FEATURE_MOD_ARCHETYPE:         return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:        return false;
        case FEATURE_SHOW_DESCRIPTION:      return true;
        
        default:                        return null;
    }
}

/**
 * Saves a new instance of the ejsappbooking into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $ejsappbooking An object from the form in mod_form.php
 * @param mod_ejsappbooking_mod_form $mform
 * @return int The id of the newly inserted ejsappbooking record
 */
function ejsappbooking_add_instance($ejsappbooking) {
    global $DB;
      
    //ejsappbooking table: 
    $ejsappbooking->timecreated = time();
    $ejsappbooking->timemodified = time();
    $bookingid = $DB->insert_record('ejsappbooking', $ejsappbooking);
    
    //ejsappbooking_usersaccess table:
    $context = get_context_instance(CONTEXT_COURSE, $ejsappbooking->course);
    $users = get_enrolled_users($context);
    $course_ejsapps = $DB->get_records('ejsapp', array('course'=>$ejsappbooking->course));
    
    $ejsappbooking_usersaccess = new stdClass();
    $ejsappbooking_usersaccess->timecreated = time();
    $ejsappbooking_usersaccess->timemodified = time();
    $ejsappbooking_usersaccess->bookingid = $bookingid;
    
    //Grant remote access to admin user:
    $ejsappbooking_usersaccess->allowremaccess = 1; 
    $ejsappbooking_usersaccess->userid = 2;  
    foreach ($course_ejsapps as $course_ejsapp) {
      if ($course_ejsapp->is_rem_lab == 1) {
        $ejsappbooking_usersaccess->ejsappid = $course_ejsapp->id;
        $DB->insert_record('ejsappbooking_usersaccess', $ejsappbooking_usersaccess);
      }
    }
    
    //Rest of users: 
    foreach ($users as $user) {
      $ejsappbooking_usersaccess->userid = $user->id;
      if ($user->id != 2) {
        if (!has_capability('moodle/course:viewhiddensections', $context, $user->id, false)) {
          $ejsappbooking_usersaccess->allowremaccess = 0;
        } else {
          $ejsappbooking_usersaccess->allowremaccess = 1;
        }
        foreach ($course_ejsapps as $course_ejsapp) {
          if ($course_ejsapp->is_rem_lab == 1) {
            $ejsappbooking_usersaccess->ejsappid = $course_ejsapp->id;
            $DB->insert_record('ejsappbooking_usersaccess', $ejsappbooking_usersaccess);
          }
        }
      }
    }
      
    return $bookingid;
}

/**
 * Updates an instance of the ejsappbooking in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $ejsappbooking An object from the form in mod_form.php
 * @param mod_ejsappbooking_mod_form $mform
 * @return boolean Success/Fail
 */
function ejsappbooking_update_instance($ejsappbooking) {
    global $DB;

    $ejsappbooking->timemodified = time();
    $ejsappbooking->id = $ejsappbooking->instance;

    return $DB->update_record('ejsappbooking', $ejsappbooking);
}

/**
 * Removes an instance of the ejsappbooking from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function ejsappbooking_delete_instance($id) {
    global $DB;

    if (! $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $id))) {
        return false;
    }
    
    $ejsappbooking_usersaccess_delete = $DB->get_records('ejsappbooking_usersaccess', array('bookingid'=>$ejsappbooking->id));   

    # Delete dependent records #

    $DB->delete_records('ejsappbooking', array('id' => $ejsappbooking->id));
    foreach ($ejsappbooking_usersaccess_delete as $ejsappbooking_delete) {
      $DB->delete_records('ejsappbooking_usersaccess', array('id' => $ejsappbooking_delete->id));
    }

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function ejsappbooking_user_outline($course, $user, $mod, $ejsappbooking) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $ejsappbooking the module instance record
 * @return void, is supposed to echp directly
 */
function ejsappbooking_user_complete($course, $user, $mod, $ejsappbooking) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in ejsappbooking activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function ejsappbooking_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link ejsappbooking_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function ejsappbooking_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see ejsappbooking_get_recent_mod_activity()}

 * @return void
 */
function ejsappbooking_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function checks whether all users and all ejsapps are in the ejsappbooking_usersaccess database. If not, it add them. It is necessary when a ejsappbooking instance has already been added to a course and then new ejsapp are added or new users are enrolled to that course. Also, the function checks if there are users and ejsapps in the ejsappbooking_usersaccess database that no longer exist. In that case, it deletes them.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function ejsappbooking_cron () {
    global $DB;
    
    //ADDING NEW USERS AND/OR EJSAPPS:
    $ejsappbooking_usersaccess->timecreated = time();
    $ejsappbooking_usersaccess->timemodified = time();
    
    //Get all ejsappbooking instances' ids from the ejsappbooking data table.
    $ejsappbookings = $DB->get_records('ejsappbooking');
 
    foreach ($ejsappbookings as $ejsappbooking) { 
      $ejsappbooking_usersaccess->bookingid = $ejsappbooking->id;
      //Get context of the course to which ejsappbooking belongs to.
      $context = get_context_instance(CONTEXT_COURSE, $ejsappbooking->course);
      $users = get_enrolled_users($context);
      $course_ejsapps = $DB->get_records('ejsapp', array('course'=>$ejsappbooking->course));
      foreach ($course_ejsapps as $course_ejsapp) {
        if ($course_ejsapp->is_rem_lab == 1) {
          $ejsappbooking_usersaccess->ejsappid = $course_ejsapp->id;                              
          foreach ($users as $user) {
            $ejsappbooking_usersaccess->userid = $user->id;
            if (!has_capability('moodle/course:viewhiddensections', $context, $user->id, false)) {
              $ejsappbooking_usersaccess->allowremaccess = 0; 
            } else {
              $ejsappbooking_usersaccess->allowremaccess = 1;
            }
            if (!$ejsapp_exists = $DB->get_record('ejsappbooking_usersaccess', array('ejsappid' => $course_ejsapp->id, 'userid' => $user->id))) {
              if ($user->id != 2) {
                //Not admin users:
                $DB->insert_record('ejsappbooking_usersaccess', $ejsappbooking_usersaccess);
              }
            }
          }
          //Grant remote access to admin user:
          $ejsappbooking_usersaccess->userid = 2;
          $ejsappbooking_usersaccess->allowremaccess = 1;
          $DB->insert_record('ejsappbooking_usersaccess', $ejsappbooking_usersaccess);
        } 
      }   
    }
    
    //DELETING OLD USERS AND/OR EJSAPPS:
    foreach ($ejsappbookings as $ejsappbooking) { 
      $bookingid = $ejsappbooking->id;
      //Get context of the course to which ejsappbooking belongs to.
      $context = get_context_instance(CONTEXT_COURSE, $ejsappbooking->course);
      $users = get_enrolled_users($context);
      $ejsapps_usersaccess = $DB->get_records('ejsappbooking_usersaccess');
      foreach ($ejsapps_usersaccess as $ejsapp_usersaccess) {
        if (!$ejsapp_exists = $DB->get_record('ejsapp', array('id' => $ejsapp_usersaccess->ejsappid))) {
          $DB->delete_records('ejsappbooking_usersaccess', array('ejsappid' => $ejsapp_usersaccess->ejsappid));
        }
        $user_exists = false;
        foreach ($users as $user) {
          if ($user->id == $ejsapp_usersaccess->userid) {
            $user_exists = true;
            break;
          }
        }
        if ((!$user_exists) && ($ejsapp_usersaccess->userid != 2)) {
          $DB->delete_records('ejsappbooking_usersaccess', array('userid' => $ejsapp_usersaccess->userid));
        }
      }
    }          
    
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function ejsappbooking_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of ejsappbooking?
 *
 * This function returns if a scale is being used by one ejsappbooking
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $ejsappbookingid ID of an instance of this module
 * @return bool true if the scale is used by the given ejsappbooking instance
 */
function ejsappbooking_scale_used($ejsappbookingid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('ejsappbooking', array('id' => $ejsappbookingid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of ejsappbooking.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any ejsappbooking instance
 */
function ejsappbooking_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('ejsappbooking', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the give ejsappbooking instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $ejsappbooking instance object with extra cmidnumber and modname property
 * @return void
 */
function ejsappbooking_grade_item_update(stdClass $ejsappbooking) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $item = array();
    $item['itemname'] = clean_param($ejsappbooking->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $ejsappbooking->grade;
    $item['grademin']  = 0;

    grade_update('mod/ejsappbooking', $ejsappbooking->course, 'mod', 'ejsappbooking', $ejsappbooking->id, 0, null, $item);
}

/**
 * Update ejsappbooking grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $ejsappbooking instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function ejsappbooking_update_grades(stdClass $ejsappbooking, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $grades = array(); // populate array of grade objects indexed by userid

    grade_update('mod/ejsappbooking', $ejsappbooking->course, 'mod', 'ejsappbooking', $ejsappbooking->id, 0, $grades);
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function ejsappbooking_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for ejsappbooking file areas
 *
 * @package mod_ejsappbooking
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function ejsappbooking_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the ejsappbooking file areas
 *
 * @package mod_ejsappbooking
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the ejsappbooking's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function ejsappbooking_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding ejsappbooking nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the ejsappbooking module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function ejsappbooking_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the ejsappbooking settings
 *
 * This function is called when the context for the page is a ejsappbooking module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $ejsappbookingnode {@link navigation_node}
 */
function ejsappbooking_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $ejsappbookingnode=null) {
}