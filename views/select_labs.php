<?php
    
    $select_lab = '<select name="labid" class="booking_select" data-previousindex="0" "> '; // onchange="this.form.submit()
    $currentlab = '';
    $i = 1;
    foreach ($remlabs as $remlab) {
        $labname[$remlab->id] = $remlab->name;
        if ($i == 1 && $labid == 0) {
            $labid = $remlab->id;
        }
        $select_lab .= '<option value="' . $remlab->id . '"';

        if ($labid == $remlab->id) {
            $select_lab .= 'selected="selected"';
            $currentlab = $labname[$remlab->id];
        }
        $select_lab .= '>' . $multilang->filter($labname[$remlab->id]) . '</option>';
        $i++;
    }
    $select_lab .= '</select><br>';
    
    echo $select_lab;