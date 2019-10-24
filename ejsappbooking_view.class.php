<?php

defined('MOODLE_INTERNAL') || die();

class ejsappbooking_view {
    
    public function __construct($id,$url, $title, $heading, $intro, $remlabs, $practices, $tz, $tz_edit_url) {
        $this->setup_header($url,$title,$heading);
        
        if ( $remlabs == null ){
            $this->body = $this->generate_nolabs_warning();
        } else  {
            $this->body = $this->generate_intro($intro) .
                $this->generate_booking_form($id, $remlabs, $practices, $tz, $tz_edit_url) .
                $this->generate_bookings_table();
        }
    }
    
    /**
     * Load the Javascript and CSS components for page.
     */
    function load_page_components() {
        global $PAGE, $CFG;
        
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui', 'core');
        $PAGE->requires->jquery_plugin('ui-css', 'core');

        $PAGE->requires->js_call_amd('mod_ejsappbooking/ui','init', array(
            $CFG->wwwroot . '/mod/ejsappbooking/controllers'));
    }
    
    /**
     * Abstracted version of print_header() / header()
     *
     * @param string $url The URL of the page
     * @param string $title Appears at the top of the window
     * @param string $heading Appears at the top of the page
     * @throws
     */
    function setup_header($url, $title = '', $heading = '') {
        global $PAGE;

        $PAGE->set_url($url);
        $PAGE->set_title($title);
        $PAGE->set_heading($heading);
    }
    
    public function render() {
        global $OUTPUT;

        $this->load_page_components();
  
        echo  $OUTPUT->header();
        
        echo $this->body;
        
        echo $OUTPUT->footer();
    }
    
    function generate_intro($intro) {
        global $OUTPUT;
        return
            html_writer::start_div('row') .
                html_writer::start_tag('div', array('class' => 'col-md-8 offset-md-1')) .
                    $OUTPUT->box($intro, 'generalbox mod_introbox', 'ejsappbookingintro') .
                html_writer::end_tag('div') .
            html_writer::end_div();
    }
    
    function generate_nolabs_warning() {
        return
            html_writer::start_div('row') .
                html_writer::start_tag('div', array('class' => 'col-md-8 offset-md-2')) .
                    get_string('no_remlabs', 'ejsappbooking') .
                html_writer::end_tag('div') .
            html_writer::end_div();
    }
    
