<?php

global $emailtosunrise_db_version;
$emailtosunrise_db_version = "0.5";

function email_to_sunrise_install_db() {
   global $wpdb;
   global $emailtosunrise_db_version;

   $message_table = $wpdb->prefix . "emailtosunrise_email";
   
   // 78 is the recommended max message_id length - http://tools.ietf.org/html/rfc5322#section-3.6.4
   $sql = "CREATE TABLE $message_table (
     id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,     
     message_id VARCHAR(78) NOT NULL,
     type VARCHAR(10),
     UNIQUE KEY message_id (id)
     );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
 
   add_option("emailtosunrise_db_version", $emailtosunrise_db_version);
}

function set_message_status($message_id, $status) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   
   $id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $message_table WHERE message_id=%s;", array($message_id_truncated)) );
   if( $id ) {
     $wpdb->update( 
     	$message_table, 
     	array( 
     		'type' => $status
     	), 
     	array( 'id' => $id ), 
     	array( 
     		'%s'
     	), 
     	array( '%d' ) 
     );
   } else {
     $rows_affected = $wpdb->insert( 
        $message_table, 
        array(  'message_id' => $message_id_truncated, 
                'type' => $status 
        )
      );
   }
}

function get_message_handled($message_id) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   
   $sql = $wpdb->prepare("SELECT COUNT(*) FROM $message_table WHERE message_id=%s;", array($message_id_truncated));
   $message_count = $wpdb->get_var( $sql );
   return $message_count == 1;
}

?>