<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

  <style type="text/css" media="screen">

  </style>

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/includes/common.js"></script>

<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> Atom Feed" href="<?php bloginfo('atom_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php wp_head(); ?>

<script type="text/javascript">

$(document).ready(function() {

$('#rsvpselect').change(function() {

if ($('#rsvpselect option:selected').text() != '--') {

document.forms['rsvpform'].submit();

}
});

$('#cf_field_7').val((window.location.search).replace('?rsvpid=', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' ').replace('+', ' '));

$('.page-item-194').remove();

	$("#map").googleMap(36.840405, -119.781801, 15, {
		controls: ["GSmallMapControl"],
		markers: $(".geo")
	});
$('.Collapsible .Contents').hide();
$('.Collapsible .Toggle').addClass('Closed');
$('.Collapsible .Toggle').click(function(event)
{
event.preventDefault();
$(this).siblings('.Contents').slideToggle();
$(this).toggleClass('Closed');
return false;
});



});

//]]>
</script>

</head>
<body>
<div id="con"><div id="con_pattern">
  <div id="hdr">
  	<h3><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h3>
  	<div class="site_descr"><?php bloginfo('description'); ?></div>
  </div>