<?php

global $emailtosunrise_db_version;
$emailtosunrise_db_version = "0.2";

function email_to_sunrise_install_db() {
   global $wpdb;
   global $emailtosunrise_db_version;

   $message_table = $wpdb->prefix . "emailtosunrise_email";
      
   $sql = "CREATE TABLE $message_table (id VARCHAR(78) NOT NULL,
     UNIQUE KEY id (id)
     );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
 
   add_option("emailtosunrise_db_version", $emailtosunrise_db_version);
}

function set_message_seen($message_id) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   
   $rows_affected = $wpdb->insert( 
      $message_table, 
      array( 'id' => $message_id_truncated ),
      array( '%s')      
    );
}

function get_message_seen($message_id) {
   global $wpdb;
   $message_table = $wpdb->prefix . "emailtosunrise_email";
   $message_id_truncated = substr($message_id, 0, 78);
   
   $sql = $wpdb->prepare("SELECT COUNT(*) FROM $message_table WHERE id=%s;", array($message_id_truncated));
   $message_count = $wpdb->get_var( $sql );
   return $message_count == 1;
}

?>