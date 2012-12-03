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
 * The main ejsappbooking configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_ejsappbooking_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $OUTPUT;
        
        $mform = $this->_form;
        $ejsappbooking = $DB->record_exists('ejsappbooking', array('course'=>$this->current->course));
        $update = optional_param('update', 0, PARAM_INT);
        
        if(!$ejsappbooking || $update>0){
          //-------------------------------------------------------------------------------
          // Adding the "general" fieldset, where all the common settings are showed
          $mform->addElement('header', 'general', get_string('general', 'form'));

          // Adding the standard "name" field
          $mform->addElement('text', 'name', get_string('ejsappbookingname', 'ejsappbooking'), array('size'=>'64'));
          if (!empty($CFG->formatstringstriptags)) {
              $mform->setType('name', PARAM_TEXT);
          } else {
              $mform->setType('name', PARAM_CLEAN);
          }
          $mform->addRule('name', null, 'required', null, 'client');
          $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
          $mform->addHelpButton('name', 'ejsappbookingname', 'ejsappbooking');

          // Adding the standard "intro" and "introformat" fields
          $this->add_intro_editor();

          // add standard elements, common to all modules
          $this->standard_coursemodule_elements();
          //-------------------------------------------------------------------------------
          // add standard buttons, common to all modules
          $this->add_action_buttons();
        } else{
            //An email activity is already set for this course.
            $mform->addElement('html', $OUTPUT->error_text(get_string('already_enabled', 'ejsappbooking')));
            $mform->addElement($mform->createElement('cancel'));
            $this->standard_hidden_coursemodule_elements();
        }             
    }
    
}