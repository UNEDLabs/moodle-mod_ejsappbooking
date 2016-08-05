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
//  - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain

/**
 * Defines the version of ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2016080400;      // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2013111800;      // Requires this Moodle version
$plugin->cron      = 86400;           // Period for cron to check this module (secs)
$plugin->component = 'mod_ejsappbooking'; // To check on upgrade, that module sits in correct place
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '2.3 (Build: 2015113000)';
$plugin->dependencies = array('mod_ejsapp' => 2016080400, 'block_remlab_manager' => 2016080400);