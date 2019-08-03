<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$bookid = optional_param('bookid', 0, PARAM_INT); // Book ID

$model = new ejsappbooking_model($id, null );

$exit = $model->delete_booking($bookid);

echo json_encode($exit);
