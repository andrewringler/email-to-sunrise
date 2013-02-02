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
Adapted from wordpress core wp-mail.php and http://wordpress.org/extend/plugins/postie/
Author: Andrew Ringler
Version: 0.1
Author URI: http://andrewringler.com/
*/
require 'vendor/autoload.php';
require 'database.php';
require 'email.php';
require 'posting.php';
require 'debug.php';


register_activation_hook(__FILE__,'email_to_sunrise_install_db');
// TODO add upgrade function http://codex.wordpress.org/Creating_Tables_with_Plugins

function email_to_sunrise_post() {
  clear_db_posts_media_and_uploads();
  check_email_populate_db($limit = 500);
  create_post_and_comments_from_db(); 
  
  // TODO the hook doesn't really allow for circumventing the email checking process
  // so we have to kill the script here.
  // it would probably better to use URL rewrites to register a service URL
  // as something like http://yoursite.com/email-to-sunrise
  wp_die( __( 'Ok' ), 'Ok' );
}
//Runs upon visiting the url: yoursite.com/wp-mail.php
add_action('wp-mail.php', 'email_to_sunrise_post');

?>