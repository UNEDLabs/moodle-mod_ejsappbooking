<?php

defined('MOODLE_INTERNAL') || die();

class ejsappbooking_json_view {
    
    public function render($data){
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
}
