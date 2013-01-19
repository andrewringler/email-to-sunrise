<?php
/**
 * @package Email_To_Sunrise
 * @version 0.1
 */
/*
Plugin Name: Email to Sunrise
Plugin URI: https://github.com/andrewringler/email-to-sunrise
Description: My family sends around sunrise and sunset photos via email,
this wordpress plugin parses those emails and converts them into Wordress posts maintaining 
threaded conversations by converting all replies to comments on the original post.
Author: Andrew Ringler
Version: 0.1
Author URI: http://andrewringler.com/
*/

function email_to_sunrise_post() {
  $post = array(
    'post_title'    => 'My post',
    'post_content'  => 'This is my post.',
    'post_status'   => 'draft',
    'post_author'   => 1,
  );
  wp_insert_post( $post );
  
  wp_die( __( 'Done checking email...' ) );
}

/*
Runs upon visiting the url: yoursite.com/wp-mail.php
*/
add_action('wp-mail.php', 'email_to_sunrise_post');

?>
