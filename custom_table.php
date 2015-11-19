
<?php
register_activation_hook( __FILE__, 'create_plugin_tables' );
function create_plugin_tables()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'user_image';

    $sql = "CREATE TABLE $table_name (
      id int(11) NOT NULL AUTO_INCREMENT,
      username varchar(45) NOT NULL,
		image varchar(45) NOT NULL,
      UNIQUE KEY id (id)
    );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
?>