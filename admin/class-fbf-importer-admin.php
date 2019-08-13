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
     * The options name to be used in this plugin
     *
     * @since  	1.0.0
     * @access 	private
     * @var  	string 		$option_name 	Option name of this plugin
     */
    private $option_name = 'fbf_importer';

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
        include_once 'partials/fbf-importer-admin-display.php';
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

        // components for our email
        $recepients = 'kevin@code-mill.co.uk';
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

        // components for our email
        $recepients = 'kevin@code-mill.co.uk';
        $subject = 'Hello from your Cron Job';
        $message = 'This is a test mail sent by WordPress automatically as per your schedule.';

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
            $this->option_name . '_position',
            __( 'Text position', 'fbf-importer' ),
            array( $this, $this->option_name . '_position_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_position' )
        );
        add_settings_field(
            $this->option_name . '_day',
            __( 'Post is outdated after', 'fbf-importer' ),
            array( $this, $this->option_name . '_day_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_day' )
        );
        add_settings_field(
            $this->option_name . '_file',
            __( 'File name to process', 'fbf-importer' ),
            array( $this, $this->option_name . '_file_cb' ),
            $this->plugin_name,
            $this->option_name . '_general',
            array( 'label_for' => $this->option_name . '_file' )
        );
        register_setting( $this->plugin_name, $this->option_name . '_position', array( $this, $this->option_name . '_sanitize_position' ) );
        register_setting( $this->plugin_name, $this->option_name . '_day', 'intval' );
        register_setting( $this->plugin_name, $this->option_name . '_file', 'string' );
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
     * Render the radio input field for position option
     *
     * @since  1.0.9
     */
    public function fbf_importer_position_cb() {
        $position = get_option( $this->option_name . '_position' );
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo $this->option_name . '_position' ?>" id="<?php echo $this->option_name . '_position' ?>" value="before" <?php checked( $position, 'before' ); ?>>
                <?php _e( 'Before the content', 'fbf-importer' ); ?>
            </label>
            <br>
            <label>
                <input type="radio" name="<?php echo $this->option_name . '_position' ?>" value="after" <?php checked( $position, 'after' ); ?>>
                <?php _e( 'After the content', 'fbf-importer' ); ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Render the threshold day input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_day_cb() {
        $day = get_option( $this->option_name . '_day' );
        echo '<input type="text" name="' . $this->option_name . '_day' . '" id="' . $this->option_name . '_day' . '" value="' . $day . '"> ' . __( 'days', 'fbf-importer' );
    }

    /**
     * Render the file name input for this plugin
     *
     * @since  1.0.9
     */
    public function fbf_importer_file_cb() {
        $file = get_option( $this->option_name . '_file' );
        echo '<input type="text" name="' . $this->option_name . '_file' . '" id="' . $this->option_name . '_file' . '" value="' . $file . '"> ' . __( 'file', 'fbf-importer' );
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

}
