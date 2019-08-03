<?php


global $DB, $CFG, $USER, $PAGE, $OUTPUT;

require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.

$model = new ejsappbooking_model($id, null );

$labinfo = $model->get_lab_info($labid);
echo json_encode($labinfo);