<?php 

$username = $USER->username;
$today = new DateTime("now");
$baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));

$bookingtable = new html_table();
$bookingtable->attributes['class'] = 'table table-hover';
//    $bookingtable->attributes['border'] = '1';
$bookingtable->id = 'tablabooking';
$bookingtable->align[1] = 'center';

$bookingtable->head = array(' ', get_string('plant', 'ejsappbooking'),
            get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'), 'Action ');

// Show user´s bookings.
$events = $DB->get_records_sql(
    "SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name 
    FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
    WHERE a.username = ? AND a.starttime >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) 
    ORDER BY a.starttime ASC", 
    array($username, $today->format('Y-m-d H:i')));

$result = count($events);

// Page´s configuration.
$bookingpage = 12;
$pages = ceil($result / $bookingpage);
$currentpage = 0;

if (isset($page) || empty($page)) {
    $currentpage = $page;
}

if ($currentpage < 1) {
    $currentpage = 1;
} else if ($currentpage > $pages) {
    $currentpage = $pages;
}

$initbook = ($currentpage - 1) * $bookingpage;

// Check bookings.
$events2 = $DB->get_records_sql("
    SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro 
    FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id 
    INNER JOIN {block_remlab_manager_exp2prc} c ON a.practiceid = c.practiceid  
    WHERE a.ejsappid = c.ejsappid AND a.username = ? AND starttime  >= to_timestamp( ?, 'YYYY-MM-DD HH24:MI' ) 
    ORDER BY a.starttime ASC LIMIT ? OFFSET ?",
    array($username,date("Y-m-d H:i"), $bookingpage, $initbook));


$result2 = count($events2);

// Exists.
if ($result != 0) {

    $i = 0;
    foreach ($events2 as $event) {

        $name = 'booking[' . $i . ']';
        $value = $event->id;

        $time = new DateTime($event->starttime);
        $timeend = new DateTime($event->endtime);
        $visible = array(null);

        if ($today->format("Y-m-d H:i") < $time->format("Y-m-d H:i") ||
            $today->format("Y-m-d H:i") < $timeend->format("Y-m-d H:i")) {
            $url = 'available.png';
        } else {
            $url = 'busy.png';
            if ($today->format("Y-m-d") == $time->format("Y-m-d")) {
                $visible = array('disabled' => 'disable');
            }
        }

        $bookingtable->data[] = new html_table_row();
        
        $bookingcell = new html_table_cell();
        $bookingcell->attributes['class'] = 'text-center';
        
        if ($currentpage == 1) {
            $numpage = ($i + 1);
        } else {
            $numpage = (($currentpage - 1) * $bookingpage) + ($i + 1);
        }
        $bookingcell->text = ' ' . $numpage . ' ';
        $bookingtable->data[$i]->cells[] = $bookingcell;

        // Add link to access the lab if the current time is within the booking slot.
        $currentslot = false;
        if ($today->format("Y-m-d H:i:s") > $time->format("Y-m-d H:i:s") &&
            $today->format("Y-m-d H:i:s") < $timeend->format("Y-m-d H:i:s")) {
            $currentslot = true;
        }

        $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
        
        $bookingcell = new html_table_cell();
        $bookingcell->attributes['id'] = 'mybookings';
        $bookingcell->attributes['class'] = 'center';
        
        if ($currentslot) {
            // Add link to access the lab if the current time is within the booking slot.
            $bookingcell->text = "<a href='../ejsapp/view.php?n=" . $event->ejsappid . "'>" .
                $multilang->filter($event->name) . '. ' . $event->practiceintro . "</a>";
        } else {
            $bookingcell->text = $multilang->filter($event->name) . '. ' . $event->practiceintro;
        }

        $bookingtable->data[$i]->cells[] = $bookingcell;
        $bookingcell = new html_table_cell();
        $bookingcell->attributes['class'] = 'center';
        $bookingcell->text = $time->format("Y-m-d");
        $bookingtable->data[$i]->cells[] = $bookingcell;

        $bookingcell = new html_table_cell();
        $bookingcell->attributes['class'] = 'center';
        $time2 = new DateTime($event->endtime);
        $bookingcell->text = $time->format("H:i") . '-' . $time2->format("H:i");;
        $bookingtable->data[$i]->cells[] = $bookingcell;
        
        $bookingcell = new html_table_cell();
        $bookingcell->attributes['class'] = 'text-center';
        //$bookingcell->text = html_writer::checkbox($name, $value, false, null, $visible);
        
        $del_url = "controllers/delete_booking.php?id=".$id."&bookid=" . $event->id ;
        $bookingcell->text = '<a href="'.$del_url.'" class="del_btn" >
            <span class="ui-icon ui-icon-trash " >  &nbsp; </span></a>';
        
        $bookingtable->data[$i]->cells[] = $bookingcell;
    
        $i++;
    }
    
}

echo html_writer::table($bookingtable);

if ($result > $bookingpage) {

    echo '<div class="paginacion">';

    // Show pagination.
    for ($i = 1; $i <= $pages; $i++) {

        if ($i == $currentpage) {
            echo '&nbsp;&nbsp;&nbsp;<span class="pagina actual">' . $i . '</span>';
        } else if ($i == 1 || $i == $pages || ($i >= $currentpage - 2 && $i <= $currentpage + 2)) {
            echo '&nbsp;&nbsp;&nbsp;<a href="' . $baseurl->out() . '&page=' . $i . '" class="pagina">' . $i . '</a>';
        }
    }
    echo '</div>';
}
