<?php
// Adapted from wordpress wp-mail.php
// Adapted from http://wordpress.org/extend/plugins/postie/

function email_to_sunrise_post() {
  /* Params are pulled from Admin -> Settings -> Writing -> Post view e-mail
  mail server should not include protocol as it will be added here by Pop3
  and ignored
  
  TODO pull custom options for plugin, don't rely on "Post view e-mail"
  */
  $mail = new Zend\Mail\Storage\Imap(array('host'     => get_option('mailserver_url'),
                                           'user'     => get_option('mailserver_login'),
                                           'password' => get_option('mailserver_pass'),
                                           'port'     => get_option('mailserver_port'),
                                           'ssl'      => 'SSL'
                                           ));  

   echo '<p>'. $mail->countMessages() . " messages found</p>\n";
   foreach ($mail as $message) {
     handle_message($message);    
   }
  
  wp_die( __( 'Ok' ), 'Ok' );
}

function handle_message($message) {
   $post_body = '';
   $message_id = $message->getHeader('Message-ID', 'string');
   
   if( get_message_seen($message_id) ) {
       echo 'Skipping '. esc_html($message_id) .'<br>';
       return; // this email has already been posted
   } 
   
   // already a post or comment? then skip
    // $args = array(
    //   'numberposts' => 1,
    //   'post_type' => 'post',
    //   'meta_key' => 'message_id',
    //   'meta_value' => $message_id
    // );
    // $post = new WP_Query( $args );
    // if( $the_query->have_posts() ) {
    //   continue; // this email has already been posted
    //   echo 'Skipping '. $message_id .'<br>';
    // }
   
   $reference1 = '';
   try {
     $reference1 = explode(" ", $message->getHeader('References','string'));
     $reference1 = $reference1[0];
   } catch (Zend\Mail\Storage\Exception\InvalidArgumentException $e) {
     // ignore, no references, not a reply or forward
   }
  ?>
   <p>
     From: <?php echo esc_html($message->from); ?><br>
     Subject: <?php echo esc_html($message->subject); ?><br>
     Message-ID: <?php echo esc_html($message_id); ?><br>
     References: <?php echo esc_html($reference1); ?><br>
     <br>
     <?php
       // text/plain message?
       $content_type = explode(';', $message->contentType);
       $content_type = $content_type[0];
       // text/plain message, just output body its good
       if($content_type === 'text/plain'){
         $post_body = $message->getContent();
         echo esc_html($post_body) .'<br>';
       } else {
         // multi-part message
         $foundPart = null;
         foreach (new RecursiveIteratorIterator($message) as $part) {
             try {
                 // plain text part
                 if (strtok($part->contentType, ';') == 'text/plain') {
                   $foundPart = $part;
                   $post_body = $foundPart;
                   echo ($post_body) .'<br>';
                   
                 // image
                 } else if (strtok($part->contentType, ';') == 'image/jpeg') {
                   $image = $part->getContent();
                   $encoding = $part->getHeader('Content-Transfer-Encoding', 'string');
                   $image = $encoding === Zend\Mime\Mime::ENCODING_BASE64 ? base64_decode($image) : $image;
                   
                   $uploadDir = wp_upload_dir();
                   $tmpFile = tempnam($uploadDir['path'], 'emailtosunrise');
                   $bytes = file_put_contents($tmpFile, $image);
                   $filename = sanitize_file_name($part->getHeaderField('Content-Disposition','filename'));
                   $image_url = $uploadDir['url'] .'/'. basename($tmpFile);
                   echo "<img style=\"max-width: 400px;\" src=\"{$image_url}\"><br>\n";
                   echo '['. esc_html($filename) ." - {$bytes} bytes]<br>";
                 }
             } catch (Zend_Mail_Exception $e) {
                echo 'Exception trying to read '. esc_html($message_id) .' '.esc_html($e);
                 // continue on
             }
         }
         if (!$foundPart) {
             echo 'No text/plain parts found in message';
         }
       }
       
       //           /* Is this email in reply to another email
       //           that we have already created a post from?
       //           if so we treat this email as a comment on
       //           the original post
       //           */
       //           $args = array(
       //   'numberposts' => -1,
       //   'post_type' => 'post',
       //   'meta_query' => array (
       //   array (
       //    'key' => 'email-to-sunrise-message_id',
       //    'value' => $reference1,
       //               'compare' => 'IN'
       //   )
       // ));    
       //           $post = new WP_Query( $args );
       
       // create post
       $title = $message->subject;
       
       // is the email sender an author on the blog?
       if ( preg_match('|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i', $message->from, $matches) )
					$author = $matches[0];
				else
					$author = trim($line);
				$author = sanitize_email($author);
				
       if ( is_email($author) ) {
					$userdata = get_user_by('email', $author);
					if ( ! empty( $userdata ) ) {
						$post_author = $userdata->ID;
						$author_found = true;
					} else {
					  echo 'Author with email ' . esc_html($author) . ' not found skipping...<br>';
					}
				}
				
				if ( $author_found ) {
         $post = array(
           'post_title'    => wp_strip_all_tags($title),
           'post_content'  => $post_body,
           'post_status'   => 'draft',
           'post_author'   => $post_author,
         );
         // $post_id = wp_insert_post( $post );
         // add_post_meta( $post_id, 'message_id', $message_id );
         
         echo 'Added post ' . $post_id;
				}
       set_message_seen($message_id);
       
     ?>
   
   </p>
   <?php
}

?>