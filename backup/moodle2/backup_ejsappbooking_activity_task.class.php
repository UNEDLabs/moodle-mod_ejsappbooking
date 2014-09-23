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
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//  - Francisco José Calvillo: ji92camuf@gmail.com
//
// at the Computer Science and Automatic Control, Spanish Open University
// (UNED), Madrid, Spain

/**
 * Tasks file to perform the EJSAppBooking backup
 *
 * Backup/Restore files in ejsappbooking just work with data in the ejsappbooking table.
 * The data from ejsappbooking_remlab_access and ejsappbooking_useraccess is backup/restored
 * by ejsapp
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Luis de la Torre, Ruben Heradio and Francisco José Calvillo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/ejsappbooking/backup/moodle2/backup_ejsappbooking_stepslib.php');
require_once($CFG->dirroot . '/mod/ejsappbooking/backup/moodle2/backup_ejsappbooking_settingslib.php');

/**
 * EJSAppBooking backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_ejsappbooking_activity_task extends backup_activity_task {

    /**
     * Define particular settings for this activity
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Caller to define_structure->define_structure
     */
    protected function define_my_steps() {
        $this->add_step(new backup_ejsappbooking_activity_structure_step('ejsappbooking_structure', 'ejsappbooking.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     *  order to get transportable (encoded) links
     *
     * @param string $content
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/ejsappbooking','#');

        //Access a list of all links in a course
        $pattern = '#('.$base.'/index\.php\?id=)([0-9]+)#';
        $replacement = '$@EJSAPPBOOKINGBINDEX*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        //Access the link supplying a course module id
        $pattern = '#('.$base.'/view\.php\?id=)([0-9]+)#';
        $replacement = '$@EJSAPPBOOKINGVIEWBYID*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }
}