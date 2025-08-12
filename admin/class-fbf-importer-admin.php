<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/admin
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */
#[\AllowDynamicProperties]
class Fbf_Importer_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    protected $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The options name to be used in this plugin
     *
     * @since  	1.0.0
     * @access 	private
     * @var  	string 		$option_name 	Option name of this plugin
     */
    private $option_name = 'fbf_importer';

    private $a;
    private $b;
    private $c;

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

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Importer_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Importer_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fbf-importer-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook_suffix) {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Importer_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Importer_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fbf-importer-admin.js', array( 'jquery' ), $this->version, false );

        if($hook_suffix == $this->mts_ow_id()){
            wp_enqueue_script( $this->plugin_name . '-mts-ow', plugin_dir_url( __FILE__ ) . 'js/fbf-importer-admin-mts-ow.js', array( 'jquery' ), $this->version, false );
            $ajax_params = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce($this->plugin_name),
            );
            wp_localize_script($this->plugin_name . '-mts-ow', 'fbf_importer_admin_mts_ow', $ajax_params);
        }else if($hook_suffix == $this->boughto_ow_id()){
            wp_enqueue_script( $this->plugin_name . '-boughto-ow', plugin_dir_url( __FILE__ ) . 'js/fbf-importer-admin-boughto-ow.js', array( 'jquery' ), $this->version, false );
            $ajax_params = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce($this->plugin_name),
            );
            wp_localize_script($this->plugin_name . '-boughto-ow', 'fbf_importer_admin_boughto_ow', $ajax_params);
        }
    }

    /**
     * Add an options page under the Settings submenu
     *
     * @since  1.0.0
     */
    public function add_options_page() {
        $this->plugin_screen_hook_suffix = add_options_page(
            __( 'Importer Settings', 'fbf-importer' ),
            __( 'Importer', 'fbf-importer' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_options_page' )
        );
        $this->plugin_pimberly_ow_screen_hook_suffix = add_options_page(
            __('MTS to OrderWise Import', 'fbf-importer'),
            __('Pimberly to OW', 'fbf-importer'),
            'manage_options',
            $this->plugin_name . '-mts-ow',
            [$this, 'display_mts_ow']
        );
        $this->plugin_boughto_ow_screen_hook_suffix = add_options_page(
            __('Boughto to OrderWise Import', 'fbf-importer'),
            __('Boughto to OW', 'fbf-importer'),
            'manage_options',
            $this->plugin_name . '-boughto-ow',
            [$this, 'display_boughto_ow']
        );
    }

    /**
     * Render the options page for plugin
     *
     * @since  1.0.0
     */
    public function display_options_page() {
        if(!isset($_GET['log_id'])){
            include_once 'partials/fbf-importer-admin-display.php';
        }else if(!isset($_GET['filename'])){
            include_once 'partials/fbf-importer-admin-display-log.php';
        }else{
            include_once 'partials/fbf-importer-admin-download-xml.php';
        }
    }

    /**
     * Render the MTS to OW page
     *
     * @since  1.0.0
     */
    public function display_mts_ow()
    {
        if(!isset($_GET['log_id'])){
            include_once 'partials/fbf-importer-admin-display-mts-ow.php';
        }else{
            include_once 'partials/fbf-importer-admin-display-mts-ow-log.php';
        }
    }

    /**
     * Render the MTS to OW page
     *
     * @since  1.0.0
     */
    public function display_boughto_ow()
    {
        if(!isset($_GET['log_id'])){
            include_once 'partials/fbf-importer-admin-display-boughto-ow.php';
        }else{
            include_once 'partials/fbf-importer-admin-display-boughto-ow-log.php';
        }
    }

    /**
     * Example daily event.
     *
     * @since 1.0.4
     */
    public function run_daily_event() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        // do something every hour

        // do here what needs to be done automatically as per your schedule
        // in this example we're sending an email

        //TODO: run the import here and make result available for the email

        // components for our email
        $recepients = get_option($this->option_name . '_email', get_bloginfo('admin_email'));
        $subject = 'Hello from your Cron Job';
        $message = 'This is a test mail sent by WordPress automatically as per your schedule.';

        // let's send it
        mail($recepients, $subject, $message);
    }

    /**
     * Example daily event.
     *
     * @since 1.0.4
     */
    public function run_hourly_event() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        // do something every hour

        // do here what needs to be done automatically as per your schedule
        // in this example we're sending an email

        $current_time = time();
        $timeout = 2 * HOUR_IN_SECONDS;
        $auto_imports = get_option('fbf_importer_auto_imports', []);
        if(!empty($auto_imports)){
            foreach($auto_imports as $import_time){
                if($import_time + $timeout > $current_time){
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                    $email = '<h1>Stock Import error</h1>';
                    $email.= sprintf('<p>An error was detected - an automatic import started at <strong>%s</strong> but it has not completed by <strong>%s</strong></p>', date('H:i:s', $import_time), date('H:i:s', $current_time));
                    if($mail = wp_mail('kevin@code-mill.co.uk', 'Stock Import Error', $email, $headers)){
                        if (($key = array_search($import_time, $auto_imports)) !== false) {
                            unset($auto_imports[$key]);
                        }
                    }
                }
            }
            update_option('fbf_importer_auto_imports', $auto_imports);
        }
    }

    /**
     * Register settings.
     *
     * @since 1.0.9
     */
    public function register_settings() {
        // Add a General section
        add_settings_section(
            $this->option_name . '_general',
            __( 'General', 'fbf-importer' ),
            array( $this, $this->option_name . '_general_cb' ),
            $this->plugin_name
        );
        add_settings_field(
            $this->option_name . '_file',
            __( 'File name to process', 'fbf-importer' ),
            array( $this, $this->option_name . '_file_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_file' )
        );
        add_settings_field(
            $this->option_name . '_email',
            __( 'Email address to notify', 'fbf-importer' ),
            array( $this, $this->option_name . '_email_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_email' )
        );
        add_settings_field(
            $this->option_name . '_batch',
            __( 'Batch size', 'fbf-importer' ),
            array( $this, $this->option_name . '_batch_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_batch' )
        );
        add_settings_field(
            $this->option_name . '_logdays',
            __( 'Keep logs for (days)', 'fbf-importer' ),
            array( $this, $this->option_name . '_logdays_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_logdays' )
        );
        register_setting( $this->plugin_name, $this->option_name . '_file', 'sanitize_text_field' );
        register_setting( $this->plugin_name, $this->option_name . '_email', 'sanitize_email' );
        register_setting( $this->plugin_name, $this->option_name . '_batch', 'sanitize_text_field' );
        register_setting( $this->plugin_name, $this->option_name . '_logdays', 'sanitize_text_field' );
    }

    /**
     * Render the text for the general section
     *
     * @since  1.0.9
     */
    public function fbf_importer_general_cb() {
        echo '<p>' . __( 'Please change the settings accordingly.', 'fbf-importer' ) . '</p>';
    }

    /**
     * Render the file name input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_file_cb() {
        $file = get_option( $this->option_name . '_file' );
        echo '<input type="text" name="' . $this->option_name . '_file' . '" id="' . $this->option_name . '_file' . '" value="' . $file . '"> ';
    }

    private function display_log_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_log';
        $sql = "SELECT * FROM $table ORDER BY starttime DESC";
        $logs = $wpdb->get_results($sql, 'ARRAY_A');
        if(!empty($logs)){
            echo '<hr/>';
            echo '<h2>Import log</h2>';
            echo '<table class="widefat">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Start time</th>';
            echo '<th>End time</th>';
            echo '<th>Status</th>';
            echo '<th></th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';

            for($i=0;$i<count($logs);$i++){
                $file = unserialize($logs[$i]['log'])['processingxml']['file'];
                printf('<tr class="%s">', $i%2?"alternate":"");
                printf('<td>%s</td>', $logs[$i]['starttime']);
                printf('<td>%s</td>', $logs[$i]['endtime']);
                printf('<td>%s</td>', $logs[$i]['success']?'<span style="color:green;font-weight:bold;">Complete</span>':'<span style="color:dimgrey;font-weight:bold;">Running</span>');
                printf('<td><a href="%s">%s</a></td>', get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '&log_id=' . $logs[$i]['id'], 'View log');
                printf('<td><form action="%s" method="post"><input type="hidden" name="action" value="%s"/><input type="hidden" name="file" value="%s"/><a href="#" onclick="this.closest(\'form\').submit();return false;">%s</a></form></td>', admin_url('admin-post.php'), 'fbf_importer_download_file', $file, 'Download XML');
                printf('</tr>');
            }

            echo '</table>';
        }
    }

    private function display_mts_ow_log_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_logs';
        $sql = "SELECT * FROM $table ORDER BY started DESC";
        $logs = $wpdb->get_results($sql, 'ARRAY_A');
        if(!empty($logs)){
            echo '<hr/>';
            echo '<h2>MTS to Orderwise Import log</h2>';
            echo '<table class="widefat">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Start time</th>';
            echo '<th>End time</th>';
            echo '<th>Status</th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';

            for($i=0;$i<count($logs);$i++){
                $file = unserialize($logs[$i]['log'])['processingxml']['file'];
                printf('<tr class="%s">', $i%2?"alternate":"");
                printf('<td>%s</td>', $logs[$i]['started']);
                printf('<td>%s</td>', $logs[$i]['ended']);
                printf('<td>%s</td>', $logs[$i]['status']==='COMPLETED'?'<span style="color:darkgreen;font-weight:bold;">Completed</span>':'<span style="color:dimgrey;font-weight:bold;">Running</span>');
                if($logs[$i]['status']==='COMPLETED'){
                    printf('<td><a href="%s">%s</a></td>', get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '-mts-ow' . '&log_id=' . $logs[$i]['id'], 'View details');
                }else{
                    echo '<td></td>';
                }
                printf('</tr>');
            }

            echo '</table>';
        }
    }

    private function display_boughto_ow_log_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_boughto_logs';
        $sql = "SELECT * FROM $table ORDER BY started DESC";
        $logs = $wpdb->get_results($sql, 'ARRAY_A');
        if(!empty($logs)){
            echo '<hr/>';
            echo '<h2>Boughto to Orderwise Import log</h2>';
            echo '<table class="widefat">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Start time</th>';
            echo '<th>End time</th>';
            echo '<th>Status</th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';

            for($i=0;$i<count($logs);$i++){
                $file = unserialize($logs[$i]['log'])['processingxml']['file'];
                printf('<tr class="%s">', $i%2?"alternate":"");
                printf('<td>%s</td>', $logs[$i]['started']);
                printf('<td>%s</td>', $logs[$i]['ended']);
                printf('<td>%s</td>', $logs[$i]['status']==='COMPLETED'?'<span style="color:darkgreen;font-weight:bold;">Completed</span>':'<span style="color:dimgrey;font-weight:bold;">Running</span>');
                if($logs[$i]['status']==='COMPLETED'){
                    printf('<td><a href="%s">%s</a></td>', get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '-boughto-ow' . '&log_id=' . $logs[$i]['id'], 'View details');
                }else{
                    echo '<td></td>';
                }
                printf('</tr>');
            }

            echo '</table>';
        }
    }

    private function display_status()
    {
        $option = get_option($this->plugin_name, ['status' => 'READY']);

        if(!empty($option['batch'])){
            if(!empty($option['max_batch'])){
                $batch_text = sprintf('(batch %s of %s)', $option['batch'], $option['max_batch']);
            }else{
                $batch_text = sprintf('(batch %s)', $option['batch']);
            }
        }

        if(!empty($option['stage'])){
            $stage_text = sprintf(' - stage: %s', $option['stage']);
        }

        if(!empty($option['num_items'])){
            $item_text = sprintf('(item %s of %s items)', $option['current_item'], $option['num_items']);
        }

        return sprintf($option['status'] . ' %s %s %s', $batch_text ?? '', $stage_text ?? '', $item_text ?? '');
    }

    private function display_log()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_log';
        $id = $_GET['log_id'];
        $sql = "SELECT * FROM $table WHERE id = $id";
        $log = $wpdb->get_row($sql);
        if(!empty($log)){
            $text = unserialize($log->log);
            echo '<div class="postbox">';
            echo '<div class="inside">';
            printf('<h3><code class="transparent">Import - <strong>%s</strong></code></h3>', $log->success?'<span style="color:green;">completed</span>':'<span style="color:dimgrey;">running</span>');
            echo '<hr/>';
            printf('<p><code class="transparent">Started: <strong>%s</strong></code></p>', $log->starttime);
            printf('<p><code class="transparent">Finished: <strong>%s</strong></code></p>', $log->endtime);

            if(!empty($text)){
                echo '<pre>';
                print_r($text);
                echo '</pre>';

                /*echo '<table class="widefat code">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Stage</th>';
                echo '<th>Execution time</th>';
                echo '<th>Status</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach($text as $k => $v){
                    echo '<tr>';
                    printf('<td><strong>%s</strong></td>', $k);
                    printf('<td>%f seconds</td>', round($v->{'Execution time'}, 5));
                    printf('<td>%s</td>', isset($v->errors)?'<span style="color:red;">Failed</span>':'<span style="color:green;">Passed</span>');
                    echo '</tr>';
                    if(isset($v->errors)){
                        echo '<tr>';
                        echo '<td colspan="3" style="background:#fae3e3;">';
                        foreach($v->errors as $error){
                            printf('<p class="mb-0 error"><small>%s</small></p>', $error);
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    if(isset($v->stock_status)){
                        print('<tr style="background: #dbffdd;"><td colspan="3"><p style="color: #0b4f00;" class="mb-0"><small>' . $this->print_stock_import_status($v->stock_status)) . '</small></p></td></tr>';
                        foreach($v->stock_status as $sk => $sv){
                            //if(isset($sv->errors)){
                                printf('<tr style="background: %s;">', isset($sv->errors)?'#ffeaca':'#dbffdd');
                                printf('<td colspan="3"><p class="mb-0" style="color: %s"><small><strong>%s</strong>%s</small></p></td>', isset($sv->errors)?'#5f4316':'#0b4f00', $sk, isset($sv->errors)?' - ' . $this->implode($sv->errors):' - ' . $this->print_status($sv));
                                echo '</tr>';
                            //}
                        }
                    }
                }
                echo '<tbody>';
                echo '</table>';*/
            }
            echo '</div>';
            echo '</div>';
        }
    }

    private function display_mts_ow_log()
    {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'fbf_importer_pimberly_logs';
        $log_items_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';

        $id = $_GET['log_id'];
        $sql = "SELECT * FROM $logs_table WHERE id = $id";
        $log = $wpdb->get_row($sql);
        printf('<p>Started: <code>%s</code></p>', $log->started);
        printf('<p>Ended: <code>%s</code></p>', $log->ended);

        $items_sql = $wpdb->prepare("SELECT * FROM $log_items_table
        WHERE log_id = %s", $_GET['log_id']);
        $items = $wpdb->get_results($items_sql, ARRAY_A);
        if($items){
            foreach($items as $item){
                $log = unserialize($item['log']);

                switch($item['process']){
                    case 'PIMBERLY_IMPORT':
                        echo '<h3>Import data from Pimberly:</h3>';
                        printf('<p><strong>Inserts</strong> - these are records that are in the Pimberly data but do not currently appear in our data: %s inserts, %s insert errors</p>', array_key_exists('inserts', $log) ? $log['inserts'] : 0, array_key_exists('insert_errors', $log) ? $log['insert_errors'] : 0);
                        printf('<p><strong>Updates</strong> - these are records that are in the Pimberly data AND also appear in our data: %s updates, %s update errors</p>', array_key_exists('updates', $log) ? $log['updates'] : 0, array_key_exists('update_errors', $log) ? $log['update_errors'] : 0);
                        printf('<p><strong>Discontinued</strong> - these are records that are in our data but DO NOT appear in Pimberly data: %s discontinued, %s discontinue errors</p>', array_key_exists('discontinued', $log) ? $log['discontinued'] : 0, array_key_exists('discontinue_errors', $log) ? $log['discontinue_errors'] : 0);
                        break;
                    case 'OW_IMPORT_PREPARE':
                        echo '<h3>Prepare for import to OW:</h3>';
                        echo '<p><strong><code>primary_id</code> exists in Orderwise:</strong></p>';
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data and the <code>variantCode</code> of the SKU in Orderwise are the same</p>', $log['ow_id_update_not_required']);

                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data and the <code>variantId</code> of the SKU in Orderwise are <strong>NOT</strong> the same, records are updated with fresh <code>variantId</code> and updated date is removed to force an update, there were %s errors</p>', array_key_exists('ow_id_updates', $log) ? $log['ow_id_updates'] : 0, array_key_exists('ow_id_update_errors', $log) ? $log['ow_id_update_errors'] : 0);

                        echo '<p><strong><code>primary_id</code> does not exist in Orderwise:</strong></p>';
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data is <code>NULL</code></p>', $log['ow_id_null_not_required']);
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data is <strong>NOT</strong> <code>NULL</code>, records have <code>ow_id</code> set to <code>NULL</code> and updated date is removed to force an update, there were %s errors</p>', array_key_exists('ow_id_null', $log) ? $log['ow_id_null'] : 0, array_key_exists('ow_id_null_errors', $log) ? $log['ow_id_null_errors'] : 0);
                        break;
                    case 'OW_UPDATE_DCNT':
                        echo '<h3>Discontinue items in Orderwise:</h3>';
                        printf('<p>%s items successfully updated to discontinued</p>', array_key_exists('ow_discontinue_updated', $log) ? $log['ow_discontinue_updated'] : 0);
                        if(array_key_exists('ow_discontinue_variant_ids', $log)){
                            foreach($log['ow_discontinue_variant_ids'] as $dc_id){
                                printf('<p>&nbsp - <code>%s</code></p>', $dc_id);
                            }
                        }
                        if(array_key_exists('ow_discontinue_errors', $log)){
                            printf('<p>%s items failed to successfully update to discontinued</p>', array_key_exists('ow_discontinue_errors', $log) ? $log['ow_discontinue_errors'] : 0);
                            foreach($log['ow_discontinue_error_items'] as $dc_error_item){
                                echo '<pre>';
                                print_r($dc_error_item);
                                echo '</pre>';
                            }
                        }
                        break;
                    case 'OW_IMPORT_UPDATE':
                        echo '<h3>Update items in Orderwise:</h3>';
                        if($log){
                            printf('<p>%s items successfully updated</p>', array_key_exists('ow_update_updated', $log) ? $log['ow_update_updated'] : 0);
                            if(array_key_exists('ow_update_errors', $log)){
                                printf('<p>%s items failed to be updated:</p>', array_key_exists('ow_update_errors', $log) ? $log['ow_update_errors'] : 0);
                                foreach($log['ow_update_error_items'] as $update_error_item){
                                    echo '<pre>';
                                    print_r($update_error_item);
                                    echo '</pre>';
                                }
                            }
                        }else{
                            echo '<p>0 items successfully updated</p>';
                        }
                        break;
                    case 'OW_IMPORT_CREATE':
                        echo '<h3>Create items in Orderwise:</h3>';
                        printf('<p>%s items successfully created</p>', array_key_exists('ow_create_created', $log) ? $log['ow_create_created'] : 0);
                        if(array_key_exists('ow_create_errors', $log)){
                            printf('<p>%s items failed to be created:</p>', array_key_exists('ow_create_errors', $log) ? $log['ow_create_errors'] : 0);
                            foreach($log['ow_create_error_items'] as $create_error_item){
                                echo '<pre>';
                                print_r($create_error_item);
                                echo '</pre>';
                            }
                        }
                        break;
                }
            }
        }
    }

    private function display_boughto_ow_log()
    {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'fbf_importer_boughto_logs';
        $log_items_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';

        $id = $_GET['log_id'];
        $sql = "SELECT * FROM $logs_table WHERE id = $id";
        $log = $wpdb->get_row($sql);
        printf('<p>Started: <code>%s</code></p>', $log->started);
        printf('<p>Ended: <code>%s</code></p>', $log->ended);

        $items_sql = $wpdb->prepare("SELECT * FROM $log_items_table
        WHERE log_id = %s", $_GET['log_id']);
        $items = $wpdb->get_results($items_sql, ARRAY_A);
        if($items){
            foreach($items as $item){
                $log = unserialize($item['log']);

                switch($item['process']){
                    case 'BOUGHTO_IMPORT':
                        echo '<h3>Import data from Boughto:</h3>';
                        printf('<p><strong>Total</strong> - total boughto records read: %s</p>', $log['product_count']);
                        printf('<p><strong>Inserts</strong> - these are records that are in the Boughto data but do not currently appear in our data: %s inserts, %s insert errors</p>', array_key_exists('inserts', $log) ? $log['inserts'] : 0, array_key_exists('insert_errors', $log) ? $log['insert_errors'] : 0);
                        printf('<p><strong>Updates</strong> - these are records that are in the Boughto data AND also appear in our data: %s updates, %s update errors</p>', array_key_exists('updates', $log) ? $log['updates'] : 0, array_key_exists('update_errors', $log) ? $log['update_errors'] : 0);
                        printf('<p><strong>Discontinued</strong> - these are records that are in our data but DO NOT appear in Boughto data: %s discontinued, %s discontinue errors</p>', array_key_exists('discontinued', $log) ? $log['discontinued'] : 0, array_key_exists('discontinue_errors', $log) ? $log['discontinue_errors'] : 0);
                        break;
                    case 'OW_IMPORT_PREPARE':
                        echo '<h3>Prepare for import to OW:</h3>';
                        echo '<p><strong><code>primary_id</code> exists in Orderwise:</strong></p>';
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data and the <code>variantCode</code> of the SKU in Orderwise are the same</p>', $log['ow_id_update_not_required']);

                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data and the <code>variantId</code> of the SKU in Orderwise are <strong>NOT</strong> the same, records are updated with fresh <code>variantId</code> and updated date is removed to force an update, there were %s errors</p>', array_key_exists('ow_id_updates', $log) ? $log['ow_id_updates'] : 0, array_key_exists('ow_id_update_errors', $log) ? $log['ow_id_update_errors'] : 0);

                        echo '<p><strong><code>primary_id</code> does not exist in Orderwise:</strong></p>';
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data is <code>NULL</code></p>', $log['ow_id_null_not_required']);
                        printf('<p>&nbsp;- %s items exist where the <code>ow_id</code> in our data is <strong>NOT</strong> <code>NULL</code>, records have <code>ow_id</code> set to <code>NULL</code> and updated date is removed to force an update, there were %s errors</p>', array_key_exists('ow_id_null', $log) ? $log['ow_id_null'] : 0, array_key_exists('ow_id_null_errors', $log) ? $log['ow_id_null_errors'] : 0);
                        break;
                    case 'OW_UPDATE_DCNT':
                        echo '<h3>Discontinue items in Orderwise:</h3>';
                        printf('<p>%s items successfully updated to discontinued</p>', array_key_exists('ow_discontinue_updated', $log) ? $log['ow_discontinue_updated'] : 0);
                        if(array_key_exists('ow_discontinue_variant_ids', $log)){
                            foreach($log['ow_discontinue_variant_ids'] as $dc_id){
                                printf('<p>&nbsp - <code>%s</code></p>', $dc_id);
                            }
                        }
                        if(array_key_exists('ow_discontinue_errors', $log)){
                            printf('<p>%s items failed to successfully update to discontinued</p>', array_key_exists('ow_discontinue_errors', $log) ? $log['ow_discontinue_errors'] : 0);
                            foreach($log['ow_discontinue_error_items'] as $dc_error_item){
                                echo '<pre>';
                                print_r($dc_error_item);
                                echo '</pre>';
                            }
                        }
                        break;
                    case 'OW_IMPORT_UPDATE':
                        echo '<h3>Update items in Orderwise:</h3>';
                        if($log){
                            printf('<p>%s items successfully updated</p>', array_key_exists('ow_update_updated', $log) ? $log['ow_update_updated'] : 0);
                            if(array_key_exists('ow_update_errors', $log)){
                                printf('<p>%s items failed to be updated:</p>', array_key_exists('ow_update_errors', $log) ? $log['ow_update_errors'] : 0);
                                foreach($log['ow_update_error_items'] as $update_error_item){
                                    echo '<pre>';
                                    print_r($update_error_item);
                                    echo '</pre>';
                                }
                            }
                        }else{
                            echo '<p>0 items successfully updated</p>';
                        }
                        break;
                    case 'OW_IMPORT_CREATE':
                        echo '<h3>Create items in Orderwise:</h3>';
                        printf('<p>%s items successfully created</p>', array_key_exists('ow_create_created', $log) ? $log['ow_create_created'] : 0);
                        if(array_key_exists('ow_create_errors', $log)){
                            printf('<p>%s items failed to be created:</p>', array_key_exists('ow_create_errors', $log) ? $log['ow_create_errors'] : 0);
                            foreach($log['ow_create_error_items'] as $create_error_item){
                                echo '<pre>';
                                print_r($create_error_item);
                                echo '</pre>';
                            }
                        }
                        break;
                }
            }
        }
    }

    private function implode(Array $a)
    {
        $s = implode(', ', $a);
        return $s;
    }

    private function print_status($info)
    {
        switch($info->action) {
            case 'Update':
                $status = 'updated';
                break;
            case 'Create':
                $status = 'created';
                break;
            case 'Hide':
                $status = 'hidden';
                break;
            default:
                $status = 'no action';
                break;
        }
        if(isset($info->image_info)){
            $status.= $this->get_image_info($info->image_info);
        }
        if(isset($info->gallery_info)){
            $status.= $this->get_gallery_info($info->gallery_info);
        }
        if(isset($info->gallery_image_info)){
            $status.= $this->get_image_gallery_info($info->gallery_image_info);
        }
        return $status;
    }

    private function get_image_info($info)
    {
        $string = implode(', ', $info);
        return ', PRODUCT IMAGE: ' . $string;
    }

    private function get_gallery_info($info)
    {
        return ', PRODUCT IMAGE GALLERY: ' . $info;
    }

    private function get_image_gallery_info($info)
    {
        return ', PRODUCT IMAGE GALLERY IMAGES: ' . $info;
    }

    private function print_stock_import_status($stock_status)
    {
        $return = 'Import summary: ';
        $summary = [
            'Update' => 0,
            'Hide' => 0,
            'Create' => 0
        ];
        foreach($stock_status as $sk => $sv){
            if(!isset($sv->errors)){
                $summary[$sv->action]+=1;
            }
        }
        foreach($summary as $sk => $sv){
            $return.= $sk .': ' . $sv . ', ';
        }

        return $return;
    }

    /**
     * Render the email input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_email_cb() {
        $email = get_option( $this->option_name . '_email' );
        echo '<input type="text" name="' . $this->option_name . '_email' . '" id="' . $this->option_name . '_email' . '" value="' . $email . '"> ';

    }

    /**
     * Render the batch input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_batch_cb() {
        $batch = get_option( $this->option_name . '_batch' );
        echo '<input type="text" name="' . $this->option_name . '_batch' . '" id="' . $this->option_name . '_batch' . '" value="' . $batch . '"> ';

    }

    /**
     * Render the logdays input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_logdays_cb() {
        $logdays = get_option( $this->option_name . '_logdays' );
        echo '<input type="text" name="' . $this->option_name . '_logdays' . '" id="' . $this->option_name . '_logdays' . '" value="' . $logdays . '"> ';

    }

    /**
     * Sanitize the text position value before being saved to database
     *
     * @param  string $position $_POST value
     * @since  1.0.0
     * @return string           Sanitized value
     */
    public function fbf_importer_sanitize_position( $position ) {
        if ( in_array( $position, array( 'before', 'after' ), true ) ) {
            return $position;
        }
    }

    /**
     * Perform the import task
     *
     * @return boolean
     */
    public function fbf_importer_run_import($auto=null)
    {
        $options = get_option($this->plugin_name, ['status' => 'READY']);

        if($options['status']==='READY'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-file-reader.php';
            $reader = new Fbf_Importer_File_Reader($this->plugin_name);
            // Check whether the file is uploaded completely
            $reader->check_file_uploaded();
        }else if($options['status']==='READYTOPROCESS'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-batch-processor.php';
            $processor = new Fbf_Importer_Batch_Processor($this->plugin_name);
            $processor->run();

            /*require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-file-parser.php';
            $importer = new Fbf_Importer_File_Parser($this->plugin_name);
            $importer->run($auto);*/
        }else if($options['status']==='READYTOCLEANUP'){
            // Here when all processing is done and we are able to hide product
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-cleanup.php';
            $cleaner = new Fbf_Importer_Cleanup($this->plugin_name);
            $cleaner->clean();
        }
    }

    /**
     * Perform the Pimberly to OW import
     */
    public function fbf_importer_run_pimberly_to_ow_import()
    {
        $options = get_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']);
        if(array_key_exists('log_id', $options)){
            $log_id = $options['log_id'];
        }

        if($options['status']==='READY'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-pimberly-ow.php';
            $pimberly_to_ow = new Fbf_Importer_Pimberly_Ow($this->plugin_name);
            $pimberly_to_ow->run($log_id);
        }else if($options['status']==='READYFOROW') {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if ($token) {
                // OK to run OW api calls
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_ow_prepare($log_id);
            }
        }else if($options['status']==='READYFOROWDISCONTINUE'){
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_ow_discontinue($log_id);
            }
        }else if($options['status']==='READYFOROWCREATE'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_ow_create($log_id);
            }
        }else if($options['status']==='READYFOROWUPDATE'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_ow_update($log_id);
            }
        }
    }

    /**
     * Perform the Boughto to OW import
     */
    public function fbf_importer_run_boughto_to_ow_import()
    {
        $options = get_option($this->plugin_name . '-boughto-ow', ['status' => 'STOPPED']);
        if(array_key_exists('log_id', $options)){
            $log_id = $options['log_id'];
        }

        if($options['status']==='READY'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-boughto-ow.php';
            $pimberly_to_ow = new Fbf_Importer_Boughto_Ow($this->plugin_name);
            $pimberly_to_ow->run($log_id);
        }else if($options['status']==='READYFOROW') {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if ($token) {
                // OK to run OW api calls
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_boughto_ow_prepare($log_id);
            }
        }else if($options['status']==='READYFOROWDISCONTINUE'){
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_boughto_ow_discontinue($log_id);
            }
        }else if($options['status']==='READYFOROWUPDATE'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_boughto_ow_update($log_id);
            }
        }else if($options['status']==='READYFOROWCREATE'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi-auth.php';
            $auth = new Fbf_Importer_Owapi_Auth($this->plugin_name, $this->version);
            $token = $auth->get_valid_token();
            if($token){
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-owapi.php';
                $owapi = new Fbf_Importer_Owapi($this->plugin_name, $this->version, $token);
                $owapi->run_boughto_ow_create($log_id);
            }
        }
    }

    /**
     * Perform the free stock update
     *
     * @return integer or boolean false
     */
    public function fbf_free_stock_update()
    {

    }

    /**
     * Process the stock feeds
     */
    public function fbf_importer_process_stock()
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-process-stock.php';
        $processor = new Fbf_Importer_Stock_Processor($this->plugin_name);
        if($processor->process()){
            $status = 'success';
            $message = urlencode('<strong>Success</strong> - processed Stock files');
        }else{
            $status = 'error';
            $message = urlencode('<strong>Failed</strong> - Stock files were not processed');
        }
        //wp_redirect(get_admin_url() . 'admin.php?page=' . $this->plugin_name . '&fbf_importer_status=' . $status . '&fbf_importer_message=' . $message);
    }

    /**
     * Remove Variable Products - White Lettering
     */
    public function fbf_importer_remove_variations()
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-remove-variable-products.php';
        $engine = new Fbf_Importer_Remove_Variable_Products($this->plugin_name);
        $engine->run();
    }

    /**
     * Perform the data clean
     *
     * @return boolean
     */
    public function fbf_importer_clean_data()
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-clean-data.php';
        $cleaner = new Fbf_Importer_Clean_Data($this->plugin_name);
        $cleaner->run();
    }

    /**
     * Download an xml file
     *
     * @return void
     */
    public function fbf_importer_download_file()
    {
        if(function_exists('get_home_path')){
            $filepath = get_home_path() . '../supplier/imported_stock/' . $_REQUEST['file'];
        }else{
            $filepath = ABSPATH . '../../supplier/imported_stock/' . $_REQUEST['file'];
        }
        $file = file_get_contents($filepath);

        header('Content-type: text/xml');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $_REQUEST['file']));

        echo $file;
        exit();
    }

    public function fbf_importer_admin_notices()
    {
        if(isset($_REQUEST['fbf_importer_status'])) {
            printf('<div class="notice notice-%s is-dismissible">', $_REQUEST['fbf_importer_status']);
            printf('<p>%s</p>', $_REQUEST['fbf_importer_message']);
            echo '</div>';
        }
    }

    public function fbf_importer_relevanssi_index()
    {
        relevanssi_build_index(false, false);
        //mail('kevin@code-mill.co.uk', '4x4 Index', 'Indexing done');
    }

    public function fbf_update_ebay_packages()
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-file-parser.php';
        $importer = new Fbf_Importer_File_Parser($this->plugin_name);
        $files = $importer->update_ebay_packages();

        header('Content-Type: application/json');
        echo json_encode($files);
    }

    public function mts_ow_id(){
        return 'settings_page_fbf-importer-mts-ow';
    }

    public function boughto_ow_id(){
        return 'settings_page_fbf-importer-boughto-ow';
    }

    public function get_status()
    {
        $option = $this->plugin_name . '-mts-ow';
        if(get_option($option)){
            $state = get_option($option)['state'];
            echo $state;
        }
    }
}
