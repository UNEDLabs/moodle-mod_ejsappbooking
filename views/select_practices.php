<?php

// Select practices.
$practices = $DB->get_records_sql(
    "SELECT id, ejsappid, practiceid, practiceintro 
     FROM {block_remlab_manager_exp2prc} 
     WHERE ejsappid = ? ", array($labid));

$i = 1;
foreach ($practices as $practice) {
    $labname[$practice->id] = $multilang->filter($practice->practiceintro);
    if ($i == 1 && $practid == 0) {
        $practid = $practice->practiceid;
    }
    $i++;
} // Select first practices as default;

$select="";
$selectedpractice = '';
$select .= '<select id="foo" name="practid" class="booking_select" data-previousindex="0" > ';

$i = 1;
foreach ($practices as $practice) {
    $labname[$practice->practiceid] = $practice->practiceintro;
    if ($i == 1 && $practid == 0) {
        $practid = $practice->practiceid;
    }
    $select .= '<option value="' . $practice->practiceid . '"';

    if ($practid == $practice->practiceid) {
        $select .= 'selected="selected"';
        $selectedpractice = $labname[$practice->practiceid];
    }
    $select .= '>' . $multilang->filter($labname[$practice->practiceid]) . '</option>';
    $i++;
}

$select .= '</select>';

echo $select;