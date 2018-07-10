<?php
// This file is part of the Moodle block "Remlab Manager"
//
// Remlab Manager is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Remlab Manager is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// Remlab Manager has been developed by:
// - Luis de la Torre: ldelatorre@dia.uned.es
//
// at the Computer Science and Automatic Control, Spanish Open University
// (UNED), Madrid, Spain.

/**
 * EJSAppBooking usersaccess table maintenance task.
 *
 * @package    mod_ejsappbooking
 * @copyright  2018 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ejsappbooking\task;

/**
 * Task for maintaining clean and updated the ejsapp_booking_usersaccess table.
 *
 * @package    mod_ejsappbooking
 * @copyright  2018 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_users_table extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     * @throws
     */
    public function get_name() {
        // Shown in admin screens
        return get_string('update_users_table', 'block_remlab_manager');
    }

    /**
     * Performs the update of the table.
     *
     * @return bool|void
     * @throws
     */
    public function execute() {
        global $DB;

        $usersaccess = new stdClass();

        // ADDING NEW USERS AND/OR REMOTE EJSAPP LABS.
        $usersaccess->timecreated = time();
        $usersaccess->timemodified = time();

        // Get all ejsappbooking instances' ids from the ejsappbooking data table.
        $ejsappbookings = $DB->get_records('ejsappbooking');

        foreach ($ejsappbookings as $ejsappbooking) {
            $usersaccess->bookingid = $ejsappbooking->id;
            // Get context of the course to which ejsappbooking belongs to.
            $context = context_course::instance($ejsappbooking->course);
            $users = get_enrolled_users($context);
            $ejsapps = $DB->get_records('ejsapp', array('course' => $ejsappbooking->course));
            foreach ($ejsapps as $ejsapp) {
                if ($ejsapp->is_rem_lab == 1) {
                    $usersaccess->ejsappid = $ejsapp->id;
                    foreach ($users as $user) {
                        $usersaccess->userid = $user->id;
                        if (!has_capability('mod/ejsapp:accessremotelabs', $context, $user->id, true)) {
                            $usersaccess->allowremaccess = 0;
                        } else {
                            $usersaccess->allowremaccess = 1;
                        }
                        if (!$ejsappexists = $DB->get_record('ejsappbooking_usersaccess',
                            array('ejsappid' => $ejsapp->id, 'userid' => $user->id))) {
                            $DB->insert_record('ejsappbooking_usersaccess', $usersaccess);
                        } else if ($usersaccess->allowremaccess == 1) {
                            $usersaccess->id = $ejsappexists->id;
                            $DB->update_record('ejsappbooking_usersaccess', $usersaccess);
                        }
                    }
                    // Check whether the admin user already has booking rights and, if not, grant them to him.
                    if (!$DB->record_exists('ejsappbooking_usersaccess', array('ejsappid' => $ejsapp->id, 'userid' => '2'))) {
                        $usersaccess->userid = 2;
                        $usersaccess->allowremaccess = 1;
                        $DB->insert_record('ejsappbooking_usersaccess', $usersaccess);
                    }
                }
            }
        }

        // DELETING OLD USERS AND/OR REMOTE EJSAPP LABS.
        foreach ($ejsappbookings as $ejsappbooking) {
            // Get context of the course to which ejsappbooking belongs to.
            $context = context_course::instance($ejsappbooking->course);
            $users = get_enrolled_users($context);
            $usersaccess = $DB->get_records('ejsappbooking_usersaccess');
            foreach ($usersaccess as $useraccess) {
                $ejsapp = $DB->get_record('ejsapp', array('id' => $useraccess->ejsappid));
                if ((!$ejsapp) || ($ejsapp->is_rem_lab == 0)) {
                    $DB->delete_records('ejsappbooking_usersaccess', array('ejsappid' => $useraccess->ejsappid));
                }
                $userexists = false;
                foreach ($users as $user) {
                    if ($user->id == $useraccess->userid) {
                        $userexists = true;
                        break;
                    }
                }
                if (($userexists == false) && ($useraccess->userid != 2)) {
                    $DB->delete_records('ejsappbooking_usersaccess',
                        array('bookingid' => $ejsappbooking->id, 'userid' => $useraccess->userid));
                }
            }
        }
    }
}