<?php 

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

abstract class ejsappbooking_controller{
    
    public function __construct($courseid){
        $this->model = new ejsappbooking_model($courseid, null);
    }
    
    abstract public function dispatch();
    
    abstract public function do($params);
}
