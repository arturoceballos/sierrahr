<?php

  include_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php');
  include_once($_SERVER['DOCUMENT_ROOT'].'/wp-includes/wp-db.php');

  if (isset($_GET['email'])) {
   
    $name     = ucwords($_GET['name']);
    $email    = strtolower($_GET['email']);
    $subject  = 'WEBSITE: ' . stripslashes($_GET['subject']);
    $message  = stripslashes($_GET['message']);
    $error    = false;
    $blogid   = $_GET['blogid'];
    $to       = $wpdb->get_var("SELECT email FROM wp_organization WHERE blog_id = '$blogid'");
    
    if (strlen($name) < 2 || strlen($message) < 2) {
      $error = true;
      echo 'Please fill out all fields';
      exit();
    }
    
    if (!eregi("^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$", $email)) {
      $error = true;
      echo 'Please enter a valid email address';
      exit();
    }
    
    if (!$error) {

      $from = 'From: ' .$name. ' <' .$email. '>';
  
      mail($to, $subject, $message, $from);
      echo 'Your message was successfully sent';
      exit();
    } else {    
      echo 'There was an error, please make sure all fields are filled out.';
    }

  } else {
  
    // This is so the script degrades if js is disabled
    
    $name     = ucwords($_POST['name']);
    $email    = strtolower($_POST['email']);
    $subject  = 'WEBSITE: ' . stripslashes($_POST['subject']);
    $message  = stripslashes($_POST['message']);
    $url      = explode('?', $_POST['url']);
    $url      = $url[0];
    $error    = false;
    $blogid   = $_POST['blogid'];
   
    $to       = $wpdb->get_var("SELECT email FROM wp_organization WHERE blog_id = '$blogid'"); 
        
    if (strlen($name) < 2 || strlen($message) < 2) {
      $error = true;
    }
    
    if (!eregi("^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$", $email)) {
      $error = true;
    }
    
    if (!$error) {

      $from = 'From: ' .$name. ' <' .$email. '>';
  
      mail($to, $subject, $message, $from);
      header("Location: ".$url.'?msg=sent');
      exit();
    } else {    
      header("Location: ".$url.'?msg=failed');
      exit();
    } 

  }
 
?>
