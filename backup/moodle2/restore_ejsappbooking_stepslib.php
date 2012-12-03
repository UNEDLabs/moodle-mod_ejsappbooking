<?php

// This file is part of the Moodle module "EJSApp"
//
// EJSApp is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// EJSApp is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License is available on <http://www.gnu.org/licenses/>
//
// EJSApp has been developed by:
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain


/**
 * Steps file to perform the EJSAppBooking restore
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Structure step to restore one EJSAppBooking activity
 */
class restore_ejsappbooking_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure
     */
    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('ejsappbooking', '/activity/ejsappbooking');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }//define_structure

    /**
     * Process table process_ejsappbooking
     * @param stdClass $data
     */
    protected function process_ejsappbooking($data)
    {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();

        $ejsappbooking_has_been_restored_by_EJSApp = $DB->get_record('ejsappbooking',array('course'=>$data->course));
        if (!$ejsappbooking_has_been_restored_by_EJSApp) {
            echo "aqui1";
            $newitemid = $DB->insert_record('ejsappbooking', $data);
        } else {
            echo "aqui2";
            xdebug_var_dump($ejsappbooking_has_been_restored_by_EJSApp);
            $newitemid = $ejsappbooking_has_been_restored_by_EJSApp->id;
        }
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }//process_ejsappbooking_remlab_access


    /**
     * Do nothing
     */
    protected function after_execute() {
    } //after_execute
    
} //class