<?php
/*
Functions for hooking into the display class
*/

function ewm_show_small_calendar($params = ''){
  global $eventDisplay;

  $defaults = array(
    'month' => date("n"), 'year' => date("Y"),
    'ajax' => false,'admin' => false,
    'visiblecategory' => false,
    'dayNameLength' => 3
  );
  
  $r = wp_parse_args( $params, $defaults );
  //extract( $r, EXTR_SKIP );

  //$eventDisplay->grab_proper_includes("smallcalendar");

  $theCalendar = $eventDisplay->show_small_calendar($r);
  return $theCalendar;
}

function ewm_get_event_array($params = ''){
  global $eventDisplay;

  $defaults = array(
    'month' => false, 'year' => false, 'week' => false, 'day' => false,
    'start' => false, 'end' => false,
    'limit' => 10
  );

  $r = wp_parse_args( $params, $defaults );
  $thearray = $eventDisplay->get_events($r);
  return $thearray;
}