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

Adapted from wordpress wp-mail.php
*/

function email_to_sunrise_post() {
  // $post = array(
  //   'post_title'    => 'My post',
  //   'post_content'  => 'This is my post.',
  //   'post_status'   => 'draft',
  //   'post_author'   => 1,
  // );
  // wp_insert_post( $post );
  require 'vendor/autoload.php';

  /* Params are pulled from Admin -> Settings -> Writing -> Post view e-mail
  mail server should not include protocol as it will be added here by Pop3
  */
  $mail = new Zend\Mail\Storage\Pop3(array('host'     => get_option('mailserver_url'),
                                           'user'     => get_option('mailserver_login'),
                                           'password' => get_option('mailserver_pass'),
                                           'port'     => get_option('mailserver_port'),
                                           'ssl'      => 'SSL'
                                           ));  

   echo '<p>'. $mail->countMessages() . " messages found</p>\n";
   foreach ($mail as $message) {
     ?>
      <p>
        From: <?php echo $message->from; ?><br>
        Subject: <?php echo $message->subject; ?><br>
        <?php
          // text/plain message?
          $content_type = explode(';', $message->contentType);
          $content_type = $content_type[0];
          // text/plain message, just output body its good
          if($content_type === 'text/plain'){
            echo $message->getContent();
          } else {
            // output the first text/plain part of a multipart message
            $foundPart = null;
            foreach (new RecursiveIteratorIterator($message) as $part) {
                try {
                    if (strtok($part->contentType, ';') == 'text/plain') {
                      $foundPart = $part;
                      echo $foundPart;
                      break;
                    }
                } catch (Zend_Mail_Exception $e) {
                    // ignore
                }
            }
            if (!$foundPart) {
                echo 'No text/plain parts found in message';
            }
          }
        ?>
      
      </p>
      <?php
   }
  
  wp_die( __( 'Ok' ), 'Ok' );
}

/*
Runs upon visiting the url: yoursite.com/wp-mail.php
*/
add_action('wp-mail.php', 'email_to_sunrise_post');

?>

