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
 * Class for logging the view event of an EJSApp Booking System
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ejsappbooking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_feedback course module viewed event class.
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ejsappbooking_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ejsappbooking';
    }

    /**
     * Get event's name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_working', 'ejsapp');
    }

    /**
     * Get event description
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the EJSApp Bookins System resource with id '{$this->objectid}'.";
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/ejsappbooking/view.php', array('n' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'ejsappbooking', 'view', 'view.php?n=' . $this->objectid,
            $this->objectid, $this->contextinstanceid);
    }

}