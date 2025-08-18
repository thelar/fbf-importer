<?php

class Fbf_Importer_Admin_Ajax
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function fbf_importer_mts_ow_start()
    {
        global $wpdb;
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        $log = $wpdb->prefix . 'fbf_importer_pimberly_logs';
        $max_sql = "SELECT MAX(log_id) AS max_log_id FROM {$log_table}";
        $log_id = $wpdb->get_col($max_sql)[0]+1?:1;
        $i = $wpdb->insert($log, [
            'started' => wp_date('Y-m-d H:i:s'),
            'status' => 'RUNNING',
        ]);
        $resp = [];
        if($update = update_option($this->plugin_name . '-mts-ow', ['status' => 'READY', 'log_id' => $log_id])){
            $resp['status'] = 'success';
            $resp['option'] = get_option($this->plugin_name . '-mts-ow');
        }else{
            $resp['status'] = 'error';
            $resp['error'] = 'Unable to update ' . $this->plugin_name . '-mts-ow' . ' option';
        }
        echo json_encode($resp);
        die();
    }

    public function fbf_importer_mts_ow_check_status()
    {
        $status = get_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']);
        echo json_encode($status);
        die();
    }

    public function fbf_importer_boughto_ow_start()
    {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'fbf_importer_boughto_logs';
        $log_items_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        $max_sql = "SELECT MAX(log_id) AS max_log_id FROM {$log_items_table}";
        $log_id = $wpdb->get_col($max_sql)[0]+1?:1;
        $i = $wpdb->insert($logs_table, [
            'started' => wp_date('Y-m-d H:i:s'),
            'status' => 'RUNNING',
        ]);
        $resp = [];
        if($update = update_option($this->plugin_name . '-boughto-ow', ['status' => 'READY', 'log_id' => $log_id])){
            $resp['status'] = 'success';
            $resp['option'] = get_option($this->plugin_name . '-boughto-ow');
        }else{
            $resp['status'] = 'error';
            $resp['error'] = 'Unable to update ' . $this->plugin_name . '-boughto-ow' . ' option';
        }
        echo json_encode($resp);
        die();
    }

    public function fbf_importer_boughto_ow_check_status()
    {
        $status = get_option($this->plugin_name . '-boughto-ow', ['status' => 'STOPPED']);
        echo json_encode($status);
        die();
    }
}
