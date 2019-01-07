<?php 

// Delete booking request.

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;


$book = optional_param('bookid', 0, PARAM_INT); // Book ID

// Check and delete bookings.
$record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $book));

if ( ! $record ){
    $data['exitCode']=-1;
    $data['exitMsg']="Booking id not found ".$book;
}else { 
    $lab = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
    $prac = $DB->get_record('block_remlab_manager_exp2prc', array('practiceid' => $record->practiceid,
        'ejsappid' => $record->ejsappid));
    $inittime = new DateTime($record->starttime);

    $error = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $book));
    
    if ($error < 0 ){
        $data['exitCode']=-2;
        $data['exitMsg']="Error deleting record" . $error;
    } else {
        $data['exitCode']=0;
        $data['exitMsg']="Success";
    }
}

echo json_encode($data);
