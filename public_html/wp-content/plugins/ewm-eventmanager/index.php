<?php
/*
Plugin Name: Event Manager
Plugin URI: http://elementwebmedia.com/eventmanager/
Description: A Comprehensive Calendar/Event Manager
Author: Elementwebmedia
Version: 0.9
Author URI: http://www.elementwebmedia.com/
*/

////////////////////////////////////////////////////////////////////////////////
///////////////////////             Definitions             ////////////////////
////////////////////////////////////////////////////////////////////////////////

// Plugin
define("EWM_EM_PLUGIN_BASE", dirname(plugin_basename(__FILE__)));
define("EWM_EM_PLUGIN_DIR", get_settings('siteurl') . '/wp-content/plugins/' . dirname( plugin_basename(__FILE__) ) );
define("EWM_EM_PLUGIN_PAGE", $_SERVER['REQUEST_URI']);

    $currentFile = $_SERVER["REQUEST_URI"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1]; 

define("EWM_EM_PLUGIN_FILE", $currentFile);

// Include Directories
define("EWM_EM_PLUGIN_INCLUDES_DIR", EWM_EM_PLUGIN_DIR . '/includes');
define("EWM_EM_PLUGIN_CSS_DIR", EWM_EM_PLUGIN_INCLUDES_DIR . '/css');
define("EWM_EM_PLUGIN_JS_DIR", EWM_EM_PLUGIN_INCLUDES_DIR . '/js');
define("EWM_EM_PLUGIN_IMAGES_DIR", EWM_EM_PLUGIN_INCLUDES_DIR . '/images'); 
define("EWM_EM_PLUGIN_CLASSES_DIR", EWM_EM_PLUGIN_DIR . '/classes');

require_once("classes/templatefunctions.php");

////////////////////////////////////////////////////////////////////////////////
///////////////////////           Initiate Classes          ////////////////////
////////////////////////////////////////////////////////////////////////////////

require("classes/eventmanager.class.php");
global $myEvents;
$myEvents = &New ewm_EventManager;

require("classes/eventdisplay.class.php");
global $eventDisplay;
$eventDisplay = &New ewm_EventDisplay;

////////////////////////////////////////////////////////////////////////////////
///////////////////////               Actions               ////////////////////
////////////////////////////////////////////////////////////////////////////////

// Hook In Admin Pages and Styles
add_action( 'admin_menu', 'ewm_em_pages' );

// Hook in Shortcode API
add_shortcode('eventmanager', array(&$eventDisplay, 'ewm_em_shorttag' ));

// Hook in Rewrite rules
add_filter('rewrite_rules_array', array(&$eventDisplay, 'insert_rewrite_rules'));
add_filter('query_vars', array(&$eventDisplay, 'insert_rewrite_query_vars'));
add_action('parse_query', array(&$eventDisplay, 'parse_rewrite_rules_query'));


////////////////////////////////////////////////////////////////////////////////
///////////////////////             Admin Pages             ////////////////////
////////////////////////////////////////////////////////////////////////////////

function ewm_em_pages() {
	global $myEvents;

	if ( function_exists('add_menu_page') && function_exists('add_submenu_page')){  
    // top level page
    $ewm_em_mainPage = add_menu_page('Event Manager', 'Events', 7, 'ewm-eventmanager/index.php', 'ewm_em_main_page');
    // sub menu pages
    $ewm_em_eventPage = add_submenu_page(__FILE__, 'Edit Events', 'Add Events', 7, 'ewm-eventmanager/events.php', 'ewm_em_event_page');
    // add js/css includes for each page
    add_action( "admin_print_scripts-$ewm_em_mainPage", array(&$myEvents, 'insert_includes' ), 500 );
    add_action( "admin_print_scripts-$ewm_em_eventPage", array(&$myEvents, 'insert_includes' ), 500 );
  }

}

function ewm_em_main_page() {
	global $myEvents;
	$myEvents->display_main_page();
}

function ewm_em_event_page(){
	global $myEvents;
	$myEvents->display_event_page();
}

////////////////////////////////////////////////////////////////////////////////
///////////////////////           Debugging Hooks           ////////////////////
////////////////////////////////////////////////////////////////////////////////

add_action('init','test_flush_rules');

function test_flush_rules(){
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
    //print_r($wp_rewrite);
}



?>