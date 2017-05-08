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
// - Luis de la Torre: ldelatorre@dia.uned.es
// - Ruben Heradio: rheradio@issi.uned.es
// - Francisco José Calvillo: ji92camuf@gmail.com
//
// at the Computer Science and Automatic Control, Spanish Open University
// (UNED), Madrid, Spain.

/**
 * Steps file to perform the EJSAppBooking restore
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one EJSAppBooking activity
 *
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ejsappbooking_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure
     */
    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('ejsappbooking', '/activity/ejsappbooking');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process table process_ejsappbooking
     * @param stdClass $data
     */
    protected function process_ejsappbooking($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $ejsappbooking = $DB->get_record('ejsappbooking', array('course' => $data->course));
        if (empty($ejsappbooking)) {
            $newitemid = $DB->insert_record('ejsappbooking', $data);
        } else {
            $newitemid = $ejsappbooking->id;
        }
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Do nothing
     */
    protected function after_execute() {
    }

}