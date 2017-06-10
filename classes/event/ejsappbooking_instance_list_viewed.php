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
 * Class for logging the view all event of an EJSApp Booking System
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ejsappbooking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_ejsappbooking course module viewed event class.
 *
 * @package    mod_ejsappbooking
 * @since      Moodle 2.7
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ejsappbooking_instance_list_viewed extends \core\event\course_module_instance_list_viewed {
    // No need for any code here as everything is handled by the parent class.
}
