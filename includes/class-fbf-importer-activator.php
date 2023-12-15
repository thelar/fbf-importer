<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/includes
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */
class Fbf_Importer_Activator {
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        //Install the logging database
        self::db_install();

        // schedule events (cron jobs)
        require_once plugin_dir_path( __FILE__ ) . 'class-fbf-importer-cron.php';
        Fbf_Importer_Cron::schedule();
    }

    private static function db_install()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fbf_importer_log';
        $tmp_products_table_name = $wpdb->prefix . 'fbf_importer_tmp_products';
        $pimberly_products_table_name = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          starttime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          endtime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          success boolean,
          type varchar(20),
          log mediumtext NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_products = "CREATE TABLE $tmp_products_table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          product_id mediumint(9),
          batch tinyint(1),
          sku varchar(40),
          is_white_lettering boolean,
          item text NOT NULL,
          rsp text,
          status text,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_pimberly = "CREATE TABLE $pimberly_products_table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          ow_id mediumint(9),
          primary_id varchar(40),
          updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          last_seen datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          discontinued boolean NOT NULL,
          data text,
          PRIMARY KEY  (id),
          UNIQUE  (ow_id),
          UNIQUE  (primary_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( $sql );
        dbDelta( $sql_products );
        dbDelta( $sql_pimberly );

        add_option('fbf_importer_db_version', FBF_IMPORTER_DB_VERSION);
    }

}
