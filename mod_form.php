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
 * The main ejsappbooking configuration form
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ejsappbooking_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $OUTPUT, $CFG;

        $mform = $this->_form;
        $ejsappbooking = $DB->record_exists('ejsappbooking', array('course' => $this->current->course));
        $update = optional_param('update', 0, PARAM_INT);

        if (!$ejsappbooking || $update > 0) {
            // Adding the "general" fieldset, where all the common settings are showed.
            $mform->addElement('header', 'general', get_string('general', 'form'));

            // Adding the standard "name" field.
            $mform->addElement('text', 'name', get_string('ejsappbookingname', 'ejsappbooking'), array('size' => '64'));
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_CLEANHTML);
            }
            $mform->addRule('name', null, 'required', null, 'client');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $mform->addHelpButton('name', 'ejsappbookingname', 'ejsappbooking');

            // Adding the standard "intro" and "introformat" fields.
            if ($CFG->version < 2015051100) {
                $this->add_intro_editor();
            } else {
                $this->standard_intro_elements();
            }

            // Adding standard elements, common to all modules.
            $this->standard_coursemodule_elements();

            // Adding standard buttons, common to all modules.
            $this->add_action_buttons();
        } else {
            // A booking system is already set for this course.
            $mform->addElement('html', $OUTPUT->error_text(get_string('already_enabled', 'ejsappbooking')));
            $mform->addElement($mform->createElement('cancel'));
            $this->standard_hidden_coursemodule_elements();
        }
    }

}