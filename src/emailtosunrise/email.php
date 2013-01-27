<?php
// Adapted from wordpress wp-mail.php
// Adapted from http://wordpress.org/extend/plugins/postie/
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');

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

   $mail->selectFolder('sunrise');
   echo '<p>'. $mail->countMessages() . " messages found</p>\n";
   // echo '<p>'. esc_html(print_r($mail->getFolders())) .'<br>\n';
   $count = 0;
   foreach ($mail as $message) {
     if($count >= 3){
       break;
     }
     $count++;
     $m = get_message_info($message);
     handle_message_info($m);    
   }

   // look for new comments
   $new_comments = find_new_comments();
   if( $new_comments ) {
     echo "<p>Found {$new_comments} new comments</p>";     
   }
   
   // create posts from database
   $new_posts = new_posts();
   foreach ($new_posts as $post) {
    $userdata = get_user_by('email', $post->author);
    if ( ! empty( $userdata ) ) {
      $author_id = $userdata->ID;
      
      $post_insert = array(
          'post_title'    => $post->subject,
          'post_content'  => $post->body,
          'post_status'   => 'publish',
          'post_author'   => $author_id
       );
       $post_id = wp_insert_post( $post_insert );
       
       $image = get_image($post->id);
       echo '<p>image: '. esc_html(var_export($image, true)) ."</p>\n";
       $wp_filetype = wp_check_filetype(basename($image->filename), null);
       $attachment = array(
          'guid' => $image->image_url, 
          'post_mime_type' => $wp_filetype['type'],
          'post_title' => preg_replace('/\.[^.]+$/', '', basename($image->filename)),
          'post_content' => '',
          'post_status' => 'inherit'
       );
       $attach_id = wp_insert_attachment( $attachment, $image->filename, $post_id );
       echo '<p>attach id: '. esc_html(var_export($attach_id, true)) ."</p>\n";
       $attach_data = wp_generate_attachment_metadata( $attach_id, $image->filename );
       echo '<p>attach data: '. esc_html(var_export($attach_data, true)) ."</p>\n";
       if ( $attach_data ) {
         wp_update_attachment_metadata( $attach_id, $attach_data );         
         
         // pre-pend image to post body
         $image_post = wp_get_attachment_image( $attach_id, 'full' );
         
         $new_post_body = $image_post ."\n". $post->body;
         wp_update_post( array( 'ID' => $post_id, 'post_content' => $new_post_body) );
         
         // <a href="http://192.168.33.20/?attachment_id=1140" rel="attachment wp-att-1140">
         //   <img class="alignnone size-full wp-image-1140" alt="emailtosunrisezUyM1H" src="http://192.168.33.20/wp-content/uploads/2013/01/emailtosunrisezUyM1H.jpg" width="640" height="640" />
         //  </a>
       }
       
       update_message_status( $post->id, 'posted' );
    }		
   }
    
   // create comments from database
  
  wp_die( __( 'Ok' ), 'Ok' );
}

function get_message_info($message) {
   $m = (object) array();
   $post_body = '';
   $m->message_id = $message->getHeader('Message-ID', 'string');
   
   if( get_message_status( $m->message_id ) === 'seen' || get_message_status( $m->message_id ) === 'posted' ) {
     $m->status = 'seen';
     return $m;
   } 

   $reference = '';
   try {
     $reference = explode(" ", $message->getHeader('References','string'));
     $m->reference_id = $reference[0];
   } catch (Zend\Mail\Storage\Exception\InvalidArgumentException $e) {
     // ignore, no references, not a reply or forward
   }
   
   try{
     $m->title = trim($message->subject);     
   } catch (Exception $e) {
     $m->title = '';
   }
   
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
               $tmpFile = tempnam($uploadDir['path'], 'emailtosunrise') .'.jpg';
               $m->bytes = file_put_contents($tmpFile, $image);
               
               // $movefile = wp_handle_upload( $tmpFile, array( 'test_form' => false ) );
               // echo '<p>movefile: '. esc_html(var_export($movefile, true)) ."</p>\n";
               
               // if ( $movefile ) {
                  $m->filename = $tmpFile;
                  try {
                    $m->image_name = sanitize_file_name($part->getHeaderField('Content-Disposition','filename'));
                  } catch (Zend\Mail\Storage\Exception\InvalidArgumentException $e) {
                    $m->image_name = '';
                  }
                  $m->image_url = $uploadDir['url'] . '/' . basename($tmpFile);               
                  $image_found = true;
               // }               
            }
         } catch (Zend_Mail_Exception $e) {
           set_message_status($m->message_id, 'seen', 'error', $message->from, $m->title, $m->body);
           return (object) array( 'error' => 'Exception trying to read '. $m->message_id .' '.$e );
         }
     }
     if (!$foundPart) {
       set_message_status($m->message_id, 'seen', 'error', $message->from, $m->title, $m->body);
       return (object) array( 'error' => 'No parts found in a multi-part message!' );
     }
   }
   
   // matches filters?
   if(strstr(strtolower($m->title), 'sunrise') 
   || strstr(strtolower($m->body), 'sunrise')
   || strstr(strtolower($m->image_name), 'sunrise') ){
     $category_found = true;
     $m->category = 'sunrises';         
   }
   if(strstr(strtolower($m->title), 'sunset') 
   || strstr(strtolower($m->body), 'sunset')
   || strstr(strtolower($m->image_name), 'sunset') ){
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

	  if ( strstr(strtolower($m->title), 'fwd') || strstr(strtolower($m->body), '-forward-') ) {
	    $m->type = 'ignored'; // ignore forwards
		} else if ( $author_found && $category_found ) {
		  if ( references_an_original($m->reference_id) ) {
		    $m->type = 'comment';
	    } else if ( $m->reference_id ) {
		    // has a reference, maybe will be a comment once we get more mail in
		    $m->type = 'comment?';		  
		  } else if ( $image_found ) {
		    $m->type = 'original';
		  } else {
  		  $m->type = 'ignored';		    
		  }
		} else {
		  $m->type = 'ignored';
		}
		
		return $m;
}

function handle_message_info($m) {
  if($m->error) {
    echo 'Error '. esc_html($m->message_id) .' error '. esc_html($m->error)  .'<br>';
    return;
  }
  
  if($m->status === 'seen') {
    echo "<h2>Seen</h2>\n<small>". esc_html($m->message_id) .'</small><br>';    
    return;
  } else {
    ?>
     <p>
       <h2><?php echo $m->type; ?></h2>
       From: <?php echo esc_html($m->author_email); ?><br>
       Subject: <?php echo esc_html($m->title); ?><br>
       Message-ID: <small><?php echo esc_html($m->message_id); ?></small><br>
       <br>
       <?php
    echo esc_html($m->body) .'<br>';
    if( $m->image_url ) {
      echo "<img style=\"max-width: 400px;\" src=\"{$m->image_url}\"><br>\n";
      echo '['. esc_html($m->image_name) ." - {$m->bytes} bytes]<br>";      
    }
    echo '<p>';    
    
    $id = set_message_status($m->message_id, 'seen', $m->type, $m->author_email, $m->title, $m->body, $m->reference_id);
    if( $m->type === 'original' && $id ) {
      update_attachment($id, $m->image_url, $m->filename);      
    }
    return;
  }
}

?>