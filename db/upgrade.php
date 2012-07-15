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
 * This file keeps track of upgrades to the ejsappbooking module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute ejsappbooking upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_ejsappbooking_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // if ($oldversion < YYYYMMDD00) { //New version in version.php
    //
    // }

    return true;
}