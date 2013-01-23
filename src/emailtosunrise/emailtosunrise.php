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
require 'vendor/autoload.php';
require 'database.php';
require 'email.php';


register_activation_hook(__FILE__,'email_to_sunrise_install_db');
// TODO add upgrade function http://codex.wordpress.org/Creating_Tables_with_Plugins

/*
Runs upon visiting the url: yoursite.com/wp-mail.php

TODO the hook doesn't really allow for circumventing the email checking process
it would probably better to use URL rewrites to register a service URL
as something like http://yoursite.com/email-to-sunrise
*/
add_action('wp-mail.php', 'email_to_sunrise_post');

?>