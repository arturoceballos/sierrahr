<?php
/*
Manages custom event plugin
*/

class ewm_EventManager {

  ////////////////////////////////////////////////////////////////////////////////
  // Vars and Constructor
  ////////////////////////////////////////////////////////////////////////////////

	var $allEvents;
	var $formNotice;
  var $eventId;
  var $eventInfo;
  var $pagePurpose;
  
	function ewm_EventManager() {
    $this->formNotice = false;
    $this->allEvents = false;
    $this->eventInfo = false;
    $this->pagePurpose = 'add';
		$this->eventId = ( !($_REQUEST['eventid']) ? false : $_REQUEST['eventid']  );
		
    // Add a Media Button Icon
    //add_action('media_buttons', array($this, 'insert_media_button'), 20);
    // Reset the tabs
    //add_filter('media_upload_tabs', array($this, 'set_tabs') );
    // Call the ifram content
    //add_action('media_upload_add_event', array($this, 'call_iframe'));

	}

  ////////////////////////////////////////////////////////////////////////////////
  // Media Button & Thickbox Popup
  ////////////////////////////////////////////////////////////////////////////////

  function insert_media_button() {
      global $post_ID, $temp_ID;
      $uploadingId = (int) (0 == $post_ID ? $temp_ID : $post_ID);
      $mediaUploadIframeSrc = "media-upload.php?post_id=$uploadingId";

      // tab references a hook above
      //$media_flickr_iframe_src = apply_filters('media_flickr_iframe_src', "$media_upload_iframe_src&amp;type=flickr&amp;tab=123flickr");
      //$media_flickr_title = __('Add My Flickr photo', 'wp-media-flickr');

      echo "<a href=\"{$mediaUploadIframeSrc}&amp;TB_iframe=true&amp;type=eventmanager&amp;height=500&amp;width=640&amp;tab=add_event\" class=\"thickbox\" title=\"Add an Event/Calendar\"><img src=\"".EWM_EM_PLUGIN_IMAGES_DIR."/eventmanager_media_button.gif\" alt=\"Add an Event or Calendar\" /></a>";
  }

  function set_tabs($tabs){
  	if( current_user_can( 'unfiltered_upload' ) ){
    	//return $tabs;
    	return array(
        'add_event' =>  __('Add an Event', 'add_event')
      );
    }
  }
  
  function call_iframe(){
    wp_iframe(array($this, 'iframe_content'));
  }

