<?php

class Fbf_Importer_Boughto_Ow
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $boughto_api_url = 'https://boughtofeed.co.uk/api';
    private $api_key = 'a0dae1b780d44d9cb3a42f328d6a581a';
    private $location_key = 7;
    private $bearer_token = 'a5785ee35b10c0a179a40ed5567f367235cd28ffb115460b3821bdbcec677d9b';

    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;
    }

    public function run($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_items_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Importing products from Pimberly']);

        // Create the log entry
        $i = $wpdb->insert($log_items_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'BOUGHTO_IMPORT'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }
    }
}
