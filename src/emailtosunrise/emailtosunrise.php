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
Adapted from http://wordpress.org/extend/plugins/postie/
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
  $mail = new Zend\Mail\Storage\Imap(array('host'     => get_option('mailserver_url'),
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
            // multi-part message
            $foundPart = null;
            foreach (new RecursiveIteratorIterator($message) as $part) {
                try {
                    // plain text part
                    if (strtok($part->contentType, ';') == 'text/plain') {
                      $foundPart = $part;
                      echo $foundPart .'<br>';
                    // image
                    } else if (strtok($part->contentType, ';') == 'image/jpeg') {
                      $image = $part->getContent();
                      $encoding = $part->getHeader('Content-Transfer-Encoding', 'string');
                      $image = $encoding === Zend\Mime\Mime::ENCODING_BASE64 ? base64_decode($image) : $image;
                      
                      $uploadDir = wp_upload_dir();
                      $tmpFile = tempnam($uploadDir['path'], 'emailtosunrise');
                      $bytes = file_put_contents($tmpFile, $image);
                      $filename = $part->getHeaderField('Content-Disposition','filename');
                      $image_url = $uploadDir['url'] .'/'. basename($tmpFile);
                      echo "<img style=\"max-width: 400px;\" src=\"{$image_url}\"><br>\n";
                      echo "[{$filename} - {$bytes} bytes]" .'<br>';
                    }
                } catch (Zend_Mail_Exception $e) {
                  echo 'exception ';
                  print_r($e);
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

