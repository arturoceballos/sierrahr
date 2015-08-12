<?php
/**
 * @package WordPress
 * @subpackage Sierra_HR
 * @subpage Sierra_HR Testpage 
 */
/*
Template Name: Test Page
*/
?>

<?php get_header(); ?>

	<div id="content" class="whoweare">
    <div id="whoweare_content">
  		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  		<div class="post" id="post-<?php the_ID(); ?>">
  		  <h1><?php the_title(); ?></h1>
  			<div class="entry">
  				<?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>

          <a href="/contact-us/" class="email_link">&laquo; Click Here to Send Us an Email &raquo;</a>

  			</div>
  		</div>
  		<?php endwhile; endif; ?>

    </div>

  <div id="featured" class="homesection">
    <?php
    $lastposts = get_posts('numberposts=1&category=3');
    foreach($lastposts as $post) :
      setup_postdata($post);
    ?>

    <div class="featured_content">
    <h3><a href="<?php the_permalink(); ?>" ><?php the_title(); ?></a></h3>
    <?php the_excerpt(); ?>
    <p class="link"><a href="<?php the_permalink(); ?>">Read More...</a></p>
    </div>
    <?php endforeach; ?>
  </div>

	<?php edit_post_link('<br/><br/>Edit this entry.', '<p>', '</p>'); ?>

	</div>




<?php get_sidebar(); ?>



<?php get_footer(); ?>