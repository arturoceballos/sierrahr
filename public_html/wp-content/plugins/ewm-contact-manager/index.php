<?php
/*
Plugin Name: EWM Contact Manager 2
Plugin URI: http://elementwebmedia.com/ewm-contact-manager-2
Description: Managing an organizations contact info and staff
Version: 2.0-beta1
Author: Justin Gable
Author URI: http://justingable.com

Notes:

# needs to have 2 shortcodes working [contactform] and [contactpage] currently 
only [contactform] works

# add arguments to the shortcodes for:
  - subject
  - "to" email address

*/

////////////////////////////////////////////////////////////////////////////////
///////////////////////             Definitions             ////////////////////
////////////////////////////////////////////////////////////////////////////////

define('EWM_CM_PLUGIN_BASE', dirname(plugin_basename(__FILE__)));
define('EWM_CM_PLUGIN_DIR', get_settings('siteurl') . '/wp-content/plugins/' . dirname( plugin_basename(__FILE__) ) );
define('EWM_CM_PLUGIN_PAGE', $_SERVER['REQUEST_URI']);
define('EWM_CM_PLUGIN_PATH', $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/'. dirname( plugin_basename(__FILE__) ) );


////////////////////////////////////////////////////////////////////////////////
///////////////////////           Initiate Classes          ////////////////////
////////////////////////////////////////////////////////////////////////////////

include(EWM_CM_PLUGIN_PATH.'/classes/ewmContactManager.php');
global $ewmCM;
$ewmCM = &new ewmContactManager();

register_activation_hook(__FILE__, array(&$ewmCM, 'activate'));
register_deactivation_hook(__FILE__, array(&$ewmCM, 'deactivate'));


?>