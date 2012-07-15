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
 * English strings for ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Sistema de reservas EJSApp';
$string['modulenameplural'] = 'Sistemas de reservas EJSApp';
$string['modulename_help'] = 'El m�dulo de recurso EJSAppBooking permite a los usuarios de Moodle reservas franjas de tiempo para experimentaci�n real y remota usando los applets creados con Easy Java Simulations (EJS) y subidos a los cursos de Moodle mediante el m�dulo de actividad EJSApp.

Este recurso a�ade una aplicaci�n Java que muestra una lista de los laboratorios remotos disponibles para el usuario y le permite seleccionar una reserva para cualquier d�a y hora deseados.

El sistema de reservas consiste en dos partes: el cliente de reservas y el servidor de reservas. Mientras que la aplicaci�n del cliente de reservas se a�ade con este m�dulo, el servidor de reservas necesita estar en ejecuci�n en el servidor que aloja el portal de Moodle. Puedes encontrar esta aplicaci�n en tu carpeta /mod/ejsappbooking/applets/BookingServer/.';
$string['view_error'] = 'No se pudo abrir la aplicaci�n del sistema de reservas.';
$string['ejsappbookingname'] = 'Nombre del sistema de reservas EJSApp';
$string['ejsappbookingname_help'] = 'Nombre a mostrar para el sistema de reservas EJSApp en tu curso de Moodle.';
$string['ejsappbooking'] = 'EJSAppBooking';
$string['pluginadministration'] = 'Administraci�n de EJSAppBooking';
$string['pluginname'] = 'EJSAppBooking';