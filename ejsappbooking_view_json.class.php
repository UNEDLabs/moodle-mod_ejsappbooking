<?php

defined('MOODLE_INTERNAL') || die();
/*
require_once(__DIR__."/lib.php");
require_once(__DIR__.'/turnitintooltwo_form.class.php');
require_once(__DIR__.'/turnitintooltwo_submission.class.php');
*/

class ejsappbooking_json_view {
    
    public function render($data){
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
}
    
?>
