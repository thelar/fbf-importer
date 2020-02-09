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
    public function enqueue_scripts() {

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
    }

    /**
     * Render the options page for plugin
     *
     * @since  1.0.0
     */
    public function display_options_page() {
        if(!isset($_GET['log_id'])){
            include_once 'partials/fbf-importer-admin-display.php';
        }else{
            include_once 'partials/fbf-importer-admin-display-log.php';
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

        //TODO: run the import here and make result available for the email
        //if(date('G')==='7'){
        //$this->fbf_importer_run_import(true); //Run at 7AM
        //}

        // components for our email
        $recepients = get_option($this->option_name . '_email', get_bloginfo('admin_email'));
        $subject = 'Hello from your Cron Job';
        $message = 'This is a test mail sent by WordPress automatically as per your hourly schedule.';

        // let's send it
        mail($recepients, $subject, $message);
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
        register_setting( $this->plugin_name, $this->option_name . '_file', 'sanitize_text_field' );
        register_setting( $this->plugin_name, $this->option_name . '_email', 'sanitize_email' );
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
            echo '<th>Type</th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';

            for($i=0;$i<count($logs);$i++){
                printf('<tr class="%s">', $i%2?"alternate":"");
                printf('<td>%s</td>', $logs[$i]['starttime']);
                printf('<td>%s</td>', $logs[$i]['endtime']);
                printf('<td>%s</td>', $logs[$i]['success']?'<span style="color:green;font-weight:bold;">Success</span>':'<span style="color:red;font-weight:bold;">Fail</span>');
                printf('<td>%s</td>', $logs[$i]['type']);
                printf('<td><a href="%s">%s</a></td>', get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '&log_id=' . $logs[$i]['id'], 'View log');
                printf('</tr>');
            }

            echo '</table>';
        }
    }

    private function display_log()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_log';
        $id = $_GET['log_id'];
        $sql = "SELECT * FROM $table WHERE id = $id";
        $log = $wpdb->get_row($sql);
        if(!empty($log)){
            $text = json_decode($log->log);
            echo '<div class="postbox">';
            echo '<div class="inside">';
            printf('<h3><code class="transparent">Import - <strong>%s</strong></code></h3>', $log->success?'<span style="color:green;">succeeded</span>':'<span style="color:red;">failed</span>');
            echo '<hr/>';
            printf('<p><code class="transparent">Started: <strong>%s</strong></code></p>', $log->starttime);
            printf('<p><code class="transparent">Finished: <strong>%s</strong></code></p>', $log->endtime);

            if(!empty($text)){
                echo '<table class="widefat code">';
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
                echo '</table>';
            }
            echo '</div>';
            echo '</div>';
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
        return $status;
    }

    private function get_image_info($info)
    {
        $string = implode(', ', $info);
        return ', PRODUCT IMAGE: ' . $string;
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
        echo '<input type="text" name="' . $this->option_name . '_email' . '" id="' . $this->option_name . '_file' . '" value="' . $email . '"> ';

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
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-file-parser.php';
        $importer = new Fbf_Importer_File_Parser($this->plugin_name);
        if($importer->run($auto)===true){ //Returns true if automatic (cron)
            //Importer was successful
            mail('kevin@code-mill.co.uk', '4x4 Import', 'Import done');
        }else{
            //Importer failed TODO: report the errors
            var_dump($importer->errors);
        }
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
        mail('kevin@code-mill.co.uk', '4x4 Index', 'Indexing done');
    }
}
