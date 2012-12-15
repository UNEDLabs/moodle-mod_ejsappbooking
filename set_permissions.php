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
 *
 * Prints a particular instance of ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

define('USER_SMALL_CLASS', 20);   // Below this is considered small
define('USER_LARGE_CLASS', 200);  // Above this is considered large
define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);
define('MODE_BRIEF', 0);
define('MODE_USERDETAILS', 1);

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$contextmodid = required_param('contextid', PARAM_INT); 
$cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);

$labid = optional_param('labid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);                     // which page to show
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
$mode = optional_param('mode', NULL, PARAM_INT);                  // use the MODE_ constants
$accesssince = optional_param('accesssince',0,PARAM_INT);                // filter by last access. -1 = never
$search = optional_param('search','',PARAM_RAW);                    // make sure it is processed with p() or s() when sending to output!
$roleid = optional_param('roleid', 0, PARAM_INT);                   // optional roleid, 0 means all enrolled users (or all on the frontpage)

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

require_login($course, true, $cm);
$contextmod = get_context_instance_by_id($contextmodid, MUST_EXIST);
$context = context_course::instance($courseid);

$title = get_string('selectUsers_pageTitle', 'ejsappbooking');
$PAGE->set_context($contextmod);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// <Check whether there is at least one remote laboratory in the course>                    
$rem_labs = $DB->get_records('ejsapp', array('course' => $courseid, 'is_rem_lab' => '1'));
$i = 1;

$multilang = new filter_multilang($context, array('filter_multilang_force_old'=>0));
foreach ($rem_labs as $rem_lab) {
  $lab_name[$rem_lab->id] = $multilang->filter($rem_lab->name);
  if ($i == 1 && $labid == 0) {
    $labid = $rem_lab->id;
  }
  $i++;
}
// </Check whether there is at least one remote laboratory in the course>

$PAGE->set_url('/mod/ejsappbooking/set_permissions.php', array('id' => $id, 'courseid' => $courseid, 'contextid' => $context->id));

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$isfrontpage = ($course->id == SITEID);

$frontpagectx = get_context_instance(CONTEXT_COURSE, SITEID);

if ($isfrontpage) {
  $PAGE->set_pagelayout('admin');
} else {
  $PAGE->set_pagelayout('incourse');
}

$rolenamesurl = new moodle_url("$CFG->wwwroot/mod/ejsappbooking/set_permissions.php?id=$id&courseid=$courseid&contextid=$contextmodid&sifirst=&silast=");

$allroles = get_all_roles();
$roles = get_profile_roles($context);
$allrolenames = array();
if ($isfrontpage) {
  $rolenames = array(0=>get_string('allsiteusers', 'role'));
} else {
  $rolenames = array(0=>get_string('allparticipants'));
}


foreach ($allroles as $role) {
  $allrolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on
  if (isset($roles[$role->id])) {
    $rolenames[$role->id] = $allrolenames[$role->id];
  }
}

// make sure other roles may not be selected by any means
if (empty($rolenames[$roleid])) {
  print_error('noparticipants');
}

// no roles to display yet?
// frontpage course is an exception, on the front page course we should display all users
if (empty($rolenames) && !$isfrontpage) {
  if (has_capability('moodle/role:assign', $context)) {
    redirect($CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$context->id);
  } else {
    print_error('noparticipants');
  }
}

$bulkoperations = true;

$countries = get_string_manager()->get_list_of_countries();

$strnever = get_string('never');

$datestring = new stdClass();
$datestring->year  = get_string('year');
$datestring->years = get_string('years');
$datestring->day   = get_string('day');
$datestring->days  = get_string('days');
$datestring->hour  = get_string('hour');
$datestring->hours = get_string('hours');
$datestring->min   = get_string('min');
$datestring->mins  = get_string('mins');
$datestring->sec   = get_string('sec');
$datestring->secs  = get_string('secs');

if ($mode !== NULL) {
  $mode = (int)$mode;
  $SESSION->userindexmode = $mode;
} else if (isset($SESSION->userindexmode)) {
  $mode = (int)$SESSION->userindexmode;
} else {
  $mode = MODE_BRIEF;
}

/// Check to see if groups are being used in this course
/// and if so, set $currentgroup to reflect the current group

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
$currentgroup = groups_get_course_group($course, true);

if (!$currentgroup) {      // To make some other functions work better later
  $currentgroup  = NULL;
}

$isseparategroups = ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context));

