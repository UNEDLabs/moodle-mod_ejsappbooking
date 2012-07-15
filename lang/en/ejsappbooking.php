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
 * Spanish strings for ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'EJSApp booking system';
$string['modulenameplural'] = 'EJSApp Booking Systems';
$string['modulename_help'] = 'The EJSAppBooking resource module enables Moodle users to book slots of time for real remote experimentation using the applets created with Easy Java Simulations (EJS) and uploaded to the Moodle courses by means of the EJSApp activity module.

This resource adds a Java application that lists all the available remote labs for the user and let him select one to make a booking for any desired day and hour.

The booking system consist of two parts: the booking client and the booking server. While the booking client application is added with this module, the booking server needs to be running in the server that hosts the Moodle portal. You can find this application at your /mod/ejsappbooking/applets/BookingServer/ forlder.';
$string['view_error'] = 'Unable to open the booking system application.';
$string['ejsappbookingname'] = 'EJSApp booking system name';
$string['ejsappbookingname_help'] = 'Name to be displayed for the booking system in your Moodle course';
$string['ejsappbooking'] = 'EJSAppBooking';
$string['pluginadministration'] = 'EJSAppBooking administration';
$string['pluginname'] = 'EJSAppBooking';