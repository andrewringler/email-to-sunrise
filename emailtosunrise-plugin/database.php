<?php

global $emailtosunrise_db_version;
$emailtosunrise_db_version = "2.1";

function email_to_sunrise_install_db() {
   global $wpdb;
   global $emailtosunrise_db_version;
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

   $message_table = $wpdb->prefix . "emailtosunrise_email";   
   // 78 is the recommended max message_id length - http://tools.ietf.org/html/rfc5322#section-3.6.4
   $sql = "CREATE TABLE $message_table (
     id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,     
     type VARCHAR(10),
     status VARCHAR(10),
     author VARCHAR(255),
     subject VARCHAR(255),
     body VARCHAR(1024),
     message_id VARCHAR(78) NOT NULL,
     reference VARCHAR(78),
     ref_id BIGINT,
     send_date DATETIME NOT NULL,
     category VARCHAR(10),
     UNIQUE KEY message_id (id)
     );";
   dbDelta($sql);
 
   $image_table = $wpdb->prefix . "emailtosunrise_images";
   $sql = "CREATE TABLE $image_table (
     id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
     email_id BIGINT NOT NULL,
     image_url VARCHAR(1024) NOT NULL,
     filename VARCHAR(1024) NOT NULL
     );";
   dbDelta($sql);
 
   add_option("emailtosunrise_db_version", $emailtosunrise_db_version);
}
/*
status:
  seen - initial status
  draft - posted to wordpress as a draft (or pending comment)

type:
  original: email meets the requirements for a top-level post
    - it is from a verified sender
    - it matches the keywords (in subject or body)
    - it has 1 or more image attachments
    - it is not a reply-to (contains a reference header) 
    - it s not a forward (contains a reference header or the words fwd in subject or --forward-- in body)

  comment - email meets the requirements for a comment
    - from a verified sender
    - it references an original (contains a reference header with a message_id that matches an original)

  comment? - entry should be rechecked in a subsequent pass
    - possibly a comment, since we don't exactly know the order we are reading emails
    we could read a potential comment before we have read the original post, so keep these around

  ignored - email does not need to ever by re-visisted
*/
function set_message_status($send_date, $message_id, $status, $type, $author = '', $subject = '', $body = '', $reference = '', $category = '') {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   $reference_id_truncated = substr($reference, 0, 78);
   $author_truncated = substr($author, 0, 255);
   $subject_truncated = substr($subject, 0, 255);
   
   $patterns = array (
     '/\w/', // match any alpha-numeric character sequence, except underscores
     '/\d/', // match any number of decimal digits
     '/_/',  // match any number of underscores
     '/\s+/'  // match any number of white spaces
   );
   $replaces = array (
     '$0', // keep
     '$0', // keep 
     '$0', // keep
     ' ' // leave only 1 space
   );
   $body_truncated = substr(preg_filter($patterns, $replaces, $body), 0, 1024);
   
   $id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $message_table WHERE message_id=%s;", array($message_id_truncated)) );
   if( $id ) {
     $wpdb->update( 
     	$message_table, 
     	array( 
     		'type' => $type,
     		'status' => $status,
     		'author' => $author_truncated,
     		'subject' => $subject_truncated,
     		'body' => $body_truncated,
     		'reference' => $reference_id_truncated,
     		'send_date' => date_format($send_date, 'Y-m-d H:i:s'),
     		'category' => $category
     	), 
     	array( 'id' => $id ), 
     	array( '%s','%s','%s','%s','%s','%s' ), 
     	array( '%d' ) 
     );
     
     return $id;
   } else {
     $rows_affected = $wpdb->insert( 
        $message_table, 
        array(  'message_id' => $message_id_truncated,
                'type' => $type,
                'status' => $status,             		
               	'author' => $author_truncated,
               	'subject' => $subject_truncated,
               	'body' => $body_truncated,
             		'reference' => $reference_id_truncated,
             		'send_date' => date_format($send_date, 'Y-m-d H:i:s'),
             		'category' => $category
        )
      );
      
      return $wpdb->insert_id;
   }
}

function get_message_status($message_id) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   
   $sql = $wpdb->prepare("SELECT status FROM $message_table WHERE message_id=%s;", array($message_id_truncated));
   return $wpdb->get_var( $sql ); 
}

function references_an_original($reference_id) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $reference_id_truncated = substr($reference_id, 0, 78);
   
   $sql = $wpdb->prepare("SELECT COUNT(*) FROM $message_table WHERE type='original' AND message_id=%s;", array($reference_id_truncated));
   $message_count = $wpdb->get_var( $sql );
   return $message_count == 1;
}

/*
status is seen
type is comment?
message references an original
*/
function find_new_comments() {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $reference_id_truncated = substr($reference_id, 0, 78);
   
   $sql = "UPDATE $message_table comment, $message_table original
      SET comment.type='comment', comment.ref_id=original.id
      WHERE comment.type='comment?' AND (comment.status='seen' OR comment.status='posted')
      AND comment.id <> original.id
      AND (INSTR(comment.reference, original.message_id) <> 0) AND original.type='original';";

   return $wpdb->get_var( $sql );
}

function update_attachment($email_id, $image_url, $filename) {
   global $wpdb;
   $image_table = $wpdb->prefix . "emailtosunrise_images";
   
   if ( $email_id && $image_url && $filename ) {
     $sql = $wpdb->prepare("
       INSERT INTO $image_table
       ( email_id, image_url, filename )
       VALUES ( %s, %s, %s )",
       array( $email_id, $image_url, $filename )
     );     
     return $wpdb->get_var( $sql ); 
   }
}

function update_message_status($id, $status) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";   
   $sql = $wpdb->prepare("UPDATE $message_table SET status=%s WHERE id=%d;", array( $status, $id ));
   return $wpdb->get_var( $sql ); 
}

function new_posts() {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   
   $sql = "SELECT * FROM $message_table WHERE type='original' AND status='seen'";
   return $wpdb->get_results( $sql );
}

function new_comments() {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   
   $sql = "SELECT * FROM $message_table WHERE type='comment' AND status='seen'";
   return $wpdb->get_results( $sql );
}

function get_image($post_id) {
   global $wpdb;
   $image_table = $wpdb->prefix . "emailtosunrise_images";
   
   $sql = $wpdb->prepare("SELECT * FROM $image_table WHERE email_id=%d LIMIT 1;", array( $post_id ));
   return $wpdb->get_row( $sql );
}

function blog_post_id_from_email_id($id) {
  global $wpdb;
  $message_table = $wpdb->prefix . "emailtosunrise_email";
  $sql = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='emailtosunrise_email_id' AND meta_value=%d LIMIT 1;",
    array( $id ));
  return $wpdb->get_var( $sql );   
}

?>