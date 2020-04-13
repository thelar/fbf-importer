<?php
/**
 * The api-specific functionality of the plugin.
 *
 *
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/admin
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */

class Fbf_Importer_Api extends Fbf_Importer_Admin
{
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

        add_action('parse_request', array($this, 'endpoint'), 0);
        add_action('init', array($this, 'add_endpoint'));
    }

    public function endpoint()
    {
        global $wp;

        $endpoint_vars = $wp->query_vars;

        // if endpoint
        if ($wp->request == 'api/v2/import') {
            // Your own function to process end point
            $this->fbf_importer_run_import('automatic');
            exit;
        }else if($wp->request == 'api/v2/index') {
            $this->fbf_importer_relevanssi_index();
            exit;
        }else if($wp->request == 'api/v2/stock'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-process-stock.php';
            $processor = new Fbf_Importer_Stock_Processor($this->plugin_name);
            if($processor->process(false)){
                //mail('kevin@code-mill.co.uk', '4x4 Process Stock', 'Processed stock');
            }else{
                //mail('kevin@code-mill.co.uk', '4x4 Process Stock', 'Stock not processed');
            }
            exit;
        }


    }

    public function add_endpoint()
    {
        add_rewrite_endpoint('importer', EP_PERMALINK | EP_PAGES, true);
        add_rewrite_endpoint('index', EP_PERMALINK | EP_PAGES, true);
        add_rewrite_endpoint('stock', EP_PERMALINK | EP_PAGES, true);
    }
}
