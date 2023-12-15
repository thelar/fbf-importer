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
        $resp = [];
        if($update = update_option($this->plugin_name . '-mts-ow', ['status' => 'READY'])){
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
}
