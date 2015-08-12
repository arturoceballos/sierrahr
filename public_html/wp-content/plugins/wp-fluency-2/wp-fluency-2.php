<?php
/*
Plugin Name: Fluency Admin
Plugin URI: http://deanjrobinson.com/projects/fluency-admin/
Description: <strong>WordPress 2.7+ only.</strong> The all-new version of the popular Fluency Admin plugin, which builds upon the much improved default WP2.7 admin interface.
Author: Dean Robinson
Version: 2.0
Author URI: http://deanjrobinson.com/
*/ 

/* Main function call */
function wp_admin_fluency_css() {
	wp_admin_fluency_add_css('wp-admin.css');
	wp_admin_fluency_add_js();
}
add_action('admin_head', 'wp_admin_fluency_css',1000);

function wp_login_fluency_css($version = '2.0') {
	$fluency_path = get_settings('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__)) ;
	echo '<link rel="stylesheet" type="text/css" href="' . $fluency_path . '/resources/wp-login.css?version=' . $version .'" />'."\n";
}
add_action('login_head', 'wp_login_fluency_css',1000);

/* Echo CSS file link */
function wp_admin_fluency_add_css($file, $version = '2.0') {
	$fluency_path = get_settings('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__)) ;
	echo '<link rel="stylesheet" type="text/css" href="' . $fluency_path . '/resources/' . $file . '?version=' . $version .'" />'."\n";
}

function wp_admin_fluency_add_js($version = '2.0'){
	$fluency_path = get_settings('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__)) ;
	echo '<script src="' . $fluency_path . '/resources/fluency.js?version=' . $version .'" type="text/javascript" charset="utf-8"></script>';
	
}

function wp_fluency_footer(){
	echo "<span id='fluency-footer'><a href='http://deanjrobinson.com/projects/fluency-admin/'>Fluency Admin 2</a> is a plugin by <a href='http://deanjrobinson.com'>Dean Robinson</a></span><br/>";
}
add_action('in_admin_footer', 'wp_fluency_footer',1000);

?>