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

<?php global $myEvents; ?>



<?php get_header(); ?>


	<div id="content" class="whoweare">

    <div id="whoweare_content">

  		  		<div class="post" id="post-2">

  		  <h1>Who We Are</h1>

  			<div class="entry">

  				<h5 style="text-align: center;">Human Resource Consulting &amp; Outsourcing Services</h5>
<p>At Sierra HR Partners, we understand the challenges faced by business owners and employers in California. Hiring, managing and retaining employees; creating an enthusiastic and unified workforce; and complying with the myriad state and federal employment laws is overwhelming.</p>
<p>We can help, relieving you of the worrisome, time-consuming responsibilities of HR administration, so you can focus on growing and building your business.</p>
<p>Contact us to discuss how our human resource (&#8221;HR&#8221;) consulting and outsourcing services can benefit your business.</p>
<address><strong>Sierra HR Partners, Inc.</strong><br />
7447 N. First Street, Suite 103<br />
Fresno, CA 93720<br />
Tel: 559.431.8090<br />
FAX: 559.437.0500</address>


          <a href="/contact-us/" class="email_link">&laquo; Click Here to Send Us an Email &raquo;</a>

          <form id="rsvpform" method="get" action="/rsvp-form/">

            <div>

              <h5>RSVP to an upcoming Event</h5>

<select name="rsvpid" id="rsvpselect">
<option>--</option>
<?php 
$objEC_db = new EC_db;
$aryEvents = $objEC_db->getUpcomingEvents(30);
foreach ($aryEvents as $event) {
echo "<option>" . $event->eventTitle . " - " . $event->eventStartDate . "</option>";
}
?>
</select>
             

<br />
</div>
</form>
</div>
</div>
</div>
</div>	


<?php get_sidebar(); ?>



<?php get_footer(); ?>