if ($course->id===SITEID) {
  $PAGE->navbar->ignore_active();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('users_selection','ejsappbooking'));

if ($i>1) { // If there is at least one remote lab
    echo '<div class="userlist">';
    
    if ($isseparategroups and (!$currentgroup) ) {
      // The user is not in the group so show message and exit
      echo $OUTPUT->heading(get_string("notingroup"));
      echo $OUTPUT->footer();
      exit;
    }
    
    
    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = new moodle_url('/mod/ejsappbooking/set_permissions.php', array(
          'id' => $id, 
          'courseid' => $courseid, 
          'contextid' => $contextmodid,
          'roleid' => $roleid,
          'perpage' => $perpage,
          'accesssince' => $accesssince,
          'search' => s($search)));
    
    /// setting up tags
    if ($course->id == SITEID) {
      $filtertype = 'site';
    } else if ($course->id && !$currentgroup) {
      $filtertype = 'course';
      $filterselect = $course->id;
    } else {
      $filtertype = 'group';
      $filterselect = $currentgroup;
    }
    
    
    /// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
      $hiddenfields = array();  // teachers and admins are allowed to see everything
    } else {
      $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }
    
    if (isset($hiddenfields['lastaccess'])) {
      // do not allow access since filtering
      $accesssince = 0;
    }
    
    /// Print settings and things in a table across the top
    $controlstable = new html_table();
    $controlstable->attributes['class'] = 'controls';
    $controlstable->cellspacing = 0;
    $controlstable->data[] = new html_table_row();
    
    /// Print my course menus
    if ($mycourses = enrol_get_my_courses()) {
      $courselist = array();
    	$popupurl = new moodle_url('/mod/ejsappbooking/set_permissions.php?id='.$id.'&courseid='.$courseid.'&contextid='.$contextmodid.'&roleid='.$roleid.'&sifirst=&silast=');
      foreach ($mycourses as $mycourse) {
        $courselist[$mycourse->id] = format_string($mycourse->shortname);
      }
      if (has_capability('moodle/site:viewparticipants', $systemcontext)) {
    	  unset($courselist[SITEID]);
        $courselist = array(SITEID => format_string($SITE->shortname)) + $courselist;
      }
      $select = new single_select($popupurl, 'id', $courselist, $course->id, array(''=>'choosedots'), 'courseform');
      $select->set_label(get_string('mycourses'));
      $controlstable->data[0]->cells[] = $OUTPUT->render($select);
    } /// Print my course menus
    
    $controlstable->data[0]->cells[] = groups_print_course_menu($course, $baseurl->out(), true);
    
    if (!isset($hiddenfields['lastaccess'])) {
      // get minimum lastaccess for this course and display a dropbox to filter by lastaccess going back this far.
      // we need to make it diferently for normal courses and site course
      if (!$isfrontpage) {
        $minlastaccess = $DB->get_field_sql('SELECT min(timeaccess)
                                             FROM {user_lastaccess}
                                             WHERE courseid = ?
                                             AND timeaccess != 0', array($course->id));
        $lastaccess0exists = $DB->record_exists('user_lastaccess', array('courseid'=>$course->id, 'timeaccess'=>0));
      } else {
        $minlastaccess = $DB->get_field_sql('SELECT min(lastaccess)
                                             FROM {user}
                                             WHERE lastaccess != 0');
        $lastaccess0exists = $DB->record_exists('user', array('lastaccess'=>0));
      }
    
      $now = usergetmidnight(time());
      $timeaccess = array();
      $baseurl->remove_params('accesssince');
    
      // makes sense for this to go first.
      $timeoptions[0] = get_string('selectperiod');
    
      // days
      for ($i = 1; $i < 7; $i++) {
        if (strtotime('-'.$i.' days',$now) >= $minlastaccess) {
          $timeoptions[strtotime('-'.$i.' days',$now)] = get_string('numdays','moodle',$i);
        }
      }
      // weeks
      for ($i = 1; $i < 10; $i++) {
        if (strtotime('-'.$i.' weeks',$now) >= $minlastaccess) {
          $timeoptions[strtotime('-'.$i.' weeks',$now)] = get_string('numweeks','moodle',$i);
        }
      }
      // months
      for ($i = 2; $i < 12; $i++) {
        if (strtotime('-'.$i.' months',$now) >= $minlastaccess) {
          $timeoptions[strtotime('-'.$i.' months',$now)] = get_string('nummonths','moodle',$i);
        }
      }
      // try a year
      if (strtotime('-1 year',$now) >= $minlastaccess) {
        $timeoptions[strtotime('-1 year',$now)] = get_string('lastyear');
      }
    
      if (!empty($lastaccess0exists)) {
        $timeoptions[-1] = get_string('never');
      }
    
      if (count($timeoptions) > 1) {
        $select = new single_select($baseurl, 'accesssince', $timeoptions, $accesssince, null, 'timeoptions');
        $select->set_label(get_string('usersnoaccesssince'));
        $controlstable->data[0]->cells[] = $OUTPUT->render($select);
      }
    } // if (!isset($hiddenfields['lastaccess']))
    
    // <Select rem lab pulldown menu>
    $select = new single_select($baseurl, 'labid', $lab_name, $labid, null, 'formatmenu');
    $select->set_label(get_string('rem_lab_selection', 'ejsappbooking'));
    $remlabslistcell = new html_table_cell();
    $remlabslistcell->attributes['class'] = 'right';
    $remlabslistcell->text = $OUTPUT->render($select);
    $controlstable->data[0]->cells[] = $remlabslistcell;
    // </Select rem lab pulldown menu>
    
    $formatmenu = array( '0' => get_string('brief'),
                         '1' => get_string('userdetails'));
    $select = new single_select($baseurl, 'mode', $formatmenu, $mode, null, 'formatmenu');
    $select->set_label(get_string('userlist'));
    $userlistcell = new html_table_cell();
    $userlistcell->attributes['class'] = 'right';
    $userlistcell->text = $OUTPUT->render($select);
    $controlstable->data[0]->cells[] = $userlistcell;
    
    echo html_writer::table($controlstable);
    
    if ($currentgroup and (!$isseparategroups or has_capability('moodle/site:accessallgroups', $context))) {    /// Display info about the group
    	if ($group = groups_get_group($currentgroup)) {
        if (!empty($group->description) or (!empty($group->picture) and empty($group->hidepicture))) {
          $groupinfotable = new html_table();
          $groupinfotable->attributes['class'] = 'groupinfobox';
          $picturecell = new html_table_cell();
          $picturecell->attributes['class'] = 'left side picture';
          $picturecell->text = print_group_picture($group, $course->id, true, true, false);
    
          $contentcell = new html_table_cell();
          $contentcell->attributes['class'] = 'content';
    
          $contentheading = $group->name;
          if (has_capability('moodle/course:managegroups', $context)) {
            $aurl = new moodle_url('/group/group.php', array('id' => $group->id, 'courseid' => $group->courseid));
            $contentheading .= '&nbsp;' . $OUTPUT->action_icon($aurl, new pix_icon('t/edit', get_string('editgroupprofile')));
          }
    
          $group->description = file_rewrite_pluginfile_urls($group->description, 'pluginfile.php', $context->id, 'group', 'description', $group->id);
          if (!isset($group->descriptionformat)) {
            $group->descriptionformat = FORMAT_MOODLE;
          }
          $options = array('overflowdiv'=>true);
          $contentcell->text = $OUTPUT->heading($contentheading, 3) . format_text($group->description, $group->descriptionformat, $options);
          $groupinfotable->data[] = new html_table_row(array($picturecell, $contentcell));
          echo html_writer::table($groupinfotable);
        }
      }
    }
    
    /// Define a table showing a list of users in the current role selection
    $tablecolumns = array('userpic', 'fullname');
    $tableheaders = array(get_string('userpic'), get_string('fullnameuser'));
    if ($mode === MODE_BRIEF && !isset($hiddenfields['city'])) {
      $tablecolumns[] = 'city';
      $tableheaders[] = get_string('city');
    }
    if ($mode === MODE_BRIEF && !isset($hiddenfields['country'])) {
      $tablecolumns[] = 'country';
      $tableheaders[] = get_string('country');
    }
    if (!isset($hiddenfields['lastaccess'])) {
      $tablecolumns[] = 'lastaccess';
      $tableheaders[] = get_string('lastaccess');
    }
    
    if ($bulkoperations) {
      $tablecolumns[] = 'select';
      $tableheaders[] = get_string('booking_rights','ejsappbooking');
    }
    
    $table = new flexible_table('user-index-participants-'.$course->id);
    
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());
    
    if (!isset($hiddenfields['lastaccess'])) {
      $table->sortable(true, 'lastaccess', SORT_DESC);
    }
    
    $table->no_sorting('roles');
    $table->no_sorting('groups');
    $table->no_sorting('groupings');
    $table->no_sorting('select');
    
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'participants');
    $table->set_attribute('class', 'generaltable generalbox');
    
    $table->set_control_variables(array(
                TABLE_VAR_SORT    => 'ssort',
                TABLE_VAR_HIDE    => 'shide',
                TABLE_VAR_SHOW    => 'sshow',
                TABLE_VAR_IFIRST  => 'sifirst',
                TABLE_VAR_ILAST   => 'silast',
                TABLE_VAR_PAGE    => 'spage'
                ));
    $table->setup();
    
    // we are looking for all users with this role assigned in this context or higher
    $contextlist = get_related_contexts_string($context);
    
    list($esql, $params) = get_enrolled_sql($context, NULL, $currentgroup, true);
    
    $joins = array("FROM {user} u");
    $wheres = array();
    
    if ($isfrontpage) {
      $select = "SELECT u.id, u.username, u.firstname, u.lastname,
                        u.email, u.city, u.country, u.picture,
                        u.lang, u.timezone, u.maildisplay, u.imagealt,
                        u.lastaccess";
      $joins[] = "JOIN ($esql) e ON e.id = u.id"; // everybody on the frontpage usually
      if ($accesssince) {
        $wheres[] = get_user_lastaccess_sql($accesssince);
      }
    } else {
      $select = "SELECT u.id, u.username, u.firstname, u.lastname,
                        u.email, u.city, u.country, u.picture,
                        u.lang, u.timezone, u.maildisplay, u.imagealt,
                        COALESCE(ul.timeaccess, 0) AS lastaccess";
      $joins[] = "JOIN ($esql) e ON e.id = u.id"; // course enrolled users only
      $joins[] = "LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = :courseid)"; // not everybody accessed course yet
      $params['courseid'] = $course->id;
      if ($accesssince) {
        $wheres[] = get_course_lastaccess_sql($accesssince);
      }
    }
    
    // performance hacks - we preload user contexts together with accounts
    list($ccselect, $ccjoin) = context_instance_preload_sql('u.id', CONTEXT_USER, 'ctx');
    $select .= $ccselect;
    $joins[] = $ccjoin;
    
    // limit list to users with some role only
    if ($roleid) {
      $wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $contextlist)";
      $params['roleid'] = $roleid;
    }
    
    $from = implode("\n", $joins);
    if ($wheres) {
      $where = "WHERE " . implode(" AND ", $wheres);
    } else {
      $where = "";
    }
    
    $totalcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);
    
    if (!empty($search)) {
      $fullname = $DB->sql_fullname('u.firstname','u.lastname');
      $wheres[] = "(". $DB->sql_like($fullname, ':search1', false, false) .
                  " OR ". $DB->sql_like('email', ':search2', false, false) .
                  " OR ". $DB->sql_like('idnumber', ':search3', false, false) .") ";
      $params['search1'] = "%$search%";
      $params['search2'] = "%$search%";
      $params['search3'] = "%$search%";
    }
    
    list($twhere, $tparams) = $table->get_sql_where();
    if ($twhere) {
      $wheres[] = $twhere;
      $params = array_merge($params, $tparams);
    }
    
    $from = implode("\n", $joins);
    if ($wheres) {
      $where = "WHERE " . implode(" AND ", $wheres);
    } else {
      $where = "";
    }
    
    if ($table->get_sql_sort()) {
      $sort = ' ORDER BY '.$table->get_sql_sort();
    } else {
      $sort = '';
    }
    
    $matchcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);
    
    $table->initialbars(true);
    $table->pagesize($perpage, $matchcount);
    
    // list of users at the current visible page - paging makes it relatively short
    $userlist = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());
    
    /// If there are multiple Roles in the course, then show a drop down menu for switching
    if (count($rolenames) > 1) {
      echo '<div class="rolesform">
      <label for="rolesform_jump">'.get_string('currentrole', 'role').'&nbsp;</label>';
      echo $OUTPUT->single_select($rolenamesurl, 'roleid', $rolenames, $roleid, null, 'rolesform');
      echo '</div>';
    
    } else if (count($rolenames) == 1) {
      // when all users with the same role - print its name
      echo '<div class="rolesform">';
      echo get_string('role').get_string('labelsep', 'langconfig');
      $rolename = reset($rolenames);
      echo $rolename . '</div>';
    }
    
    if ($roleid > 0) {
      $a->number = $totalcount;
      $a->role = $rolenames[$roleid];
      $heading = format_string(get_string('xuserswiththerole', 'role', $a));
    
      if ($currentgroup and $group) {
        $a->group = $group->name;
        $heading .= ' ' . format_string(get_string('ingroup', 'role', $a));
      }
    
      if ($accesssince) {
        $a->timeperiod = $timeoptions[$accesssince];
        $heading .= ' ' . format_string(get_string('inactiveformorethan', 'role', $a));
      }
    
      $heading .= ": $a->number";
      if (user_can_assign($context, $roleid)) {
        $heading .= ' <a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?roleid='.$roleid.'&amp;contextid='.$context->id.'">';
        $heading .= '<img src="'.$OUTPUT->pix_url('i/edit') . '" class="icon" alt="" /></a>';
      }
      echo $OUTPUT->heading($heading, 3);
    } else {
      if ($course->id != SITEID && has_capability('moodle/course:enrolreview', $context)) {
        $editlink = $OUTPUT->action_icon(new moodle_url('/enrol/users.php', array('id' => $course->id)), new pix_icon('i/edit', get_string('edit')));
      } else {
        $editlink = '';
      }
      if ($course->id == SITEID and $roleid < 0) {
        $strallparticipants = get_string('allsiteusers', 'role');
      } else {
        $strallparticipants = get_string('allparticipants');
      }
      if ($matchcount < $totalcount) {
        echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount.'/'.$totalcount . $editlink, 3);
      } else {
        echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount . $editlink, 3);
      }
    }
    
    
    if ($bulkoperations) {
      $ejsappbooking = $DB->get_record('ejsappbooking', array('course' => $courseid));
      echo "<form action=\"send_messages.php?courseid=$courseid&id=$id&labid=$labid&bookingid=$ejsappbooking->id\" method=\"post\" id=\"participantsform\">" . '<div>';
      echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />
      <input type="hidden" name="returnto" value="'.s(me()).'" />';
    }
    
    if ($mode === MODE_USERDETAILS) {    // Print simple listing
      if ($totalcount < 1) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
      } else {
        if ($totalcount > $perpage) {
          $firstinitial = $table->get_initial_first();
          $lastinitial  = $table->get_initial_last();
          $strall = get_string('all');
          $alpha  = explode(',', get_string('alphabet', 'langconfig'));
    
          // Bar of first initials
         
          echo '<div class="initialbar firstinitial">'.get_string('firstname').' : ';
          if(!empty($firstinitial)) {
            echo '<a href="'.$baseurl->out().'&amp;sifirst=">'.$strall.'</a>';
          } else {
            echo '<strong>'.$strall.'</strong>';
          }
          foreach ($alpha as $letter) {
            if ($letter == $firstinitial) {
              echo ' <strong>'.$letter.'</strong>';
            } else {
              echo ' <a href="'.$baseurl->out().'&amp;sifirst='.$letter.'">'.$letter.'</a>';
            }
          }
          echo '</div>';
    
          // Bar of last initials
    
          echo '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
          if(!empty($lastinitial)) {
            echo '<a href="'.$baseurl->out().'&amp;silast=">'.$strall.'</a>';
          } else {
            echo '<strong>'.$strall.'</strong>';
          }
          foreach ($alpha as $letter) {
            if ($letter == $lastinitial) {
              echo ' <strong>'.$letter.'</strong>';
            } else {
              echo ' <a href="'.$baseurl->out().'&amp;silast='.$letter.'">'.$letter.'</a>';
            }
          }
          echo '</div>';
          
          $pagingbar = new paging_bar($matchcount, intval($table->get_page_start() / $perpage), $perpage, $baseurl);
          $pagingbar->pagevar = 'spage';
          echo $OUTPUT->render($pagingbar);
      } // if ($totalcount > $perpage)
    
      if ($matchcount > 0) {
        $usersprinted = array();
        foreach ($userlist as $user) {
          if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
            continue;
                    }
            $usersprinted[] = $user->id; /// Add new user to the array of users printed
    
            context_instance_preload($user);
    
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            $usercontext = get_context_instance(CONTEXT_USER, $user->id);
    
            $countries = get_string_manager()->get_list_of_countries();
    
            /// Get the hidden field list
            if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
              $hiddenfields = array();
            } else {
              $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
            }
              $table = new html_table();
              $table->attributes['class'] = 'userinfobox';
            
              $row = new html_table_row();
              $row->cells[0] = new html_table_cell();
              $row->cells[0]->attributes['class'] = 'left side';
    
              $row->cells[0]->text = $OUTPUT->user_picture($user, array('size' => 100, 'courseid'=>$course->id));
              $row->cells[1] = new html_table_cell();
              $row->cells[1]->attributes['class'] = 'content';
    
              $row->cells[1]->text = $OUTPUT->container(fullname($user, has_capability('moodle/site:viewfullnames', $context)), 'username');
              $row->cells[1]->text .= $OUTPUT->container_start('info');
    
              if (!empty($user->role)) {
                $row->cells[1]->text .= get_string('role').get_string('labelsep', 'langconfig').$user->role.'<br />';
              }
              if ($user->maildisplay == 1 or ($user->maildisplay == 2 and ($course->id != SITEID) and !isguestuser()) or has_capability('moodle/course:viewhiddenuserfields', $context)) {
                $row->cells[1]->text .= get_string('email').get_string('labelsep', 'langconfig').html_writer::link("mailto:$user->email", $user->email) . '<br />';
              }
              if (($user->city or $user->country) and (!isset($hiddenfields['city']) or !isset($hiddenfields['country']))) {
                $row->cells[1]->text .= get_string('city').get_string('labelsep', 'langconfig');
                if ($user->city && !isset($hiddenfields['city'])) {
                  $row->cells[1]->text .= $user->city;
                }
                if (!empty($countries[$user->country]) && !isset($hiddenfields['country'])) {
                  if ($user->city && !isset($hiddenfields['city'])) {
                    $row->cells[1]->text .= ', ';
                  }
                  $row->cells[1]->text .= $countries[$user->country];
                }
                $row->cells[1]->text .= '<br />';
              }
    
              if (!isset($hiddenfields['lastaccess'])) {
                if ($user->lastaccess) {
                  $row->cells[1]->text .= get_string('lastaccess').get_string('labelsep', 'langconfig').userdate($user->lastaccess);
                  $row->cells[1]->text .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
                } else {
                  $row->cells[1]->text .= get_string('lastaccess').get_string('labelsep', 'langconfig').get_string('never');
                }
              }
    
              $row->cells[1]->text .= $OUTPUT->container_end();
    
              $row->cells[2] = new html_table_cell();
              $row->cells[2]->attributes['class'] = 'links';
              $row->cells[2]->text = '';
    
              $links = array();
    
              if ($CFG->bloglevel > 0) {
                $links[] = html_writer::link(new moodle_url('/blog/collaborative_index.php?userid='.$user->id), get_string('blogs','blog'));
              }
    
              if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context))) {
                $links[] = html_writer::link(new moodle_url('/notes/collaborative_index.php?course=' . $course->id. '&user='.$user->id), get_string('notes','notes'));
              }
    
              if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
                $links[] = html_writer::link(new moodle_url('/course/user.php?id='. $course->id .'&user='. $user->id), get_string('activity'));
              }
    
              if ($USER->id != $user->id && !session_is_loggedinas() && has_capability('moodle/user:loginas', $context) && !is_siteadmin($user->id)) {
                $links[] = html_writer::link(new moodle_url('/course/loginas.php?id='. $course->id .'&user='. $user->id .'&sesskey='. sesskey()), get_string('loginas'));
              }
    
              $links[] = html_writer::link(new moodle_url('/user/view.php?id='. $user->id .'&course='. $course->id), get_string('fullprofile') . '...');
    
              $row->cells[2]->text .= implode('', $links);
    
              if (!empty($messageselect)) {
                $check_user_permission = array('ejsappid'=>$labid,'userid'=>$user->id);
      	        $user_permission = $DB->get_field('ejsappbooking_usersaccess','allowremaccess',$check_user_permission);
                if ($user_permission==1) { 
                  $row->cells[2]->text .= '<br /><input type="checkbox" checked="yes" name="user'.$user->id.'" /> ';
                } else {
                  $row->cells[2]->text .= '<br /><input type="checkbox" name="user'.$user->id.'" /> ';
                }
              }
              $table->data = array($row);
              echo html_writer::table($table);
            } // foreach ($userlist as $user) 
    
          } else {
            echo $OUTPUT->heading(get_string('nothingtodisplay'));
          }
      } // if ($totalcount < 1) ... else
    
    } else {
      $countrysort = (strpos($sort, 'country') !== false);
      $timeformat = get_string('strftimedate');
    
      if ($userlist) {
        $usersprinted = array();
        foreach ($userlist as $user) {
          if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
          continue;
          }
          $usersprinted[] = $user->id; /// Add new user to the array of users printed
    
          context_instance_preload($user);
        
          if ($user->lastaccess) {
            $lastaccess = format_time(time() - $user->lastaccess, $datestring);
          } else {
            $lastaccess = $strnever;
          }
    
          if (empty($user->country)) {
            $country = '';    
          } else {
            if($countrysort) {
              $country = '('.$user->country.') '.$countries[$user->country];
            } else {
              $country = $countries[$user->country];
            }
          }
    
          $usercontext = get_context_instance(CONTEXT_USER, $user->id);
    
          if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) {
            $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></strong>';
          } else {
            $profilelink = '<strong>'.fullname($user).'</strong>';
          }
    
          $data = array ($OUTPUT->user_picture($user, array('size' => 35, 'courseid'=>$course->id)), $profilelink);
    
          if ($mode === MODE_BRIEF && !isset($hiddenfields['city'])) {
            $data[] = $user->city;
          }
          if ($mode === MODE_BRIEF && !isset($hiddenfields['country'])) {
            $data[] = $country;
          }
          if (!isset($hiddenfields['lastaccess'])) {
            $data[] = $lastaccess;
          }
    
          if (isset($userlist_extra) && isset($userlist_extra[$user->id])) {
            $ras = $userlist_extra[$user->id]['ra'];
            $rastring = '';
            foreach ($ras AS $key=>$ra) {
              $rolename = $allrolenames[$ra['roleid']] ;
              if ($ra['ctxlevel'] == CONTEXT_COURSECAT) {
                $rastring .= $rolename. ' @ ' . '<a href="'.$CFG->wwwroot.'/course/category.php?id='.$ra['ctxinstanceid'].'">'.s($ra['ccname']).'</a>';
              } elseif ($ra['ctxlevel'] == CONTEXT_SYSTEM) {
                $rastring .= $rolename. ' - ' . get_string('globalrole','role');
              } else {
                $rastring .= $rolename;
              }
            }
            $data[] = $rastring;
            if ($groupmode != 0) {
              // htmlescape with s() and implode the array
              $data[] = implode(', ', array_map('s',$userlist_extra[$user->id]['group']));
              $data[] = implode(', ', array_map('s', $userlist_extra[$user->id]['gping']));
            }
          }
    
          if ($bulkoperations) {
            $check_user_permission = array('ejsappid'=>$labid,'userid'=>$user->id);
      	    $user_permission = $DB->get_field('ejsappbooking_usersaccess','allowremaccess',$check_user_permission);
            if ($user_permission==1) { 
              $data[] = '<input type="checkbox" checked="yes" class="usercheckbox" name="user'.$user->id.'" />';
            } else {
              $data[] = '<input type="checkbox" class="usercheckbox" name="user'.$user->id.'" />';
            }
          }
          $table->add_data($data);
    
        } // foreach ($userlist as $user)
      } // if ($userlist)
    
      $table->print_html();
    
    } // if ($mode === MODE_USERDETAILS) ... else
    
    if ($bulkoperations) {
      echo '<br /><div class="buttons">
      <input type="button" id="checkall" value="' . get_string('selectall') . '" /> 
      <input type="button" id="checknone" value="'. get_string('deselectall'). '" />
      <input type="submit" id="invite_participants" value="' . get_string('save_changes', 'ejsappbooking') . '" /> </div></div> </form>';
      
      $module = array('name'=>'core_user', 'fullpath'=>'/user/module.js');
      $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
    }
    
    if (has_capability('moodle/site:viewparticipants', $context) && $totalcount > ($perpage*3)) {
      echo '<form action="collaborative_index.php" class="searchform"><div><input type="hidden" name="id" value="'.$course->id.'" />'.get_string('search').':&nbsp;'."\n";
      echo '<input type="text" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'.get_string('search').'" /></div></form>'."\n";
    }
    
    $perpageurl = clone($baseurl);
    $perpageurl->remove_params('perpage');
    if ($perpage == SHOW_ALL_PAGE_SIZE) {
      $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
      echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showperpage', '', DEFAULT_PAGE_SIZE)), array(), 'showall');
    } else if ($matchcount > 0 && $perpage < $matchcount) {
      $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
      echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showall', '', $matchcount)), array(), 'showall');
    }
    
    echo '</div>';  // userlist
    
    echo $OUTPUT->footer();
    
    if ($userlist) {
      $userlist->close();
    }
    
    /**
     * returns the course last access
     *
     * @param int $accesssince
     */
    function get_course_lastaccess_sql($accesssince='') {
      if (empty($accesssince)) {
        return '';
      }
      if ($accesssince == -1) { // never
        return 'ul.timeaccess = 0';
      } else {
        return 'ul.timeaccess != 0 AND ul.timeaccess < '.$accesssince;
      }
    }
    
    /**
     * returns the user last access
     *
     * @param int $accesssince
     */
    function get_user_lastaccess_sql($accesssince='') {
      if (empty($accesssince)) {
        return '';
      }
      if ($accesssince == -1) { // never
        return 'u.lastaccess = 0';
      } else {
        return 'u.lastaccess != 0 AND u.lastaccess < '.$accesssince;
      }
    }
} else { // If there are no remote labs
  echo  get_string('no_rem_labs', 'ejsappbooking');
}