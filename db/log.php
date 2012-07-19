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
 * Definition of log events
 *
 * NOTE: this is an example how to insert log event during installation/update.
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module'=>'ejsappbooking', 'action'=>'add', 'mtable'=>'ejsappbooking', 'field'=>'name'),
    array('module'=>'ejsappbooking', 'action'=>'update', 'mtable'=>'ejsappbooking', 'field'=>'name'),
    array('module'=>'ejsappbooking', 'action'=>'view', 'mtable'=>'ejsappbooking', 'field'=>'name'),
    array('module'=>'ejsappbooking', 'action'=>'view all', 'mtable'=>'ejsappbooking', 'field'=>'name')
);