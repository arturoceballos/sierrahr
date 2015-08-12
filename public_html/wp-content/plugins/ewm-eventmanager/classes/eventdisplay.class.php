<?php
/*
Formats and Displays events for the EWM Event Manager plugin
*/

class ewm_EventDisplay {

  ////////////////////////////////////////////////////////////////////////////////
  // Vars and Constructor
  ////////////////////////////////////////////////////////////////////////////////
  
  // set for template redirection
  var $eventId;
  var $calendarMonth;
  
  var $viewType;
  var $outputData;
  var $theTemplate;
  var $displayParams;
  var $cssIncludes;
  var $visiblecategory;
  var $categoryListingCount;

	function ewm_EventDisplay() {
    global $current_user, $wpdb;

    $charles = get_option('delicious');

    //set the initial values
    $this->outputData = false;
    $this->cssIncludes = '';
    $this->categoryListingCount = 0;

    // set whether this is for eventdisplay
    $adminPage = ( $_GET['isadminpage'] == 'true' ? true : false );
    
    // show ajax calendar
    if(isset($_GET['EC_action']) && $_GET['EC_action'] == 'switchMonth') {
      switch($_GET['EC_type']){
      case "largecalendar":
        $displayParams['month'] = $_GET['EC_month'];
        $displayParams['year'] = $_GET['EC_year'];
        $displayParams['admin'] = $adminPage;
        $displayParams['ajax'] = true;
        $theCalendar = $this->show_large_calendar($displayParams);
        echo $theCalendar['the_content'];
        break;

      case "smallcalendar":
        $displayParams['month'] = $_GET['EC_month'];
        $displayParams['year'] = $_GET['EC_year'];
        $displayParams['admin'] = $adminPage;
        $displayParams['ajax'] = true;
        $theCalendar = $this->show_small_calendar($displayParams);
        echo $theCalendar['the_content'];
        break;
        
      }
      exit();
    }
	}

  ////////////////////////////////////////////////////////////////////////////////
  // Rewrite Rules & Parsing
  ////////////////////////////////////////////////////////////////////////////////
    