  function iframe_content(){
    //global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types;  
  
    // Reset the tabs
    //add_filter('media_upload_tabs', array($this, 'set_tabs') );
    
    //media_upload_header();
    
    echo 'charles';
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Includes
  ////////////////////////////////////////////////////////////////////////////////

  function insert_includes(){
    echo '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/admin-style.css" type="text/css" media="screen" />'."\n";
    echo '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_CSS_DIR.'/calendar-large.css" type="text/css" media="screen" />'."\n";
    echo '<link rel="stylesheet" href="'.EWM_EM_PLUGIN_JS_DIR.'/ui/css/jquery-ui-theme.css" type="text/css" media="screen" />'."\n";

    wp_enqueue_script( 'jquery-ui-core', "/alpha/wp-includes/js/jquery/ui.core.js", array('jquery') );
    wp_enqueue_script( 'jquery-ui-datepicker', EWM_EM_PLUGIN_JS_DIR."/ui/ui.datepicker.js", array('jquery') );
    wp_enqueue_script( 'ewm-eventmanager-backend', EWM_EM_PLUGIN_JS_DIR."/apply.js", array('jquery') );

  }

  ////////////////////////////////////////////////////////////////////////////////
  // Form Handler
  ////////////////////////////////////////////////////////////////////////////////

  function proccess_form($formaction){
    switch ($formaction) {
      case 'addevent':
        $this->proccess_event($formaction);
      break;
      case 'editevent':
        $this->proccess_event($formaction);
      break;
      case 'activate':
      case 'deactivate':
        $this->change_activity($formaction, $_REQUEST['eventid']);
      break;
      case 'delete':
        $this->delete_event($_REQUEST['eventid']);
      break;
    }
  }

  ////////////////////////////////////////////////////////////////////////////////
  // DB Gets
  ////////////////////////////////////////////////////////////////////////////////

  function get_all_events(){
    global $wpdb;
    // get the all event information for this blog (if no multi, defaults to same blog) 
    $events = $wpdb->get_results("SELECT * FROM wp_events WHERE blog_id = '".$wpdb->blogid."' ORDER BY starttime ASC", ARRAY_A);
    // grabs all events
    $this->allEvents = $events;
  }

  function get_event_info($eventid){
    global $wpdb;
    // get the event information    
    $this->eventInfo = $wpdb->get_row("SELECT * FROM wp_events WHERE id = $eventid");
  }

  ////////////////////////////////////////////////////////////////////////////////
  // DB Modification
  ////////////////////////////////////////////////////////////////////////////////

  function proccess_event($action){
    global $wpdb;
    
    $eventid = $_POST['eventid'];
    $blogid = $wpdb->blogid;

    // grab the eventarray
    extract($_POST, EXTR_OVERWRITE);
    
    $newarray = $post_category;
    
    $startyear = trim($_POST['date-start']);
    $endyear = trim($_POST['date-end']);
        
    if ($_POST['date-start-ampm'] == 'pm' && $_POST['date-start-hour'] < 12 ){ $starthour = $_POST['date-start-hour'] + 12; }else{$starthour = $_POST['date-start-hour'];}    
    $starttime = $startyear.'-'.$_POST['date-start-mm'].'-'.$_POST['date-start-dd'].' '.$starthour.':'.$_POST['date-start-min'].':00';
  
    if ($_POST['date-end-ampm'] == 'pm' && $_POST['date-end-hour'] < 12 ){ $endhour = $_POST['date-end-hour'] + 12; }else{$endhour = $_POST['date-end-hour'];}    
    $endtime = $endyear.'-'.$_POST['date-end-mm'].'-'.$_POST['date-end-dd'].' '.$endhour.':'.$_POST['date-end-min'].':00';
  
    if($action == 'addevent'){
      $this->change_event_categories($eventid, $newarray);
      $submitted = $wpdb->query("INSERT INTO wp_events (blog_id, starttime, endtime, address, city, state, zip, admission, title, description, contactname, contactemail, contactphone, active, schedule, rsvpactive)
                    VALUES ('$wpdb->blogid', '$starttime', '$endtime', '$address', '$city', '$state', '$zip', '$admission', '$title', '$description', '$contactname', '$contactemail', '$contactphone', '$active', '$schedule', '$rsvpactive')");
    } else if($action == 'editevent'){
      $this->change_event_categories($eventid, $newarray);
      $submitted = $wpdb->query("
                          UPDATE wp_events 
                          SET starttime='$starttime', endtime='$endtime', address='$address', 
                              city='$city', state='$state', zip='$zip',
                              admission='$admission', title='$title', description='$description',
                              contactname='$contactname', contactemail='$contactemail', contactphone='$contactphone',
                              active='$active', recurring='$recurring', schedule='$schedule', rsvpactive='$rsvpactive' 
                          WHERE blog_id = '$wpdb->blogid' AND id = '$eventid'");
    } else {
      $submitted = FALSE;
    }

    if($submitted){
      if($action == 'addevent'){
        $this->formNotice = 'Your event was successfully added';
        $this->eventId = mysql_insert_id();
      }else{
        $this->formNotice = 'Your event was successfully edited';
      }
    } else {

    }
  }

  function delete_event($eventid){
    global $wpdb;
    // delete the event   
    $submitted = $wpdb->query("DELETE FROM wp_events WHERE id = $eventid");
    
    if($submitted){
      $this->formNotice = 'Your event was successfully deleted';
    } else {
      $this->formNotice = 'Your event was not successfully deleted, please try again';
    }
  }

  function change_activity($activity, $eventid = '0'){
    global $wpdb;

    if($activity == 'activate') {
      $submitted = $wpdb->query("UPDATE wp_events SET active='1' WHERE id = $eventid");
    } else if($activity == 'deactivate'){
      $submitted = $wpdb->query("UPDATE wp_events SET active='0' WHERE id = $eventid");
    }
  
    if($submitted && $activity == 'activate'){
      $this->formNotice = 'Your event was successfully activated';
    } else if($submitted && $activity == 'deactivate'){
        $this->formNotice = 'Your event was successfully deactivated';
    } else {
        $this->formNotice = 'Something went wrong, Please try to edit the event again';
    }
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Add/Edit Event Admin View
  ////////////////////////////////////////////////////////////////////////////////

  function display_event_page() {
    global $wpdb, $eventDisplay;

    // send to Processform if submitted  
    if($_REQUEST['action']){
      $this->proccess_form($_REQUEST['action']);
    }

    // echo notice to display form action results
    if($this->formNotice){
      ?>    
      <div id="message" class="updated fade"><p><strong><?php echo $this->formNotice; ?></strong></p></div>
      <?php 
    }

    if($this->eventId){
      $this->pagePurpose = 'edit';
      $this->get_event_info($this->eventId);
      
      //echo "<pre>";
      //print_r($this->eventInfo);
      //echo "</pre>";
      
      // start times
      $starttimeday = $eventDisplay->format_datetime("d", $this->eventInfo->starttime, true);
      $starttimemonth = $eventDisplay->format_datetime("m", $this->eventInfo->starttime, true);
      $starttimeyear = $eventDisplay->format_datetime("Y", $this->eventInfo->starttime, true);
      $starttimehour = $eventDisplay->format_datetime("H", $this->eventInfo->starttime, true);
      $starttimeampm = $eventDisplay->format_datetime("a", $this->eventInfo->starttime, true);
      $starttimemin = $eventDisplay->format_datetime("i", $this->eventInfo->starttime, true);
      // end times
      $endtimeday = $eventDisplay->format_datetime("d", $this->eventInfo->endtime, true);
      $endtimemonth = $eventDisplay->format_datetime("m", $this->eventInfo->endtime, true);
      $endtimeyear = $eventDisplay->format_datetime("Y", $this->eventInfo->endtime, true);
      $endtimehour = $eventDisplay->format_datetime("H", $this->eventInfo->endtime, true);
      $endtimeampm = $eventDisplay->format_datetime("a", $this->eventInfo->endtime, true);
      $endtimemin = $eventDisplay->format_datetime("i", $this->eventInfo->endtime, true);
    }
    ?>
    
    	<div class="wrap">
      <h2><?php echo ucfirst($this->pagePurpose); ?> an Event</h2>
      
      <form action="<?php echo $_SERVER['SCRIPT_NAME'];?>?page=<?php echo $_GET['page'];?>" method="post">
      <table class="optiontable">
    
      <?php
      if ($this->eventId) {
      ?>
        <input type="hidden" name="action" value="editevent" />   
        <input type="hidden" name="eventid" value="<?php echo $this->eventId; ?>" />
      <?php }else{ ?>
        <input type="hidden" name="action" value="addevent" />
      <?php 
      }
      ?>  
        
        <tr valign="top">
          <th scope="row">Title:</th>
          <td><input type="text" name="title" value="<?php echo $this->eventInfo->title;?>" size="40" /></td>
        </tr>
        <tr valign="top">
              <th scope="row">Event Info:</th>
          <td colspan="1">
            
              <div class="tab-container" id="container1">
                <div class="tab-panes">

    <table class="onetime">
        <tr valign="top">
          <th scope="row">Start:</th>
          <td>
    
            <select id="date-start-mm" name="date-start-mm">
            <?php for($i = 1 ; $i < 13 ; $i++){
              $display_month=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_month ?>" <?php if($starttimemonth == $display_month ){ ?>selected="selected"<?php } ?> ><?php echo date("F", mktime(0, 0, 0, $i, 1, 2000)); ?></option>
            <?php } ?>
            </select>
    
            <select id="date-start-dd" name="date-start-dd">
            <?php for($i = 1 ; $i < 32 ; $i++){
              $display_date=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_date; ?>" <?php if($starttimeday == $display_date){ ?>selected="selected"<?php } ?> ><?php echo date("jS", mktime(0, 0, 0, 1, $i, 2000)); ?></option>
            <?php } ?>
            </select>
    
            <select class="w4em split-date" value="<?php echo $starttimeyear;?>" id="date-start" name="date-start">
            <?php for($i = 2009 ; $i < 2024; $i++){
              $display_years=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_years; ?>" <?php if($starttimeyear == $display_years){ ?>selected="selected"<?php } ?> ><?php echo $i; ?></option>
            <?php } ?>            
            </select>
            <input type="hidden" size="10" id="linkedDates" disabled="disabled"/>

    
            <select id="date-start-hour" name="date-start-hour">
            <?php for($i = 1 ; $i < 13 ; $i++){
              $display_hours=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_hours; ?>" <?php if($starttimehour == $display_hours || $starttimehour == ($display_hours + 12) ){ ?>selected="selected"<?php } ?> ><?php echo $i; ?></option>
            <?php } ?>
            </select>
    
            <select id="date-start-min" name="date-start-min">
            <?php for($i = 0 ; $i < 60 ; $i++){
              $display_mins=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_mins; ?>" <?php if($starttimemin == $display_mins ){ ?>selected="selected"<?php } ?> ><?php echo $display_mins; ?></option>
            <?php } ?>
            </select>
    
            <select id="date-start-ampm" name="date-start-ampm">
              <option value="am" <?php if($starttimeampm == 'am' ){ ?>selected="selected"<?php } ?> >am</option>
              <option value="pm" <?php if($starttimeampm == 'pm' ){ ?>selected="selected"<?php } ?> >pm</option>
            </select>
           
          </td>    
        </tr>
        <tr valign="top">
          <th scope="row">End:</th>
          <td>
          
            <select id="date-end-mm" name="date-end-mm">
            <?php for($i = 1 ; $i < 13 ; $i++){
              $display_month=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_month ?>" <?php if($endtimemonth == $display_month ){ ?>selected="selected"<?php } ?> ><?php echo date("F", mktime(0, 0, 0, $i, 1, 2000)); ?></option>
            <?php } ?>
            </select>
          
            <select id="date-end-dd" name="date-end-dd">
            <?php for($i = 1 ; $i < 32 ; $i++){
              $display_date=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_date; ?>" <?php if($endtimeday == $display_date){ ?>selected="selected"<?php } ?> ><?php echo date("jS", mktime(0, 0, 0, 1, $i, 2000)); ?></option>
            <?php } ?>
            </select>
    
            <select class="w4em split-date" value="<?php echo $endtimeyear;?>" id="date-end" name="date-end">
            <?php for($i = 2009 ; $i < 2024; $i++){
              $display_years=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_years; ?>" <?php if($endtimeyear == $display_years){ ?>selected="selected"<?php } ?> ><?php echo $i; ?></option>
            <?php } ?>            
            </select>

            <input type="hidden" size="10" id="endlinkedDates" disabled="disabled"/>
    
            <select id="date-end-hour" name="date-end-hour">
            <?php for($i = 1 ; $i < 13 ; $i++){
              $display_hours=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_hours; ?>" <?php if($endtimehour == $display_hours || $endtimehour == ($display_hours + 12) ){ ?>selected="selected"<?php } ?> ><?php echo $i; ?></option>
            <?php } ?>
            </select>
    
            <select id="date-end-min" name="date-end-min">
            <?php for($i = 0 ; $i < 60 ; $i++){
              $display_mins=sprintf("%02s", $i); ?>
              <option value="<?php echo $display_mins; ?>" <?php if($endtimemin == $display_mins ){ ?>selected="selected"<?php } ?> ><?php echo $display_mins; ?></option>
            <?php } ?>
            </select>
    
            <select id="date-end-ampm" name="date-end-ampm">
              <option value="am" <?php if($endtimeampm == 'am' ){ ?>selected="selected"<?php } ?> >am</option>
              <option value="pm" <?php if($endtimeampm == 'pm' ){ ?>selected="selected"<?php } ?> >pm</option>
            </select>
           
      </td>    
        </tr>
    </table>
                </div> <!-- panes -->
              </div>

           
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Categories:</th>
          <td>
            <div id="categories-all" class="ui-tabs-panel">
              <ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
              <?php
              $eventcategories = $this->get_event_categories($this->eventId);
              wp_category_checklist(0, false, $eventcategories); ?>
              </ul>
            </div>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Address:</th>
          <td><input type="text" name="address" value="<?php echo $this->eventInfo->address;?>" size="40" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">City:</th>
          <td><input type="text" name="city" value="<?php echo $this->eventInfo->city;?>" size="40" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">State:</th>
          <td><input type="text" name="state" value="<?php echo $this->eventInfo->state;?>" size="2" maxlength="2" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">Zip:</th>
          <td><input type="text" name="zip" value="<?php echo $this->eventInfo->zip;?>" size="6" maxlength="6" /></td>
        </tr>                
        <tr valign="top">
          <th scope="row">Contact Name:</th>
          <td><input type="text" name="contactname" value="<?php echo $this->eventInfo->contactname;?>" size="40" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">Contact Phone:</th>
          <td><input type="text" name="contactphone" value="<?php echo $this->eventInfo->contactphone;?>" size="40" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">Contact Email:</th>
          <td><input type="text" name="contactemail" value="<?php echo $this->eventInfo->contactemail;?>" size="40" /></td>
        </tr>
        <tr valign="top">
          <th scope="row">Description:</th>
          <td><textarea cols="45" rows="8" name="description"><?php echo $this->eventInfo->description;?></textarea><br/><br/></td>
        </tr>
        <tr valign="top">
          <th scope="row">RSVP Active:</th>
          <td><input type="radio" name="rsvpactive" value="1"<?php if ($this->eventInfo->rsvpactive == 1) echo ' checked="checked"';?> /> Yes&nbsp;&nbsp;<input type="radio" name="rsvpactive" value="0"<?php if ($this->eventInfo->rsvpactive == 0) echo ' checked="checked"';?> /> No<br/><br/></td>
        </tr>
        <tr valign="top">
          <th scope="row">All Day Event:</th>
          <td><input type="radio" name="schedule" value="1"<?php if ($this->eventInfo->schedule == 1) echo ' checked="checked"';?> /> Yes&nbsp;&nbsp;<input type="radio" name="schedule" value="0"<?php if ($this->eventInfo->schedule == 0) echo ' checked="checked"';?> /> No<br/><br/></td>
        </tr>
        <tr valign="top">
          <th scope="row">Active:</th>
          <td><input type="radio" name="active" value="1"<?php if ($this->eventInfo->active == 1) echo ' checked="checked"';?> /> Yes&nbsp;&nbsp;<input type="radio" name="active" value="0"<?php if ($this->eventInfo->active == 0) echo ' checked="checked"';?> /> No<br/><br/></td>
        </tr>         
      </table>
      <p class="submit"><input type="submit" name="Submit" value="<?php echo ucfirst($this->pagePurpose); ?> This Event &raquo;" /></p>
      </form>
      </div>
  <?php
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Grab Categories for a post
  ////////////////////////////////////////////////////////////////////////////////

  function get_event_categories($eventid){
    global $wpdb;

    // get the all event categories 
    $event_categories = $wpdb->get_results("SELECT * FROM wp_events_relationships WHERE event_id = '".$eventid."' ", ARRAY_A);

    //$event_categories = $event_categories[0];
    if( $event_categories ){
      foreach($event_categories as $key => $value){
        //echo $value;
        $event_cat_array[] = $value['category_id'];
      }
    } else {
      $event_cat_array = false;
    }

    return $event_cat_array;
  }
  
  function change_event_categories($theeventid, $newarray) {
    global $wpdb;
    // delete all event categories first  
    $submitted = $wpdb->query("DELETE FROM wp_events_relationships WHERE event_id = $theeventid");
    
    //then, add the new array
    
    if( !(empty($newarray)) ){
      foreach($newarray as $key => $value){
        $submitted = $wpdb->query("INSERT INTO wp_events_relationships (event_id, category_id)
          VALUES ('$theeventid', '$value')");
      }
    }

  }

  ////////////////////////////////////////////////////////////////////////////////
  // Main Event Page View
  ////////////////////////////////////////////////////////////////////////////////

  function display_main_page($submitted = FALSE, $eventid = '0') {
    global $wpdb, $eventDisplay;

    // send to Processform if submitted  
    if($_REQUEST['action']){
      $this->proccess_form($_REQUEST['action']);
    }

    // echo notice to display form action results
    if($this->formNotice){
      ?>    
      <div id="message" class="updated fade"><p><strong><?php echo $this->formNotice; ?></strong></p></div>
      <?php 
    }

    // grab the events
    $this->get_all_events();
  
  ?>
  <div class="wrap">
    <h2>Event Manager</h2>

    <?php
    $displayParams['year'] = date("Y");
    $displayParams['month'] = date("n");
    $displayParams['admin'] = true;
    $admincalendar = $eventDisplay->show_large_calendar($displayParams);
    echo $admincalendar['the_content'];
    ?>

    <div class="floatleft" style="float: left; width: 49%">
      <h3>Upcoming Events</h3>
      <?php
      $this->create_admin_list('forward');
      ?>
    </div>
    <div class="floatright" style="float: right; width: 49%">
      <h3>Recent Events</h3>
      <?php
      $this->create_admin_list('backward');
      ?>
    </div>
  <div class="clearboth"></div>
  </div>
  <?php
  }

  ////////////////////////////////////////////////////////////////////////////////
  // Formatted Output (Admin)
  ////////////////////////////////////////////////////////////////////////////////

  function create_admin_list($period = 'forward'){
    global $eventDisplay;

    $events = array();
    $allevents = $this->allEvents;
    $pl = 0;

    switch ($period){
    case 'forward':
    case 'reserve':
    case 'reserve-select':
      for( $i=0; $i<count($allevents); $i++ ){
        if(strtotime($allevents[$i]['starttime']) > time()){      
          $events[] = $allevents[$i];
        }
      }
      break;
    case 'backward': 
      for( $i=0; $i<count($allevents); $i++ ){
        if(strtotime($allevents[$i]['starttime']) < time()){      
          $events[] = $allevents[$i];
        }
      }
      break;
    }
    
    if(count($events) === 0 ){
      echo "No Events to Display";
    }

    // for the badge
    if($period == 'reserve-select'){
      //$events = array_reverse($events);
      $po = 0;
      $selectval = "";
      foreach($events as $thevent){
        if($thevent['rsvpactive'] == '1'){
          $selectval .= '<option value="'.$thevent['id'].'" >'. $eventDisplay->format_datetime("m/d/y g:ia", $thevent['starttime'], true). " " . $thevent['title'] ."</option>";
          $po++;
        }
      }
      if($po > 0){
        return $selectval;
      } else {
        return false;
      }
    }

    if($period == 'reserve'){      
      // for the submission form
      if($_GET['rsvpid']){
        $eventinfo = $this->get_event_info($_GET['rsvpid']);
        $defaultvalue = $eventDisplay->format_datetime("m/d/y g:ia", $this->eventInfo->starttime, true). " " . $this->eventInfo->title;
      } else {
        $defaultvalue = ' ';
      }
      $passval = "Event|$defaultvalue";
      //$events = array_reverse($events);
      $po = 0;
      foreach($events as $thevent){
        //echo $thevent['rsvpactive'];
        if($thevent['rsvpactive'] == '1'){
          $passval .= "#". $eventDisplay->format_datetime("m/d/y g:ia", $thevent['starttime'], true). " " . $thevent['title'] ."|". $eventDisplay->format_datetime("m/d/y g:ia", $thevent['starttime'], true). " " .$thevent['title'];
          $po++;
        }
      }
      if($po > 0){
        return $passval . '$#$selectbox$#$1$#$0$#$0$#$0$#$0';
      } else {
        return false;
      }
    }

    for( $i=0; $i<count($events); $i++ ){
      $datearray = getdate(strtotime($events[$i]['starttime']));
      $month = $datearray['month'];
      $year = $datearray['year'];
      $title = ( (strlen($events[$i]['title']) > 20 ) ? substr($events[$i]['title'], 0, 18)."&hellip;" : $events[$i]['title']  );
      $titlefull = $events[$i]['title'];
      
      if($month != $themonth ){
        if($pl != 0){
          echo "</tbody></table>";
        }
        echo "<h4>".$month." ".$year."</h4>";
        ?>

    <table class="widefat">
    <thead>
      <tr>
        <th scope="col" style="text-align: center;">&nbsp;</th>
        <th scope="col" class="ewm_em_title" >Event</th>
        <th scope="col">Start Time</th>
        <th scope="col">End Time</th>
  	    <th scope="col" colspan="3" style="text-align: center !important;">Actions</th>
      </tr>
    </thead>
    <tbody>
    
      <?php
      }//endif 
      ?>
   
        <tr<?php if (!is_float($i/2)) echo ' class="alternate"';?>>
          <th scope="row" style="text-align: center;"><!-- <?php echo $events[$i]['id'];?> --></th>
          <td class="ewm_em_title"><span title="<?php echo $titlefull;?>"><?php echo $title; ?></span></td>
          <td class="ewm_em_timedisplay"><?php $eventDisplay->format_datetime("m/d/y g:ia", $events[$i]['starttime']); ?></td>
          <td class="ewm_em_timedisplay"><?php $eventDisplay->format_datetime("m/d/y g:ia", $events[$i]['endtime']); ?></td>
          <td class="ewm_em_activity">
            <?php if ($events[$i]['active'] == 0){
            ?>
            <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page'].'&amp;action=activate&amp;eventid='.$events[$i]['id'];?>">Activate</a>
            <?php
                  }else{
            ?>
            <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page'].'&amp;action=deactivate&amp;eventid='.$events[$i]['id'];?>">Deactivate</a>
            <?php } ?>
                    </td>
          <td class="ewm_em_edit_event"><a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page=ewm-eventmanager/events.php&amp;eventid='.$events[$i]['id'];?>" class="edit">Edit</a></td>
          <td class="ewm_em_delete_event"><a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page'].'&amp;action=delete&amp;eventid='.$events[$i]['id'];?>" class="delete" onclick="javascript:return confirm('Are you sure you want to remove this event?')">Delete</a></td>
        </tr>
        
      <?php
  
      if( $i ==(count($events)-1) ){
        echo "</tbody></table>";
      }

      $themonth = $month;
      $pl++;
    }//endfor
  }

} //endclass

?>
