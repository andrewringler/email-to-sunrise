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
     $m = get_message_info($message);
     handle_message_info($m);    
   }
  
  wp_die( __( 'Ok' ), 'Ok' );
}

function get_message_info($message) {
   $m = (object) array();
   $post_body = '';
   $m->message_id = $message->getHeader('Message-ID', 'string');
   
   if( get_message_handled( $m->message_id ) ) {
     $m->status = 'HANDLED';
     return $m;
   } 

   $reference = '';
   try {
     $reference = explode(" ", $message->getHeader('References','string'));
     $m->reference_id = $reference[0];
   } catch (Zend\Mail\Storage\Exception\InvalidArgumentException $e) {
     // ignore, no references, not a reply or forward
   }
   
   $m->title = trim($message->subject);
   
   // text/plain message?
   $content_type = explode(';', $message->contentType);
   $content_type = $content_type[0];
   if($content_type === 'text/plain'){
     // text/plain message, whole content is post body
     $m->body = $message->getContent();
   } else {
     // multi-part message
     $foundPart = null;
     foreach (new RecursiveIteratorIterator($message) as $part) {
         try {
             // plain text part
             if (strtok($part->contentType, ';') == 'text/plain') {
               $foundPart = $part;
               $m->body = $foundPart;
               
             // image
             } else if (strtok($part->contentType, ';') == 'image/jpeg') {
               $image = $part->getContent();
               $encoding = $part->getHeader('Content-Transfer-Encoding', 'string');
               $image = $encoding === Zend\Mime\Mime::ENCODING_BASE64 ? base64_decode($image) : $image;
               
               $uploadDir = wp_upload_dir();
               $tmpFile = tempnam($uploadDir['path'], 'emailtosunrise');
               $m->bytes = file_put_contents($tmpFile, $image);
               $m->filename = sanitize_file_name($part->getHeaderField('Content-Disposition','filename'));
               $m->image_url = $uploadDir['url'] .'/'. basename($tmpFile);
               
               $image_found = true;
             }
         } catch (Zend_Mail_Exception $e) {
           set_message_status($m->message_id, 'ERROR');
           return (object) array( 'error' => 'Exception trying to read '. $m->message_id .' '.$e );
         }
     }
     if (!$foundPart) {
       set_message_status($m->message_id, 'ERROR');
       return (object) array( 'error' => 'No parts found in a multi-part message!' );
     }
   }
   
   // matches filters?
   if(strstr($message->subject, 'sunrise') || strstr($post_body, 'sunrise')){
     $category_found = true;
     $m->category = 'sunrises';         
   }
   if(strstr($message->subject, 'sunset') || strstr($post_body, 'sunset')){
     $category_found = true;
     $m->category = 'sunsets';
   }
   
   // is the email sender an author on the blog?
   if ( preg_match('|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i', $message->from, $matches) )
			$author = $matches[0];
		else
			$author = trim($message->from);
	 $author = sanitize_email($author);
		
   if ( is_email($author) ) {
			$userdata = get_user_by('email', $author);
			if ( ! empty( $userdata ) ) {
				$m->author_id = $userdata->ID;
				$m->author_email = $author;
				$author_found = true;
			}
		}
		
		if ( $author_found && $category_found && $image_found ) {
		  $m->status = 'NEW_POST';
		} else {
		  $m->status = "Not a post {$author_found} {$category_found} {$image_found}";
		}
		
		return $m;
}

function handle_message_info($m) {
  if($m->error) {
    echo 'Error '. esc_html($m->message_id) .' error '. esc_html($m->error)  .'<br>';
    return;
  }
  
  if($m->status === 'HANDLED') {
    echo 'Seen <small>'. esc_html($m->message_id) .'</small><br>';    
    return;
  } else if($m->status === 'NEW_POST') {
    ?>
     <p>
       From: <?php echo esc_html($m->author_email); ?><br>
       Subject: <?php echo esc_html($m->title); ?><br>
       Message-ID: <small><?php echo esc_html($m->message_id); ?></small><br>
       References: <?php echo esc_html($m->reference); ?><br>
       <br>
       <?php
    echo esc_html($m->body) .'<br>';
    echo "<img style=\"max-width: 400px;\" src=\"{$m->image_url}\"><br>\n";
    echo '['. esc_html($m->filename) ." - {$m->bytes} bytes]<br>";
    echo '<p>';    
    set_message_status($m->message_id, 'POST');
  } else {
    echo 'Not a Post <small>'. esc_html($m->message_id) .'</small><br>';
    set_message_status($m->message_id, 'IGNORED');
    return;
  }
}

?>