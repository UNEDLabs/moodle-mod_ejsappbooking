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
 * Spanish strings for ejsappbooking
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'EJSApp booking system';
$string['modulenameplural'] = 'EJSApp Booking Systems';
$string['modulename_help'] = 'The EJSAppBooking resource module enables Moodle users to book slots of time for real remote experimentation using the applets created with Easy Java Simulations (EJS) and uploaded to the Moodle courses by means of the EJSApp activity module.

This resource adds a Java application that lists all the available remote labs for the user and let him select one to make a booking for any desired day and hour.

The booking system consist of two parts: the booking client and the booking server. While the booking client application is added with this module, the booking server needs to be running in the server that hosts the Moodle portal. You can find this application at your /mod/ejsappbooking/applets/BookingServer/ folder.';
$string['view_error'] = 'Unable to open the booking system application.';
$string['ejsappbookingname'] = 'EJSApp booking system name';
$string['ejsappbookingname_help'] = 'Name to be displayed for the booking system in your Moodle course';
$string['ejsappbooking'] = 'EJSAppBooking';
$string['pluginadministration'] = 'EJSAppBooking administration';
$string['pluginname'] = 'EJSAppBooking';

$string['manage_access_but'] = 'Manage users access';

// Strings in select_rem_lab.php.
$string['selectRemLab_pageTitle'] = 'Remote lab selection';
$string['rem_lab_selection'] = 'Select a remote lab';
$string['select_users_but'] = 'Set users rights for this lab';
$string['no_rem_labs'] = 'There are no remote labs in this course';

// Strings in select_users.php.
$string['bookingRights_pageTitle'] = 'Booking rights';
$string['users_selection'] = 'Select users to give them booking rights over the selected remote lab';
$string['accept_users_but'] = 'Accept';
$string['save_changes'] = 'Save changes';
$string['booking_rights'] = 'Booking rights';

// Strings in send_messages.php.
$string['allow_remlabaccess'] = 'You were granted permission to make bookings for a new remote lab: ';
$string['sending_message'] = 'Sending info messages';


$string['update_remlab_table'] = 'Update ejsapp booking remlab table';
$string['update_users_table'] = 'Update ejsapp booking users table';

$string['already_enabled'] = 'You already have a booking system in this course.';

$string['newreservation'] = 'New reservation';
$string['deleteBooking'] = 'Booking info deleted';
$string['mybookings'] = 'My bookings';
$string['mybookings_empty'] = 'There is no active bookings at the moment';
$string['plant'] = 'Plant';
$string['availability'] = 'Availability';
$string['bookinginfo'] = 'Booking info';
$string['totalslots'] = 'Max slots allowed in this laboratory';
$string['weeklyslots'] = 'Max weekly slots allowed in this laboratory';
$string['dailyslots'] = 'Max slots per day';
$string['bookingexits'] = 'The booking already exists';
$string['selectdate'] = 'You must select an available date and time on the calendar';
$string['delete'] = 'Delete';
$string['selectbooking'] = 'To cancel a reservation, select it and then press';
$string['nobooking'] = 'No bookings';
$string['date'] = 'Date';
$string['hour'] = 'Time';
$string['action'] = 'Action';
$string['send_message'] = 'Submitted warning message about the prior action';
$string['book'] = 'Book';
$string['book_message'] = 'Booking';
$string['messageDelete'] = 'Are you sure you want to delete this booking?';
$string['cancel'] = 'Cancel';
$string['active_plant'] = 'Plant available';
$string['inactive_plant'] = 'This plant not available';
$string['iyear'] = 'Year';
$string['imonth'] = 'Month';
$string['availability_booking'] = 'Number of available reserves';
$string['rem_prac_selection'] = 'Select a practice';
$string['day_selection'] = 'Select a day';
$string['time_selection'] = 'Select starting time on';
$string['time_zone'] = 'Your current timezone is';
$string['time_zone_help'] = 'You can change this settings in your user profile';
$string['messageOccupied'] = 'The slot is occupied';
$string['no_labs_rem'] = 'You are not authorized to book or there is no remote laboratory set';
$string['slot-free'] = 'This slot is available';
$string['slot-past'] = 'This slot has expired';
$string['slot-busy'] = 'This slot is occupied';
$string['plant-inactive'] = 'This plant is currently unavailable';
$string['submit-error'] = 'Please, check error messages before completing your booking.';
$string['submit-success'] = 'Your booking has been saved successfully';
$string['submit-error-exists'] = 'This record already exists';
$string['delete-confirmation'] = 'Are you sure you want to delete this item?';

// Capabilities.
$string['ejsappbooking:addinstance'] = 'Add a new booking system';
$string['ejsappbooking:view'] = 'View the booking system';
$string['ejsappbooking:managerights'] = 'Manage users\' rights for making bookings';

//Privacy
$string['privacy:metadata'] = 'The EJSApp Booking activity only stores information about user\'s bookings for EJSApp remote lab activities.';