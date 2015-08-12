<?php
/*
Plugin Name: Page Link Manager
Version: 0.3
Description: Adds admin panel to choose which pages appear in the site navigation.
Author: Garrett Murphey
Author URI: http://gmurphey.com/
Plugin URI: http://gmurphey.com/2006/10/05/wordpress-plugin-page-link-manager/
*/

// GLOBAL VARIABLES //
define('GDM_MARGIN', '&mdash;&nbsp;&nbsp;');

// DEPRECATED FUNCTIONALITY //
if (!get_option('gdm_excluded_pages'))
	gdm_page_links_activate();

function gdm_wswwpx_fold_page_list($query = '', $fullunfold = false) {
	wswwpx_fold_page_list($query, $fullunfold);
}

function gdm_wp_pages_nav($query = '', $fullunfold = false) {
	wp_pages_nav($query, $fullunfold);
}
// END DEPRECATED FUNCTIONALITY //

// CORE FUNCTIONALITY //
function gdm_list_selected_pages($query = '') {
	wp_list_pages($query);
}
// END CORE FUNCTIONALITY //

// ADMIN SCREENS //
function gdm_page_links_management_form() {
	$excludedPages = get_option('gdm_excluded_pages');
	?>
    <script type="text/javascript">
	function gdmCheckChildren(obj) {
		var parentID = obj.value;
		var children = $A($$('#children-' + parentID + ' input[type=checkbox]'));
		if (obj.checked == false) {
			children.each(function (c) {
				c.checked = false;
				c.disabled = true;
			});
		} else {
			children.each(function (c) {
				c.disabled = false;						
			});
		}
	}
	
	function gdmInit() {
		var pages = $A($$('input[type=checkbox]'));
		pages.each(function (p) {
			var parentID = p.value;
			var children = $A($$('#children-' + parentID + ' input[type=checkbox]'));
			if (p.checked == false) {
				children.each(function (c) {
					c.checked = false;
					c.disabled = true;
				});
			}
		});
	}
	
	Event.observe(window, 'load', function (e) { gdmInit(); });
	</script>
	<div class="wrap">
	<h2><?php _e('Manage Page Links'); ?></h2>
	<fieldset class="options">
	<legend><?php _e('Select the Pages to Include in Site Navigation'); ?></legend>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
		<?php
		gdm_print_pages(0, '', $excludedPages);
		?>
		<input type="submit" name="gdm_submit" value="<?php _e('Update Navigation'); ?>" />
	</form>
	</fieldset>
	</div>
	<?php
}

function gdm_page_links_page_edit_form() {
	global $post_ID;
	$excludedPages = gdm_get_excluded_pages();
	?><p><input type="checkbox" name="gdmIncludePage"<?php if (!empty($post_ID)) { if (!in_array($post_ID, $excludedPages)) { ?>checked<?php } } else { ?>checked<?php } ?> /> <?php _e('Include Page in Site Navigation'); ?></p><?php
}
// END ADMIN SCREENS //

// ADMIN FUNCTIONALITY //
function gdm_page_links_page_edit_submit($id) {
	$excludedPages = get_option('gdm_excluded_pages');
	if (($_POST['post_type'] == 'page') && ($_POST['gdmIncludePage'] != 'on')) {
		$excludedPages[] = $id;
		update_option('gdm_excluded_pages', $excludedPages);
	} elseif (($_POST['post_type'] == 'page') && ($_POST['gdmIncludePage'] == 'on')) {
		if (in_array($id, $excludedPages)) {
			update_option('gdm_excluded_pages', array_diff($excludedPages, array($id)));
		}
	}
}

function gdm_page_links_management() {
	$gdmAllPages = get_all_page_ids();
	if (empty($_POST['gdm_submit'])) {
		gdm_page_links_management_form();
	} else {
		if (is_array($_POST['includedPages']))
			$excludedPages = array_diff($gdmAllPages, $_POST['includedPages']);
		else
			$excludedPages = $gdmAllPages;
		update_option('gdm_excluded_pages', $excludedPages);
		?><div id="message" class="updated fade"><p><strong><?php _e('Page Links Updated'); ?>.</strong></p></div><?php
		gdm_page_links_management_form();
	}
}

function gdm_page_links_page_edit() {
	gdm_page_links_page_edit_form();
}

function gdm_add_admin_pages() {
	add_management_page('Page Links', 'Page Links', 5, __FILE__, 'gdm_page_links_management');
}

function gdm_add_js_libs() {
	wp_enqueue_script('prototype');
}

function gdm_page_links_activate() {
	if (!get_option('gdm_excluded_pages'))
		add_option('gdm_excluded_pages', array());
}

function gdm_page_links_deactivate() {
	delete_option('gdm_excluded_pages');
}
// END ADMIN FUNCTIONALITY //

// HELPER FUNCTIONS //
function gdm_print_pages($parent, $margin = '', $excludedPages) {
	global $wpdb;
	$pages = $wpdb->get_results('SELECT id, post_title FROM ' . $wpdb->prefix . 'posts WHERE post_parent = ' . $parent . ' AND post_type = "page" ORDER BY menu_order ASC', ARRAY_A);
	?><div id="children-<?php echo $parent; ?>"><?php
	for ($x = 0; $pages[$x]; $x++) {
		?><p><?php echo $margin; ?><input type="checkbox" name="includedPages[]" value="<?php echo $pages[$x]['id']; ?>" onchange="gdmCheckChildren(this)"<?php if (!in_array($pages[$x]['id'], $excludedPages)) { ?> checked<?php } ?> /> <?php echo $pages[$x]['post_title']; ?></p><?php
		gdm_print_pages($pages[$x]['id'], $margin . GDM_MARGIN, $excludedPages);
	}
	?></div><?php
}
// END HELPER FUNCTIONS //

// FILTERS //
function gdm_add_excludes($excludes) {
	$excludes = array_merge(gdm_get_excluded_pages(), $excludes);
	sort($excludes);
	return $excludes;
}
// END FILTERS //

// DEVELOPER FUNCTIONS //
function gdm_is_excluded_page($id) {
	return in_array($id, get_option('gdm_excluded_pages'));
}

function gdm_get_excluded_pages() {
	return get_option('gdm_excluded_pages');
}
// END DEVELOPER FUNCTIONS //

// WP HOOKS //
add_filter('wp_list_pages_excludes', 'gdm_add_excludes');
add_action('admin_menu', 'gdm_add_admin_pages');
add_action('admin_print_scripts', 'gdm_add_js_libs');
add_action('edit_page_form', 'gdm_page_links_page_edit');
add_action('edit_post', 'gdm_page_links_page_edit_submit');
add_action('save_post', 'gdm_page_links_page_edit_submit');
add_action('publish_post', 'gdm_page_links_page_edit_submit');
add_action('activate_page_links_manager.php', 'gdm_page_links_activate');
add_action('deactivate_page_links_manager.php', 'gdm_page_links_deactivate');
// END WP HOOKS//
?>