  //Rewrite rules section
  function insert_rewrite_rules($rules){
  	$newrules = array();
  	// event info rules: without or with slug
  	$newrules['calendar/id/?([0-9]+)/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=event_info&ewm_em_eventid=$matches[1]';
  	$newrules['calendar/id/?([0-9]+)/(.+)?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=event_info&ewm_em_eventid=$matches[1]';
    // calendar rules: year, month, week, day
    $newrules['calendar/([0-9]{4})/([0-9]{2})/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=calendar&ewm_em_year=$matches[1]&ewm_em_month=$matches[2]';
    // eventlist rules: year, month, week, day,
    $newrules['calendar/list/([0-9]{4})/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=eventlist&ewm_em_year=$matches[1]';
    $newrules['calendar/list/([0-9]{4})/([0-9]{1,2})/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=eventlist&ewm_em_year=$matches[1]&ewm_em_month=$matches[2]';
    $newrules['calendar/list/([0-9]{4})/([0-9]{2})/([0-9]{1,2})/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=eventlist&ewm_em_year=$matches[1]&ewm_em_month=$matches[2]&ewm_em_day=$matches[3]';  	
    $newrules['calendar/list/([0-9]{4})/([0-9]{2})/week/([1-5]{1,})/?$'] = 'index.php?pagename=about_phantom&ewm_em_viewtype=eventlist&ewm_em_year=$matches[1]&ewm_em_month=$matches[2]&ewm_em_week=$matches[3]';
  	return $newrules+$rules;
  }

  function insert_rewrite_query_vars($vars){
  	array_push($vars, 'ewm_em_viewtype', 'ewm_em_eventid', 'ewm_em_day', 'ewm_em_month', 'ewm_em_year', 'ewm_em_week');
  	return $vars;
  }

  function parse_rewrite_rules_query($query){
    global $post;

    // set the id/month/viewtype, for use later in the get_proper_template
  	if(!empty($query->query_vars['ewm_em_eventid']))
      $this->eventId = $query->query_vars['ewm_em_eventid'];
      
  	if(!empty($query->query_vars['ewm_em_month']))
      $this->calendarMonth = $query->query_vars['ewm_em_month'];

    // set the viewtype, if it exists, and redirect accordingly
  	if(!empty($query->query_vars['ewm_em_viewtype'])){
      $this->viewType = $query->query_vars['ewm_em_viewtype'];  	
      // grab the appropriate dataset, format it
      switch($this->viewType){
      case "event_info":
        $this->outputData = $this->show_event_info($this->eventId);
        break;
      case "calendar":
        $this->displayParams['month'] = ltrim($query->query_vars['ewm_em_month'], 0);
        $this->displayParams['year'] = $query->query_vars['ewm_em_year'];
        $this->outputData = $this->show_large_calendar($this->displayParams);
        break;
      case "eventlist":
        $this->displayParams['day'] = $query->query_vars['ewm_em_day'];
        $this->displayParams['month'] = ltrim($query->query_vars['ewm_em_month'], 0);
        $this->displayParams['year'] = $query->query_vars['ewm_em_year'];
        $this->displayParams['week'] = $query->query_vars['ewm_em_week'];
        $this->outputData = $this->show_event_list($this->displayParams);
        break;
      }
      // redirect to the template page
      add_action('template_redirect', array($this, 'template_redirection'));
      
    }
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Template Redirection & Loop Content Filtering
  ////////////////////////////////////////////////////////////////////////////////

  function template_redirection() {
    global $wp_query;

    // redirect the query if there's no output data for the page requested
    if(!($this->outputData)){
      $homeurl = get_bloginfo("url");
      wp_redirect($homeurl);
    }

    // plug [er...trick] wordpress into thinking it's a page
    $wp_query->current_post = -1;
    $wp_query->post_count = 1;
    $wp_query->is_page = true;

    // prevent the_content & the_title from being changed globally within the template
    // limit the action to within the loop
    add_action('loop_start', array($this, 'loop_start_mods'));
    add_action('loop_end', array($this, 'loop_end_mods'));

    // enqueue js and include css
    $this->grab_proper_includes();

    // grab the template and wrap things up
    $this->theTemplate = $this->grab_proper_template();
    include($this->theTemplate);
  	exit;
  }

  function loop_start_mods(){
    add_filter('the_title', array($this, 'insert_the_title'));
    add_filter('the_content', array($this, 'insert_the_content'));
  }

  function loop_end_mods(){
    remove_filter('the_title', array($this, 'insert_the_title'));
    remove_filter('the_content', array($this, 'insert_the_content'));
  }

  function insert_the_title($title){
    return $this->outputData['the_title'];
  }
  
  function insert_the_content($content){
    return $this->outputData['the_content'];
  }

  function grab_proper_template() {
    $template = false;
    switch($this->viewType){
    case "event_info":
      if ( file_exists(TEMPLATEPATH . "/eventpage-" . $this->eventId. '.php') )
        $template = TEMPLATEPATH . "/eventpage-" . $this->eventId . '.php';
      else if ( file_exists(TEMPLATEPATH . "/eventpage.php") )
        $template = TEMPLATEPATH . "/eventpage.php";
      else if ( file_exists(TEMPLATEPATH . "/page.php") )
        $template = TEMPLATEPATH . "/page.php";
        
      //$template = TEMPLATEPATH . "/tpl_pageFull.php";
      break;
    case "calendar":
      if ( file_exists(TEMPLATEPATH . "/calendarpage-" . $this->calendarMonth. '.php') )
        $template = TEMPLATEPATH . "/calendarpage-" . $this->calenderMonth . '.php';
      else if ( file_exists(TEMPLATEPATH . "/calendarpage.php") )
        $template = TEMPLATEPATH . "/calendarpage.php";
      else if ( file_exists(TEMPLATEPATH . "/page.php") )
        $template = TEMPLATEPATH . "/page.php";
      break;
    case "eventlist":
      if ( file_exists(TEMPLATEPATH . "/eventlistpage.php") )
        $template = TEMPLATEPATH . "/eventlistpage.php";
      else if ( file_exists(TEMPLATEPATH . "/page.php") )
        $template = TEMPLATEPATH . "/page.php";
      break;
    }
    return $template;
  }
  
  function grab_proper_includes($viewArg = false) {
    $cssFile = false;
    if($viewArg == false)
      $viewArg = $this->viewType;
    switch($viewArg){
    case "event_info":
      break;
    case "calendar":
      wp_enqueue_script('jquery');
      $cssFile = 'calendar-large.css';
      break;
    case "smallcalendar":
      wp_enqueue_script('jquery');
      break;
    case "eventlist":
      break;
    }
    if($cssFile){
      $this->cssIncludes .= '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/'.$cssFile.'" type="text/css" media="screen" />'."\n";
      add_action('wp_head', array($this, 'insert_css' ));
    }
  }

  function insert_css(){
    echo $this->cssIncludes;
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Short-Tag
  ////////////////////////////////////////////////////////////////////////////////

  function ewm_em_shorttag($atts) {

    // defaults are set here and supercede those set within the 'show' methods
    $thisMonth = date("m");
    $thisYear = date("Y");
  
  	extract(shortcode_atts(array(
  		'month' => $thisMonth,
  		'year' => $thisYear,
  		'type' => ''
  	), $atts));
  
    //$ewm_output = "<script type='text/javascript' src='http://fpc.elementwebmedia.com/wp-admin/js/common.js'></script>\n\n";
    switch($type){
    case "largecalendar":
      $ewm_output .= '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/calendar-large.css" type="text/css" media="screen" />'."\n";
      $the_calendar = $this->show_large_calendar($atts);
      $ewm_output .= $the_calendar['the_content'];
      break;
    case "smallcalendar":
      $ewm_output .= '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/calendar-small.css" type="text/css" media="screen" />'."\n";
      $the_calendar = $this->show_small_calendar($atts);
      $ewm_output .= $the_calendar['the_content'];
      break;
    case "eventlist":
      $ewm_output .= '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/eventlist.css" type="text/css" media="screen" />'."\n";
      $the_eventlist .= $this->show_event_list($atts);
      $ewm_output .= $the_eventlist['the_content'];
      break;
    }
    return $ewm_output;
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Event Info Views
  ////////////////////////////////////////////////////////////////////////////////

  function show_event_info($id) {
    $eventData = $this->get_event_by_id($id);

    if(!is_array($eventData))    
      return false;

    $outputData = array();
    $outputData['the_title'] = $eventData['title'];
// JUSTIN HAS EDITED ///////////////////////////////////////////////////////////
    // format the time
    if ($eventData['endtime']) {
      if (date('m/d/Y', strtotime($eventData['starttime'])) == date('m/d/Y', strtotime($eventData['endtime']))) {
        $theTime = date('M j, Y @ g:ia', strtotime($eventData['starttime'])) . ' - ' . date('g:ia', strtotime($eventData['endtime']));
      } else {
        $theTime = date('M j, Y @ g:ia', strtotime($eventData['starttime'])) . '<br />to ' . date('M j, Y @ g:ia', strtotime($eventData['endtime']));
      }
    } else {
      $theTime = date('M j, Y @ g:ia', strtotime($eventData['starttime']));
    }

    $outputData['the_content'] =  '<p>'.nl2br($eventData['description'])."</p>\n";
    $outputData['the_content'] .= '<h4>When:</h4><p>'.$theTime."</p>\n";
    $outputData['the_content'] .= '<h4>Where:</h4><p>'.$eventData['address'].'<br />'.$eventData['city'].', '.$eventData['state'].' '.$eventData['zip']."</p>\n";
    $outputData['the_content'] .= '<h4>Contact:</h4><p>For more information you may contact<br />'.($eventData['contactname'] ? $eventData['contactname'].'<br />' : '').($eventData['contactphone'] ? $eventData['contactphone'].'<br />' : '').($eventData['contactemail'] ? '<a href="mailto:'.$eventData['contactemail'].'">'.$eventData['contactemail'].'</a>' : '')."</p>\n";
    if($eventData['rsvpactive'] == '1'){
      $outputData['the_content'] .= '<a href="/rsvp-form/?rsvpid='.$eventData['id'].'" class="email_linkr">&laquo; Click Here to RSVP for This Event &raquo;</a>';
    }
    /*
    foreach($eventData as $key => $value){
    
      if (!($key == 'title')){
        $outputData['the_content'] .= $key . " : " . $value . "<br/>";
      }
      
    }
    */
// JUSTIN HAS EDITED ///////////////////////////////////////////////////////////
    return $outputData;
  }
	
  ////////////////////////////////////////////////////////////////////////////////
  // Large Calendar Views
  ////////////////////////////////////////////////////////////////////////////////

  function show_large_calendar($params = '') {
    global $wp_rewrite;// remove this post-debugging

    //echo '<pre>';
    //print_r($wp_rewrite);
    //echo '</pre>';

    $defaults = array(
      'month' => date("n"), 'year' => date("Y"),
      'ajax' => false,'admin' => false,
      'dayNameLength' => 3
    );

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );

    $month = ltrim($month, '0');
  
    $admin = ( $admin == true ? 'true' : 'false' );
    $blogUrl = get_bloginfo('url');
  
    $calendarData = $this->format_calendar_data_month($month, $year);

    $firstDay = 0; // 0 is Sunday
    $firstOfMonth = gmmktime(0,0,0,$month,1,$year);
    
    $dayNames = array();
    for($n=0,$t=(3+$firstDay)*86400; $n<7; $n++,$t+=86400) //January 4, 1970 was a Sunday
      $dayNames[$n] = ucfirst(gmstrftime('%A',$t)); //%A means full textual day name
    
    list($month, $year, $monthName, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$firstOfMonth));
    $weekday = ($weekday + 7 - $firstDay) % 7; //adjust for $first_day
    $title = htmlentities(ucfirst($monthName))."&nbsp;".$year;
    $previousMonth = date('F', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth = date('F', mktime(0, 0, 0, $month+1, 1, $year));
    $previousMonthInt = date('n', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonthInt = date('n', mktime(0, 0, 0, $month+1, 1, $year));
    $calendar = '';
    
    if($ajax == false){
      $calendar .= "<div class=\"calendar_wrapLarge\">";  
    }
  
    //<a href=\"ddd\">" . substr($previousMonth, 0, 3) . "</a>
    $calendar .= "<span class=\"EC_previousMonthLarge\"></span><span class=\"EC_nextMonthLarge\"></span><h3><span class=\"month_title\">$title</span></h3>";
    $calendar .= "<table class=\"wp-calendarLarge\">\n\t<thead>\n\t<tr>\n";
    
    if($dayNameLength){ //if the day names should be shown ($day_name_length > 0)
      //if day_name_length is >3, the full name of the day will be printed
      foreach($dayNames as $d)
    	  $calendar .= "\t\t<th abbr=\"".htmlentities($d)."\" scope=\"col\" title=\"".htmlentities($d)."\"><span>".htmlentities($dayNameLength < 4 ? substr($d,0,$dayNameLength) : $d)."</span></th>\n";
      $calendar .= "\t</tr>\n\t</thead>\n\t<tbody>\n\t<tr>\n\t";
    }
    
    if($weekday > 0)
      $calendar .= "\t\t<td colspan=\"".$weekday."\" class=\"pad\">&nbsp;</td>"; //initial \"empty\" days
      
    for($day=1,$daysInMonth=gmdate('t',$firstOfMonth); $day<=$daysInMonth; $day++,$weekday++){
      if($weekday === 7){
    	  $weekday  = 0; //start a new week
    	  $calendar .= "\n\t</tr>\n\t<tr>\n\t\t";
      }

      $dayID = ( $day == date('j') && $month == date('n') && $year == date('Y') ) ? " class=\"todayLarge\"" : "";
      $eventListing = '';
  
      if(array_key_exists($day, $calendarData)) {
        foreach($calendarData[$day] as $elisting){
          $evTitle = ( (strlen($elisting->title) > 20 ) ? substr($elisting->title, 0, 18)."&hellip;" : $elisting->title  );
          $evTitleFull = $elisting->title;
          $evSlug = $this->format_title_to_slug($evTitleFull);
          if($admin == 'true'){
            $eventListing .= "<span class='events-calendar-".$day."-".$elisting->id."Large'><a class='event_title' href='". get_settings('siteurl') ."/wp-admin/admin.php?page=".EWM_EM_PLUGIN_BASE."/events.php&amp;eventid=".$elisting->id."' title='".$evTitleFull."'>".$evTitle."</a></span><br />";
          } else {
            if($elisting->active == 1){
              $eventListing .= "<span class='events-calendar-".$day."-".$elisting->id."Large'><a class='event_title' href='".$blogUrl."/calendar/id/".$elisting->id."/".$evSlug."' title='".$evTitleFull."'>".$evTitle."</a></span><br />";
            }
          }
        }
      }    
      $calendar .= "<td".$dayID."><div class=\"dayHead\">$day</div><div class=\"events-calendar-".$day."Large\">".$eventListing."</div></td>";
    }
    if($weekday != 7) $calendar .= "\n\t\t<td colspan=\"".(7-$weekday)."\" class=\"pad\">&nbsp;</td>"; //remaining "empty" days
    
    $calendar .= "\t</tr>\n\t</tbody>\n\t</table> \n\t\n\t";

    $jsparams = array();
    $jsparams['month'] = $month;
    $jsparams['year'] = $year;
    $jsparams['admin'] = $admin;
    
    $calendar .= $this->show_large_calendar_js($jsparams);
  
    if($ajax == false){
      $calendar .= "</div>"; 
    }
    
    $outputData = array();
    $outputData['the_content'] = $calendar;
    $outputData['the_title'] = $this->format_content_title($r);
    
    return $outputData;
  }

  function show_large_calendar_js($params = ''){
    
    $defaults = array(
      'month' => date("n"), 'year' => date("Y"),
      'admin' => false
    );

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );
  
    $previousMonth = date('n', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth = date('n', mktime(0, 0, 0, $month+1, 1, $year));
  
    $previousMonth3 = date('M', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth3 = date('M', mktime(0, 0, 0, $month+1, 1, $year));
    
    $previousYear = date('Y', mktime(0, 0, 0, $month-1, 1, $year));
    $nextYear = date('Y', mktime(0, 0, 0, $month+1, 1, $year));
  
    $bloginfourl = get_bloginfo('url');
  
    $calendarjs .= "\n";
  
    $calendarjs .= "<script type='text/javascript'>\n";
    $calendarjs .= "//<![CDATA[\n\n";
    
    if($admin){
      $calendarjs .= 'jQuery(function($) {'."\n";
      $calendarjs .= '$(document).ready(function() {'."\n\t";
    }
        $calendarjs .= '$(".EC_previousMonthLarge").append("&laquo; '.$previousMonth3.'");'."\n\t";
        $calendarjs .= '$(".EC_nextMonthLarge").prepend("'.$nextMonth3.' &raquo;");'."\n\t";
    
        $calendarjs .= '$(".EC_previousMonthLarge").mouseover(function() {'."\n\t\t";
          $calendarjs .= '$(this).css("cursor", "pointer");'."\n\t";
        $calendarjs .= '});'."\n\t";
        
        $calendarjs .= '$(".EC_nextMonthLarge").mouseover(function() {'."\n\t\t";
          $calendarjs .= '$(this).css("cursor", "pointer");'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$(".EC_previousMonthLarge").click(function() {'."\n\t\t";
          $calendarjs .= '$(".month_title").empty();'."\n\t\t";
          $calendarjs .= '$(".month_title").append("<img src=\"'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/ajax-loader.gif\" style=\"padding-top:7px;\" alt=\"Loading\" />");'."\n\t\t";
          $calendarjs .= '$.get("'.$bloginfourl.'",'."\n\t\t";
          $calendarjs .= '{EC_action: "switchMonth", EC_month: '.$previousMonth.', EC_year: '.$previousYear.', EC_type: "largecalendar", ajax: false, isadminpage: '.$admin.'},'."\n\t\t";
          $calendarjs .= 'function(data) {'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapLarge").empty();'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapLarge").html(data);'."\n\t\t";
            $calendarjs .= '$(".event_title").tTips();'."\n\t\t";
          $calendarjs .= '});'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$(".EC_nextMonthLarge").click(function() {'."\n\t\t";
          $calendarjs .= '$(".month_title").empty();'."\n\t\t";
          $calendarjs .= '$(".month_title").append("<img src=\"'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/ajax-loader.gif\" style=\"padding-top:7px;\" alt=\"Loading\" />");'."\n\t\t";
          $calendarjs .= '$.get("'.$bloginfourl.'",'."\n\t\t";
          $calendarjs .= '{EC_action: "switchMonth", EC_month: '.$nextMonth.', EC_year: '.$nextYear.', EC_type: "largecalendar", ajax: false, isadminpage: '.$admin.'},'."\n\t\t";
          $calendarjs .= 'function(data) {'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapLarge").empty();'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapLarge").html(data);'."\n\t\t";
            $calendarjs .= '$(".event_title").tTips();'."\n\t\t";
          $calendarjs .= '});'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$.preloadImages = function() {'."\n\t\t";
          $calendarjs .= 'for(var i = 0; i<arguments.length; i++){'."\n\t\t\t";
            $calendarjs .= '$("<img>").attr("src", arguments[i]);'."\n\t\t";
          $calendarjs .= '}'."\n\t";
        $calendarjs .= '}'."\n\t";
        $calendarjs .= '$.preloadImages("'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/loading.gif");'."\n";

    if($admin){
      $calendarjs .= "});"."\n";
      $calendarjs .= "});"."\n\n";
    }
    
    $calendarjs .= "//]]>"."\n";
    $calendarjs .= "</script>"."\n";
    $calendarjs .= ""."\n";

    return $calendarjs;
    ?>  
  <?php
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Small Calendar Views
  ////////////////////////////////////////////////////////////////////////////////

  function show_small_calendar($params = '') {

    $this->viewType = 'smallcalendar';

    $defaults = array(
      'month' => date("n"), 'year' => date("Y"),
      'ajax' => false,'admin' => false,
      'visiblecategory' => false,
      'dayNameLength' => 3
    );

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );

    $this->visiblecategory = $visiblecategory;

    $admin = ( $admin == true ? 'true' : 'false' );
    $blogUrl = get_bloginfo('url');
  
    $calendarData = $this->format_calendar_data_month($month, $year);

    $firstDay = 0; // 0 is Sunday
    $firstOfMonth = gmmktime(0,0,0,$month,1,$year);
    
    $dayNames = array();
    for($n=0,$t=(3+$firstDay)*86400; $n<7; $n++,$t+=86400) //January 4, 1970 was a Sunday
      $dayNames[$n] = ucfirst(gmstrftime('%A',$t)); //%A means full textual day name
    
    list($month, $year, $monthName, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$firstOfMonth));
    $weekday = ($weekday + 7 - $firstDay) % 7; //adjust for $first_day
    $title = htmlentities(ucfirst($monthName))."&nbsp;".$year;
    $previousMonth = date('F', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth = date('F', mktime(0, 0, 0, $month+1, 1, $year));
    $calendar = '';
    
    if($ajax == false){
      $calendar .= "<div class=\"calendar_wrapSmall\">";  
    }
  
    $calendar .= "<span class=\"EC_previousMonthSmall\"></span><span class=\"EC_nextMonthSmall\"></span><h2><span class=\"month_title\">$title</span></h2>";
    $calendar .= "<div class=\"tableWrap\"><span class=\"calTop\"></span><table class=\"wp-calendarSmall\" cellpadding=\"0\" cellspacing=\"0\">\n\t<thead>\n\t<tr>\n";
    
    if($dayNameLength){ //if the day names should be shown ($day_name_length > 0)
      //if day_name_length is > 3, the full name of the day will be printed
      foreach($dayNames as $d)
    	  $calendar .= "\t\t<th abbr=\"".htmlentities($d)."\" scope=\"col\" title=\"".htmlentities($d)."\"><span>".htmlentities($dayNameLength < 4 ? substr($d,0,$dayNameLength) : $d)."</span></th>\n";
      $calendar .= "\t</tr>\n\t</thead>\n\t<tbody>\n\t<tr>\n\t";
    }
    
    if($weekday > 0)
      $calendar .= "\t\t<td colspan=\"".$weekday."\" class=\"pad\">&nbsp;</td>"; //initial \"empty\" days
    for($day=1,$daysInMonth=gmdate('t',$firstOfMonth); $day<=$daysInMonth; $day++,$weekday++){
      if($weekday === 7){
    	  $weekday  = 0; //start a new week
    	  $calendar .= "\n\t</tr>\n\t<tr>\n\t\t";
      }

      $dayID = ( $day == date('j') && $month == date('m') && $year == date('Y') ) ? " class=\"todaySmall\"" : "";
      $eventListing = '';
      $PopupEvents = '';
      $dayHeadClass = 'dayHead';
      $dayLink = $day;
      $eventp = 0;
      $prependlisting = "";
      $appendlisting = "";      
  
      if(array_key_exists($day, $calendarData)) {
        foreach($calendarData[$day] as $elisting){
          $evTitle = ( (strlen($elisting->title) > 20 ) ? substr($elisting->title, 0, 18)."&hellip;" : $elisting->title  );
          $evTitleFull = $elisting->title;
          
          if($admin == 'true'){
            $eventListing .= "<span class='events-calendar-".$day."-".$elisting->id."Small'><a class='event_title' href='". get_settings('siteurl') ."/wp-admin/admin.php?page=".EWM_EM_PLUGIN_BASE."/events.php&amp;eventid=".$elisting->id."' title='".$evTitleFull."'>".$evTitle."</a></span>";
          } else {
            if($elisting->active == 1){
              $eventp++;
              $eventListing .= "<span class='events-calendar-".$day."-".$elisting->id."Small'><a class='event_title' href='".$blogUrl."/calendar/".$elisting->id."' title='".$evTitleFull."'>".$evTitle."</a></span>";
              $PopupEvents .= "<span>".$evTitleFull."</span>";
            }
          }
        }
        $dayHeadClass = 'dayHead eventthisday';
        if($eventp > 1){
          $dayLink = "<a href=\"/calendar/list/$year/$month/$day/\">$day</a>";
        }else{
          $dayLink = "<a href='".$blogUrl."/calendar/id/".$elisting->id."'>".$day."</a>";
        }
      }
      if( strlen($PopupEvents) > 0 ){
        $prependlisting = "<strong>";
        $appendlisting = "</strong>";
      }
      $calendar .= "<td".$dayID."><div title=\"".$prependlisting.$monthName." ".$day.$appendlisting.$PopupEvents."\" class=\"".$dayHeadClass."\">$dayLink</div><div class=\"events-calendar-".$day."Small\">".$eventListing."</div></td>";
    }
    if($weekday != 7) $calendar .= "\n\t\t<td colspan=\"".(7-$weekday)."\" class=\"pad\">&nbsp;</td>"; //remaining "empty" days
    
    $calendar .= "\t</tr>\n\t</tbody>\n\t</table></div> \n\t\n\t";

    $jsparams = array();
    $jsparams['month'] = $month;
    $jsparams['year'] = $year;
    $jsparams['admin'] = $admin;
    
    $calendar .= $this->show_small_calendar_js($jsparams);
  
    if($ajax == false){
      $calendar .= "</div>"; 
    }
    
    $outputData = array();
    
    $outputData['the_content'] = $calendar;
    $outputData['the_title'] = $this->format_content_title($r);
    $outputData['categoryListingCount'] = $this->categoryListingCount;
    
    return $outputData;
  }

  function show_small_calendar_js($params = ''){
    
    $defaults = array(
      'month' => date("n"), 'year' => date("Y"),
      'admin' => false
    );

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );
      
    $previousMonth = date('n', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth = date('n', mktime(0, 0, 0, $month+1, 1, $year));
  
    $previousMonth3 = date('M', mktime(0, 0, 0, $month-1, 1, $year));
    $nextMonth3 = date('M', mktime(0, 0, 0, $month+1, 1, $year));
    
    $previousYear = date('Y', mktime(0, 0, 0, $month-1, 1, $year));
    $nextYear = date('Y', mktime(0, 0, 0, $month+1, 1, $year));
  
    $bloginfourl = get_bloginfo('url');
  
    $calendarjs = "\n";
  
    $calendarjs .= "<script type='text/javascript'>\n";
    $calendarjs .= "//<![CDATA[\n\n";
    
      //$calendarjs .= 'jQuery(function($) {'."\n";
      //$calendarjs .= '$(document).ready(function() {'."\n\t";
      
        $calendarjs .= '$(".EC_previousMonthSmall").append("&laquo;");'."\n\t";
        $calendarjs .= '$(".EC_nextMonthSmall").prepend("&raquo;");'."\n\t";
    
        $calendarjs .= '$(".EC_previousMonthSmall").mouseover(function() {'."\n\t\t";
          $calendarjs .= '$(this).css("cursor", "pointer");'."\n\t";
        $calendarjs .= '});'."\n\t";
        
        $calendarjs .= '$(".EC_nextMonthSmall").mouseover(function() {'."\n\t\t";
          $calendarjs .= '$(this).css("cursor", "pointer");'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$(".EC_previousMonthSmall").click(function() {'."\n\t\t";
          $calendarjs .= '$(".calendar_wrapSmall .month_title").empty();'."\n\t\t";
          $calendarjs .= '$(".calendar_wrapSmall .month_title").append("<img src=\"'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/loading.gif\" style=\"padding-top:7px;\" alt=\"Loading\" />");'."\n\t\t";
          $calendarjs .= '$.get("'.$bloginfourl.'",'."\n\t\t";
          $calendarjs .= '{EC_action: "switchMonth", EC_month: '.$previousMonth.', EC_year: '.$previousYear.', ajax: false, EC_type: "smallcalendar", isadminpage: '.$admin.'},'."\n\t\t";
          $calendarjs .= 'function(data) {'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapSmall").empty();'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapSmall").html(data);'."\n\t\t";
            $calendarjs .= '$(".eventthisday").tTips();'."\n\t\t";
          $calendarjs .= '});'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$(".EC_nextMonthSmall").click(function() {'."\n\t\t";
          $calendarjs .= '$(".calendar_wrapSmall .month_title").empty();'."\n\t\t";
          $calendarjs .= '$(".calendar_wrapSmall .month_title").append("<img src=\"'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/loading.gif\" style=\"padding-top:7px;\" alt=\"Loading\" />");'."\n\t\t";
          $calendarjs .= '$.get("'.$bloginfourl.'",'."\n\t\t";
          $calendarjs .= '{EC_action: "switchMonth", EC_month: '.$nextMonth.', EC_year: '.$nextYear.', ajax: false, EC_type: "smallcalendar", isadminpage: '.$admin.'},'."\n\t\t";
          $calendarjs .= 'function(data) {'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapSmall").empty();'."\n\t\t\t";
            $calendarjs .= '$(".calendar_wrapSmall").html(data);'."\n\t\t";
            $calendarjs .= '$(".eventthisday").tTips();'."\n\t\t";
          $calendarjs .= '});'."\n\t";
        $calendarjs .= '});'."\n\t";
    
        $calendarjs .= '$.preloadImages = function() {'."\n\t\t";
          $calendarjs .= 'for(var i = 0; i<arguments.length; i++){'."\n\t\t\t";
            $calendarjs .= '$("<img>").attr("src", arguments[i]);'."\n\t\t";
          $calendarjs .= '}'."\n\t";
        $calendarjs .= '}'."\n\t";
        $calendarjs .= '$.preloadImages("'.$bloginfourl.'/wp-content/plugins/ewm-eventmanager/includes/images/loading.gif");'."\n";
    
      //$calendarjs .= "});"."\n";
      //$calendarjs .= "});"."\n\n";
    
    $calendarjs .= "//]]></script>";
    $calendarjs .= ""."\n";
  
    return $calendarjs;
    ?>  
  <?php
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Event List View
  ////////////////////////////////////////////////////////////////////////////////

  function show_event_list($params = '') {

    $defaults = array(
      'month' => false, 'year' => false, 'week' => false, 'day' => false,
      'start' => false, 'end' => false,
      'limit' => 10
    );

    //print_r($params);

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );

    // grab the specific resultset    
    $eventlist = $this->get_events($r);
    
    //print_r($eventlist);
    
    if($eventlist){
      foreach($eventlist as $event){

        if ($event['endtime']) {
          if (date('m/d/Y', strtotime($event['starttime'])) == date('m/d/Y', strtotime($event['endtime']))) {
            $theTime = date('m/d/Y @ g:ia', strtotime($event['starttime'])) . ' - ' . date('g:ia', strtotime($event['endtime']));
          } else {
            $theTime = date('m/d/Y @ g:ia', strtotime($event['starttime'])) . ' to ' . date('m/d/Y @ g:ia', strtotime($event['endtime']));
          }
        } else {
          $theTime = date('m/d/Y @ g:ia', strtotime($event['starttime']));
        }
      
      $events .= "<h3 class=\"eventlist\"><a href=\"/calendar/id/".$event['id']."/\" >".date('M j, Y', strtotime($event['starttime']))." - ".$event['title']."</a></h3>";
      $events .= "<div class=\"date\">".$theTime."</div>";
      $events .= "<div id=\"evententry\" class=\"entry\">".$event['description']."... <a href=\"/calendar/id/".$event['id']."/\">Learn More &raquo;</a></div>";
      //$events .= "<p class=\"postmetadata\"><a href=\"/calendar/id/".$event['id']."/\">Read More About This Event &raquo;</a></p>";
      
        foreach ($event as $key => $value){
          //$events .= $key . " : " . $value . "<br/>";
        }
      }
    }else{
      //return false;
    }
    
    $outputData = array();
    $outputData['the_content'] = $events;
    $outputData['the_title'] = $this->format_content_title($r);
    return $outputData;
  }  

  ////////////////////////////////////////////////////////////////////////////////
  // Event Datasets from DB
  ////////////////////////////////////////////////////////////////////////////////

  function get_event_by_id($id){
    global $wpdb;
    
    $query = "SELECT *
              FROM wp_events
              WHERE id = ".$id."
              AND active = '1'
              ";   
    
    $event = $wpdb->get_row($query, ARRAY_A);
    
    if( is_array($event) ) {
      return $event;    
    } else{
      return false;
    }
    
  }

  function get_events($params = ''){
    global $wpdb;

    $defaults = array(
      'month' => false, 'year' => false, 'week' => false, 'day' => false,
      'start' => false, 'end' => false,
      'limit' => 10,
      'include_inactive' => false
    );

    $r = wp_parse_args( $params, $defaults );
    extract( $r, EXTR_SKIP );

    if($day) {
      $datetime = "AND ( DATE( starttime ) >= FROM_UNIXTIME(". mktime(0,0,0,$month,$day,$year) .") AND DATE( starttime ) < FROM_UNIXTIME(". mktime(0,0,0,$month,$day+1,$year) .") )";
      $datetime .= "OR ( DATE( endtime ) >= FROM_UNIXTIME(". mktime(0,0,0,$month,$day,$year) .") AND DATE( endtime ) < FROM_UNIXTIME(". mktime(0,0,0,$month,$day+1,$year) .") )";
      $datetime .= "OR ( DATE( endtime ) >= FROM_UNIXTIME(". mktime(0,0,0,$month,$day,$year) .") AND DATE( starttime ) < FROM_UNIXTIME(". mktime(0,0,0,$month,$day+1,$year) .") )";
      //$datetime = "AND `starttime` < '$sqldate1' AND `endtime` >= '$sqldate2' ORDER BY `starttime`, `endtime`;";
    } elseif ($week) { //weekview
      $weekinfo = $this->format_week_interval($year, $month, $week);
      $datetime = "AND DATE( starttime ) >= FROM_UNIXTIME(".$weekinfo['start'].") AND DATE( starttime ) < FROM_UNIXTIME(".$weekinfo['end'].") ";  
    } elseif($month) { //monthview
      $monthend = $month+1;
      $datetime = "AND DATE( starttime ) >= FROM_UNIXTIME(". mktime(0,0,0,$month,01,$year) .") AND DATE( starttime ) < FROM_UNIXTIME(". mktime(0,0,0,$month+1,01,$year) .") ";
    } elseif ($year){ // yearview
      $datetime = "AND DATE( starttime ) >= FROM_UNIXTIME(". mktime(0,0,0,01,01,$year) .") AND DATE( starttime ) < FROM_UNIXTIME(". mktime(0,0,0,01,01,$year+1) .") ";
    } else {
      // if no year/month/week/day, it defaults to current date (y/m/d) as the starting point, and shows future events
      $datetime = "AND DATE( starttime ) >= FROM_UNIXTIME(". mktime(0,0,0,date("m"),date("d"),date("Y")) .") ";
    }

    if($limit)
      $limit = "LIMIT 0," . $limit;
    else
      $limit = "";

    $query = "SELECT *
              FROM wp_events
              WHERE blog_id = '0'
              ".$datetime."
              AND active = '1'
              ORDER BY starttime ASC
              ".$limit." ";
    
    //echo $query;
    
    // get resultset for this blog (if no multi, defaults to same blog) 
    $events = $wpdb->get_results($query, ARRAY_A);

    if( is_array($events) ) {
      return $events;    
    } else{
      return false;
    }
  }

  function get_day_events($y, $m, $d) {
    global $wpdb;
    
    $sqldate1 = date('Y-m-d', mktime(0, 0, 0, $m, $d, $y) + 86400);
    $sqldate2 = date('Y-m-d', mktime(0, 0, 0, $m, $d, $y));

    $sql = "SELECT * FROM `wp_events` WHERE `starttime` < '$sqldate1' AND `endtime` >= '$sqldate2' ORDER BY `starttime`, `endtime`;";

    $daysevents = $wpdb->get_results($sql);

    //echo($this->visiblecategory);

    if( !(empty($daysevents)) ){
      //print_r($daysevents);
      if($this->visiblecategory){
        // get the all event categories 
        $event_ids = $wpdb->get_results("SELECT * FROM wp_events_relationships WHERE category_id = '".$this->visiblecategory."' ", ARRAY_A);
        //print_r($event_ids);

        if( $event_ids ){
          foreach($event_ids as $key => $value){
            $event_id_array[] = $value['event_id'];
          }
        } else {
          $event_id_array = false;
        }
        
        //print_r($event_id_array);
        $returnedevents = array();
        
        if( !(empty($event_id_array)) ){
          for($i = 0; $i < count($daysevents); $i++){
            //echo $daysevents[$i]->id;
            if( in_array($daysevents[$i]->id, $event_id_array) ){
              $returnedevents[] = $daysevents[$i];
              $this->categoryListingCount++;
            }
          }
          return $returnedevents;
        } else {
          return $wpdb->get_results($sql);
        }
      }    
    } else {
      return $wpdb->get_results($sql);
    }
    
    return $wpdb->get_results($sql);  
    //return $returnedevents;
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Formatting Helper Functions (Admin)
  ////////////////////////////////////////////////////////////////////////////////

  //
  function format_calendar_data_month($m, $y) {
    global $current_user;

    $lastDay = date('t', mktime(0, 0, 0, $m, 1, $y));
    $datearray = array();
  
    for($d = 1; $d <= $lastDay; $d++):
    foreach($this->get_day_events($y, $m, $d) as $e) :
      
      $datearray[$d][] = $e;
      
      $output = '';
      $id = "$d-$e->id";
      $title = $e->title;
      $description = $e->eventDescription;
      $location = isset($e->eventLocation) && !empty($e->eventLocation) ? $e->eventLocation : '';
      list($ec_startyear, $ec_startmonth, $ec_startday) = explode("-", $e->starttime);
        if(!is_null($event->starttime) && !empty($e->starttime)) {
          list($ec_starthour, $ec_startminute, $ec_startsecond) = explode(":", $e->eventStartTime);
          $startTime = date('g:i a', mktime($ec_starthour, $ec_startminute, $ec_startsecond, $ec_startmonth, $ec_startday, $ec_startyear));
        }
        else 
          $startTime = null;
        $startDate = date('n/j/Y', mktime($ec_starthour, $ec_startminute, $ec_startsecond, $ec_startmonth, $ec_startday, $ec_startyear));
        list($ec_endyear, $ec_endmonth, $ec_endday) = split("-", $e->endtime);
        if($event->endtime != null && !empty($e->endtime)) {
          list($ec_endhour, $ec_endminute, $ec_endsecond) = split(":", $e->eventEndTime);
          $endTime = date('g:i a', mktime($ec_endhour, $ec_endminute, $ec_endsecond, $ec_endmonth, $ec_endday, $ec_endyear));
        }
        else
          $endTime = null;
        $endDate = date('n/j/Y', mktime($ec_endhour, $ec_endminute, $ec_endsecond, $ec_endmonth, $ec_endday, $ec_endyear));
      $accessLevel = $e->accessLevel;
    endforeach;
    endfor;
    return $datearray;
  }

  // return/echo the mysql datetime into a date accornding to format (string)
  function format_datetime($format, $mysql_datetime, $returnit = false){
    $unix=strtotime($mysql_datetime);
    $date="&nbsp;";
    if($unix>0){
      $date=date($format, $unix);
    }
    if($returnit){
      return $date;    
    }
    echo $date;
  }

  // returns the page title in it's formatted form
  function format_content_title($params){
    extract( $params, EXTR_SKIP );

    $title = '';
    if($month)
      $title .= date('F', mktime(0, 0, 0, $month, 1, $year))." ";
    if($day)
      $title .= date('j', mktime(0, 0, 0, $month, $day, $year)).", ";
    $title .= date('Y', mktime(0, 0, 0, 1, 1, $year));

    switch($this->viewType){
      case "calendar":
        return "Calendar for " . $title;
        break;
      case "eventlist":
        return "Events for " . $title;
        break;
      case "smallcalendar":
        if( ($this->visiblecategory) && ($this->categoryListingCount > 0) ){
          $catname = get_cat_name($this->visiblecategory);
          return $catname." Events"; 
        } else {
          return "Upcoming Events";        
        }
        break;
    }
  }

  // returns a sanitized vertion of the title (string)
  function format_title_to_slug($string){
    $unPretty = array('/ä/', '/ö/', '/ü/', '/Ä/', '/Ö/', '/Ü/', '/ß/', '/\s?-\s?/', '/\s?_\s?/', '/\s?\/\s?/', '/\s?\\\s?/', '/\s/', '/"/', '/\'/');
    $pretty   = array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '-', '-', '-', '-', '-', '', '');
    return strtolower(preg_replace($unPretty, $pretty, $string));
  }

  function format_week_interval($year, $month, $week_no){
    $week_info = array();
    if(date('D',strtotime("$month 1, $year")) == 'Sun')
      $sunday_offset = 1;
    else
      $sunday_offset = 0;

    $offset = 1 + (($week_no-1) * 7);
    $week_info['end'] = strtotime("next Sunday +" . ($sunday_offset) . "week", mktime(0,0,0,$month,$offset,$year));
    $week_info['start'] = strtotime("last Sunday -" . ($sunday_offset) . "week", mktime(0,0,0,$month,$offset,$year));
    return $week_info; 
  }
  
  // changes the edit page link to edit events if on a calendar page
  /*
  function editEventLink($link, $eventId) {
    
  }
*/
}
