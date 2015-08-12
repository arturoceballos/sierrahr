<?php

class ewmContactManager {

  var $sendScript;
  var $blogid;
  var $orgTable = 'wp_organization';
  var $message;

  function ewmContactManager() {
    global $blog_id;
    
    $this->blogid = $blog_id;
    $this->action = $_POST['action'];
    
    if ($this->action) {
      $this->formHandler($_POST);
    }
    
    if ($_GET['page'] == 'ewm-contact-manager/classes/ewmContactManager.php') {
      add_action('admin_head', array(&$this, 'wysiwygAdmin'));
    }
    
    add_action('wp_head', array(&$this, 'ajaxFormSubmit'));
    add_shortcode('contactform', array(&$this, 'contactFormFunc'));
    add_shortcode('orginfo', array(&$this, 'orgInfoFunc'));        
    add_action('admin_menu', array(&$this, 'adminMenuItems'));

  }
  
////////////////////////////////////////////////////////////////////////////////
// 
////////////////////////////////////////////////////////////////////////////////

  function contactFormFunc($atts) {
    $output = $this->generateContactForm($atts);
    
    return $output;
  }
  
  function orgInfoFunc($atts) {
    $output = $this->generateOrgInfo($atts);
    
    return $output;
  }  
  
  function adminMenuItems() {
    add_menu_page('Contact', 'Contact', 7, __FILE__, array(&$this, 'viewAdminOrganization'));    
  }
  
