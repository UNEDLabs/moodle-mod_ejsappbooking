<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$action = optional_param('action',0, PARAM_RAW); 

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//  $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));

$data="";

switch ($action){
    case "info":
        $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));
        $conflabs = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));
        $slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration', array('practiceintro' => $practiceintro));
        
        $data['status']=$conflabs->active;
        
        $practices = $DB->get_records_sql("
            SELECT id, ejsappid, practiceid, practiceintro FROM {block_remlab_manager_exp2prc} 
            WHERE ejsappid = ? ", 
            array($labid));
        
            foreach ($practices as $practice) {
                $practices_loc[$practice->id] = $multilang->filter($practice->practiceintro);
            }
        $data['practices'] = $practices_loc;
        
        $data['slotduration'] = $slotduration;
    
        break;
}

echo json_encode($data);