    function generate_booking_form($id, $remlabs, $practices, $tz, $tz_edit_url) {
        global $OUTPUT;

        // Header

        return
            html_writer::start_div('row') .
                html_writer::start_tag('div', array('class' => 'col-md-8 offset-md-2')) .
                    $OUTPUT->heading(get_string('newreservation', 'ejsappbooking')) .
                html_writer::end_tag('div') .
            html_writer::end_div() .
        
        // Form      
      
            html_writer::start_tag('form', array('id' => 'bookingform', 'method' => 'get',
                'action' => new moodle_url("/mod/ejsappbooking/controllers/add_booking.php", array('id' => $id)))) .

                // First row: lab and practice select

                html_writer::start_div('row selectores') .
                    html_writer::start_div('col-md-3 offset-md-2') .
                        get_string('rem_lab_selection', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>' .
                        $this->generate_lab_select($remlabs) .
                    html_writer::end_div() .

                    html_writer::start_div('col-md-3') .
                        get_string('rem_prac_selection', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>' .
                        $this->generate_practice_select($practices) .
                    html_writer::end_div() .
                html_writer::end_div() .  /* row end */

                // Second row: date and time pickers

               html_writer::start_div('row') .

                    // Left column: datepicker and timezone display

                    html_writer::start_div('col-md-3 offset-md-2') .
                        '<span class="ui-icon ui-icon-calendar"></span> &nbsp;' .
                           get_string('date-select', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>' .
                        $OUTPUT->container('<div id="datepicker"></div>') . '<p>' . $tz . '&nbsp; '.
                            "<a href='$tz_edit_url' target='_blank' 
                            title='".get_string('time_zone_help', 'ejsappbooking').
                            "'>"."<span class='ui-icon ui-icon-gear'></span></a></p></br>" .
                    html_writer::end_div() . // column  end

                    // Right column: timepicker, notif area and submit button

                    html_writer::start_div('col-md-3') .
                        '<span class="ui-icon ui-icon-clock"></span>&nbsp;' .
                        get_string('time-select', 'ejsappbooking') . ':' .
                        $this->generate_time_picker() .
                        $this->generate_notif_area() .
                        // submit button
                        html_writer::start_div("", array("id" => "submitwrap")) .
                            html_writer::tag("button", get_string('book', 'ejsappbooking'),
                                array("id" => "booking_btn", "name" => "bookingbutton",
                                    "class" => "btn btn-secondary", "value" => "1", "type" => "submit")) .
                        html_writer::end_div().
                    html_writer::end_div() . // end column

               html_writer::end_div() . /* row end */

            html_writer::end_tag("form");
    }
    
    function generate_lab_select($remlabs) {
        $select_lab = '<select name="labid" class="booking_select" data-previousindex="0" "> '; // onchange="this.form.submit()
        $i = 1;
        foreach ($remlabs as $remlab) {
            $labname[$remlab->lid] = $remlab->name;
            if ($i == 1) {
                $labid = $remlab->lid;
            }
            $select_lab .= '<option value="' . $remlab->lid . '"';

            if ($labid == $remlab->lid) {
                $select_lab .= 'selected="selected"';
            }
            $select_lab .= '>' . $labname[$remlab->lid] . '</option>';
            $i++;
        }
        $select_lab .= '</select><br>';

        return $select_lab;
    }
    
    function generate_practice_select($practices) {
        $i = 1;
        $practid = 0;
        
        foreach ($practices as $practice) {
            // $multilang->filter(
            $labname[$practice->id] = $practice->practiceintro;
            if ($i == 1 && $practid == 0) {
                $practid = $practice->practiceid;
            }
            $i++;
        } // Select first practices as default;

        $select = '<select  name="practid" class="booking_select" data-previousindex="0" > ';
        $i = 1;
        
        foreach ($practices as $practice) {
            $labname[$practice->practiceid] = $practice->practiceintro;
            if ($i == 1 && $practid == 0) {
                $practid = $practice->practiceid;
            }
            $select .= '<option value="' . $practice->practiceid . '"';

            if ($practid == $practice->practiceid) {
                $select .= 'selected="selected"';
            }
            $select .= '>' . $labname[$practice->practiceid] . '</option>';
            
            $i++;
        }

        $select .= '</select>';

        return $select;
    }
    
    function generate_time_picker() {
        return
            html_writer::start_div('', array('id' => 'timepicker')) .
                html_writer::start_tag('table', array('id' => 'hour')) .
                    html_writer::start_tag('tbody') .
                        html_writer::start_tag('tr') .
                            html_writer::start_tag('td') .
                                html_writer::img("pix/sunrise-gray-36px.jpg", "Sunrise") .
                            html_writer::end_tag('td') .
                            html_writer::tag('td', "1" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "2" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "3" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "4" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "5" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "6" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                        html_writer::end_tag('tr') .
                        html_writer::start_tag('tr') .
                            html_writer::start_tag('td', array("rowspan" => "2")) .
                                html_writer::img("pix/sun-gray-36px.jpg", "Sun", array("border" => "0")) .
                            html_writer::end_tag('td') .
                            html_writer::tag('td', "7" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "8" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "9" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "10" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "11" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                            html_writer::tag('td', "12" . html_writer::tag('span', "AM"), array("class" => "hour")) .
                        html_writer::end_tag('tr') .
                        html_writer::start_tag('tr') .
                            html_writer::tag('td', "1" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "2" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "3" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "4" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "5" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "6" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                        html_writer::end_tag('tr') .
                        html_writer::start_tag('tr') .
                            html_writer::start_tag('td') .
                                html_writer::img("pix/moon-gray-36px.jpg", "Moon") .
                            html_writer::end_tag('td') .
                            html_writer::tag('td', "7" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "8" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "9" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "10" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "11" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                            html_writer::tag('td', "12" . html_writer::tag('span', "PM"), array("class" => "hour")) .
                        html_writer::end_tag('tr') .
                    html_writer::end_tag('tbody') .
                html_writer::end_tag('table') .
                html_writer::start_div("", array("style" => "overflow-x:auto;")) .
                    html_writer::start_tag('table', array("id" => "interval")) .
                        html_writer::empty_tag('tr') .
                    html_writer::end_tag('table') .
                html_writer::end_div() .
            html_writer::end_div();
    }
    
    function generate_notif_area() {
        return
            html_writer::start_div("", array("id" => "notif-area")) .
                html_writer::div(get_string('slot-free', 'ejsappbooking'),
                    "alert alert-primary slot-free", array("role" => "alert")) .
                html_writer::div(get_string('slot-past', 'ejsappbooking'),
                    "alert alert-dark slot-past error",array("role" => "alert")) .
                html_writer::div(get_string('slot-busy', 'ejsappbooking'),
                    "alert alert-warning slot-busy error", array("role" => "alert")) .
                html_writer::div(get_string('plant-inactive', 'ejsappbooking'),
                    "alert alert-danger plant-inactive error", array("role" => "alert")) .
                html_writer::div(get_string('plant-active', 'ejsappbooking'),
                    "alert alert-success plant-active", array("role" => "alert")) .
                html_writer::div('&nbsp;', "alert", array("id" => "notif", "role" => "alert")) .
                html_writer::div(get_string('submit-error', 'ejsappbooking'),
                    "alert", array("id" => "submit-error", "role" => "alert")) .
                html_writer::div(get_string('submit-missing-field', 'ejsappbooking'),
                    "alert submit-missing-field", array("role" => "alert")) .
            html_writer::end_div();
    }
    
    function generate_bookings_table() {
        global $OUTPUT;

        return
            html_writer::start_div('row') .
                html_writer::start_div('col-md-7 offset-md-2') .
                    $OUTPUT->heading(get_string('mybookings', 'ejsappbooking')) .
                html_writer::end_div() .

                html_writer::start_div('col-md-3') .
                    '<nav><ul class="pagination" style="display: none">
                    <li class="page-item"><a class="page-link" href="#">&laquo; </a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                    </ul></nav>' .
                html_writer::end_div() .
            html_writer::end_div() . /* row end */

            html_writer::start_div('row') .
                html_writer::start_div('col-md-6 offset-md-2') .
                    '<p id="mybookings_notif" >'. get_string('mybookings_empty','ejsappbooking') . '</p>' .
                    '<table id="mybookings" class="table table-hover table-responsive-sm" style="display: none">
                        <thead><tr>
                            <th></th>
                            <th>'.get_string('date', 'ejsappbooking').'</th>              
                            <th>'.get_string('plant', 'ejsappbooking').'</th>
                            <th>'.get_string('hour', 'ejsappbooking').'</th>
                            <th>'.get_string('action', 'ejsappbooking').'</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>' .
                    html_writer::div(get_string('delete-confirmation', 'ejsappbooking'), "alert",
                        array("id" => "del-confirm", "role" => "alert")) .
                html_writer::end_div() . // end col
            html_writer::end_div(); // end row
    }

}