  function activate() {
    global $wpdb;
    
    $wpdb->query("CREATE TABLE `wp_organization` (
                  `id` INT( 3 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                  `name` VARCHAR( 64 ) NOT NULL ,
                  `address` VARCHAR( 255 ) NOT NULL ,
                  `city` VARCHAR( 64 ) NOT NULL ,
                  `state` VARCHAR( 2 ) NOT NULL ,
                  `zipcode` VARCHAR( 5 ) NOT NULL ,
                  `phone` VARCHAR( 10 ) NOT NULL ,
                  `fax` VARCHAR( 10 ) NOT NULL ,
                  `email` VARCHAR( 128 ) NOT NULL ,
                  `description` TEXT NOT NULL ,
                  `blog_id` INT( 5 ) NOT NULL DEFAULT '1'
                  ) ENGINE = MYISAM ;");
  }
  
  function deactivate() {
    global $wpdb;
    
    $wpdb->query('DROP TABLE wp_organization');
  }

////////////////////////////////////////////////////////////////////////////////
// Form Handler
////////////////////////////////////////////////////////////////////////////////

  function formHandler($post) {
  
    switch($this->action) {
      case 'update-org':

        $this->updateOrgInfo($post);
        $this->action = '';
        break;    
    }
  
  }

////////////////////////////////////////////////////////////////////////////////
// DATA Methods
////////////////////////////////////////////////////////////////////////////////

  function updateOrgInfo($data) {
    global $wpdb;
    
    $blog_id = $this->blogid;
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = ucwords($_POST['city']);
    $state = strtoupper($_POST['state']);
    $zipcode = $_POST['zipcode'];
    $phone = $_POST['phone'];
    $fax = $_POST['fax'];
    $email = $_POST['email'];
    $description = wpautop($_POST['description']);
    
    // Check to see if table entry exists, if not add the entry, if so update it
    if (!$wpdb->get_row("SELECT * FROM wp_organization WHERE blog_id = '$blog_id'")) {
    
      $wpdb->query("INSERT INTO wp_organization (name, address, city, state, zipcode, phone, fax, email, description, blog_id)
                      VALUES ('$name', '$address', '$city', '$state', '$zipcode', '$phone', '$fax', '$email', '$description', '$blog_id');");
    
    } else {    
    
      $wpdb->query("UPDATE wp_organization 
                      SET name='$name', address='$address', city='$city', 
                          state='$state', zipcode='$zipcode', phone='$phone',
                          fax='$fax', email='$email', description='$description'  
                      WHERE blog_id = '$blog_id'");
    
    }
    
    $this->message = '<strong>Successfully Updated</strong>';
        
  }

  function getOrgInfo($blogid) {
    global $wpdb;
  
    $query = 'SELECT * FROM ' . $this->orgTable . ' WHERE blog_id = "'.$blogid.'"';
    $results = $wpdb->get_row($query, ARRAY_A);
    
    return $results;
  }
  
////////////////////////////////////////////////////////////////////////////////
// HELPERS
////////////////////////////////////////////////////////////////////////////////

  function printMessage($msg) {
    echo '<div id="message" class="updated fade"><p>',$msg,'</p></div>';
  }  
  
  function wysiwygAdmin() {
?>
    <script type="text/javascript" src="<?php echo get_bloginfo('url');?>/wp-admin/js/editor.js?ver=20080325"></script>
    <script type='text/javascript'>
    /* <![CDATA[ */
    	wpTinyMCEConfig = {
    		defaultEditor: "tinymce"
    	}
    /* ]]> */
    </script>
    <script type='text/javascript' src='<?php echo get_bloginfo('url');?>/wp-includes/js/tinymce/tiny_mce_config.php?ver=20080327'></script>
<?php  
  }
  
  function ajaxFormSubmit() {
?>
<script type="text/javascript">
$(function(){
  // for ajax contact form
  if ($('#cf').attr('method')) {
    $('#cf').submit(function() {

      var inputs = [];
      $(':input', this).each(function() {
        inputs.push(this.name + '=' + escape(this.value));
        //alert('hi');
      });
      
      jQuery.ajax({
        data: inputs.join('&'),
        url: this.action,
        timeout: 2000,
        
        beforeSend: function() {
          $("#loader").html('<img src="<?php bloginfo('template_url');?>/images/ajax-loader.gif" alt="Sending Message..." />');
          $('input#sender').hide();
          $('.msg').fadeOut('fast');
        },
        success: function(r) {        
          $('input#sender').show();
          $("#loader").replaceWith('');
          $('#txtmsg').empty().append(r);
          $('.msg').css('display', 'block').fadeIn('slow');
          $('.itf:first').attr('value', '');
        }
      });
      
      return false;
    })
  }
});
</script>
<?php 
  
  }
  
////////////////////////////////////////////////////////////////////////////////
// VIEWS - Admin
////////////////////////////////////////////////////////////////////////////////  
  
  function viewAdminOrganization() {
  
  $orgInfo = $this->getOrgInfo($this->blogid);
  if ($this->message) {
    $this->printMessage($this->message);  	
  }

?>
	<div class="wrap">
  <h2>Organization Info</h2>
  
  <form action="<?php echo $_SERVER['SCRIPT_NAME'];?>?page=<?php echo $_GET['page'];?>" method="post">
  
  <table class="form-table">
    <input type="hidden" name="action" value="update-org" />
    <tr valign="top">
      <th scope="row">Organization:</th>
      <td><input type="text" name="name" value="<?php echo $orgInfo['name'];?>" size="40" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">Address:</th>
      <td><input type="text" name="address" value="<?php echo $orgInfo['address'];?>" size="40" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">City:</th>
      <td><input type="text" name="city" value="<?php echo $orgInfo['city'];?>" size="40" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">State:</th>
      <td><input type="text" name="state" value="<?php echo $orgInfo['state'];?>" size="2" maxlength="2" /> <em>(ex: CA, MI)</em></td>
    </tr>
    <tr valign="top">
      <th scope="row">Zip Code:</th>
      <td><input type="text" name="zipcode" value="<?php echo $orgInfo['zipcode'];?>" size="5" maxlength="5" /></td>
    </tr> 
    <tr valign="top">
      <th scope="row">Phone:</th>
      <td><input type="text" name="phone" value="<?php echo $orgInfo['phone'];?>" size="20" maxlength="10" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">Fax:</th>
      <td><input type="text" name="fax" value="<?php echo $orgInfo['fax'];?>" size="20" maxlength="10" /></td>
    </tr>                    
    <tr valign="top">
      <th scope="row">Email:</th>
      <td><input type="text" name="email" value="<?php echo $orgInfo['email'];?>" size="40" /><br /><small>This email address will be used for the contact form</small></td>
    </tr>
    <tr valign="top">
      <th scope="row">Description:</th>
      <td><div id="editorcontainer"><textarea cols="45" rows="8" name="description" id="content"><?php echo $orgInfo['description'];?></textarea></div></td>
    </tr>    
  </table>
  <p class="submit"><input type="submit" name="Submit" value="Update Info &raquo;" /></p>
  </form>
  </div>

<?php
  }


////////////////////////////////////////////////////////////////////////////////
// VIEWS - Frontend
//////////////////////////////////////////////////////////////////////////////// 

  function generateContactForm($atts) {   
  
    $output = '<div id="contactform">
    <h3>Send us an Email</h3>
    <form action="'.EWM_CM_PLUGIN_DIR.'/sendmessage.php" method="post" id="cf"><fieldset>';
    
    if ($msg = $_GET['msg']) {
      if ($msg == 'sent') {
        $output .= '<div class="msg"><div id="txtmsg">Your message was successfully sent</div></div>';
      } elseif ($msg == 'failed') {
        $output .= '<div class="msg"><div id="txtmsg">There was an error, please make sure all fields are filled out properly.</div></div>';
      }
    } else {
      $output .= '<div class="msg" style="display: none;"><div id="txtmsg"></div></div>';
    } 
    
    $output .= '  <p><label for="namef">Name (required)</label><br /><input name="name" id="namef" class="itf input" value="" size="22" tabindex="1" type="text" /></p>
                  <p><label for="emailf">Mail (required)</label><br /><input name="email" id="emailf" class="itf input" value="" size="22" tabindex="2" type="text" /></p>
                  <p><label for="subjectf">Subject</label><br /><input name="subject" id="subjectf" class="itf input" value="" size="22" tabindex="3" type="text" /></p>
                  <p><label for="messagef">Message</label><br /><textarea name="message" id="messagef" cols="30" rows="10" tabindex="3" class="input"></textarea></p>';
                
    $output .= '  <input type="hidden" name="url" value="'. get_bloginfo('url').$_SERVER['REQUEST_URI'].'" />
                  <input type="hidden" name="blogid" value="'.$this->blogid.'" />
                  <p><input type="submit" value="Send Message &raquo;" class="isb" id="sender" /><span id="loader"></span></p>';
    
    $output .= '</fieldset></form></div>';
    
    return $output;
  
  }
  
  function generateOrgInfo($atts) {
    global $wpdb;
    
    $orgInfo = $this->getOrgInfo($this->blogid);
    $output = '<div id="orginfo">';
    $output .= '<h3>Directions</h3>';
    $output .= '<h4>'.$orgInfo['name'].'</h4>';
    
    $output .= '<p>';
    
    if ($orgInfo['address']) {
      $output .= $orgInfo['address'].'<br />';
    }
    
    if ($orgInfo['city'] && $orgInfo['state']) {
      $output .= $orgInfo['city'].', '.$orgInfo['state'].' ';
    }
    
    if ($orgInfo['zipcode']) {
      $output .= $orgInfo['zipcode'];
    }
    
    $output .= '</p>';
    
    $output .= '<p>';
    
    if ($orgInfo['phone']) {
      $phone = '('.substr($orgInfo['phone'], 0, 3).') '.substr($orgInfo['phone'], 3, 3).'-'.substr($orgInfo['phone'], 6, 4);
      $output .= '<strong>Phone:</strong> '.$phone.'<br />';
    }
    
    if ($orgInfo['fax']) {
      $fax = '('.substr($orgInfo['fax'], 0, 3).') '.substr($orgInfo['fax'], 3, 3).'-'.substr($orgInfo['fax'], 6, 4);    
      $output .= '<strong>Fax:</strong> '.$fax;
    }
    
    $output .= '</p>';

    $output .= '<p><a href="http://maps.google.com/maps?f=q&source=s_q&hl=en&geocode=&q=7112+N.+Fresno+Street,+Suite+450+Fresno,+CA+93720&sll=37.0625,-95.677068&sspn=49.223579,114.257812&ie=UTF8&z=16&iwloc=r17">Get directions</a></p>';

    $output .= '<div id="map" ></div>';

    $output .= '<div class="geo" title="<strong>Sierra HR</strong><br />'.$orgInfo['address'].'<br />'.$orgInfo['city'].', '.$orgInfo['state'].' '.$orgInfo['zipcode'].'">';
    $output .= '<span class="latitude" title="36.840405">&nbsp;</span>';
    $output .= '<span class="longitude" title="-119.781801">&nbsp;</span>';
    $output .= '</div>';

    $output .= $orgInfo['description'];
    $output .= '</div>';
    $output .= '<br class="clear">';
    
    return $output;
    
  }

}

?>