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
 * EJSAppBooking remlab_access table maintenance task.
 *
 * @package    mod_ejsappbooking
 * @copyright  2018 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ejsappbooking\task;

/**
 * Task for maintaining clean and updated the ejsapp_booking_remlab_access table.
 *
 * @package    mod_ejsappbooking
 * @copyright  2018 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_remlab_table extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     * @throws
     */
    public function get_name() {
        // Shown in admin screens
        return get_string('update_remlab_table', 'mod_ejsappbooking');
    }

    /**
     * Performs the update of the table.
     *
     * @return bool|void
     * @throws
     */
    public function execute() {
        global $DB;

        // UPDATING VALID VALUES AND DELETING BOOKINGS OLDER THAN A WEEK.
        $remlabaccesses = $DB->get_records('ejsappbooking_remlab_access');
        $currenttime = date('Y-m-d H:00:00');
        foreach ($remlabaccesses as $remlabaccess) {
            if ($remlabaccess->endtime < $currenttime) {
                $oneweekold = date('Y-m-d H:00:00', strtotime("-7 days"));
                if ($remlabaccess->endtime < $oneweekold) {
                    $DB->delete_records('ejsappbooking_remlab_access', array('id' => $remlabaccess->id));
                } else {
                    $remlabaccess->valid = 0;
                    $DB->update_record('ejsappbooking_remlab_access', $remlabaccess);
                }
            }
        }
    }
}