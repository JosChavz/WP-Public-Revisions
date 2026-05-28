<?php

namespace WpPublicRevisions\Core;

/**
 * Installer is responsible of creating and altering the database after the plugin is activated / upgraded
 * 
 * TODO: Figure out how to not make two `get_option` calls
 * TODO: When launch, remove the versioning because it won't matter.
 */
class Installer {
    static $wppr_db_version = "1.1";

    public static function install( ) {
        global $wpdb;

        $table_name = $wpdb->prefix ."wppr_revisions";
        $charset_collate = $wpdb->get_charset_collate();
        $requires_db_update = self::check_version();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL, 
            author bigint(20) unsigned NOT NULL,
            rev_no tinyint unsigned NOT NULL,
            label varchar(255) DEFAULT '' NULL,
            content mediumblob,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	    dbDelta( $sql );

        if ( $requires_db_update ) {
            $prev_db_version = get_option('wppr_db_version', '0');

            if ( version_compare( $prev_db_version, '1.0' ,'==') ) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN is_base, DROP COLUMN base_content, DROP COLUMN diff");
            }
        }

        update_option('wppr_db_version', self::$wppr_db_version );

    }

    private static function check_version() {
        $db_version = get_option('wppr_db_version', '0');

        return version_compare( $db_version, self::$wppr_db_version, '<' );
    }
}