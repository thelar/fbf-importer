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
            $this->fbf_importer_run_pimberly_to_ow_import();
            $this->fbf_importer_run_boughto_to_ow_import();
            exit;
        }else if($wp->request == 'api/v2/import_pimberly'){
            $options = get_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']);
            $boughto_options = get_option($this->plugin_name . 'boughto-ow', ['status' => 'STOPPED']); // Need to check if Boughto import is stopped as well because OW will error if both run at the same time
            if($options['status']==='STOPPED' && $boughto_options['status']==='STOPPED'){
                $old_options = get_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']);
                update_option($this->plugin_name . '-mts-ow', ['status' => 'READY', 'log_id' => $old_options['log_id']]);
            }
            exit;
        }else if($wp->request == 'api/v2/import_boughto'){
            $options = get_option($this->plugin_name . 'boughto-ow', ['status' => 'STOPPED']);
            $pimberly_options = get_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']); // Need to check if Pimbery import is stopped as well because OW will error if both run at the same time
            if($options['status']==='STOPPED' && $pimberly_options['status']==='STOPPED'){
                $old_options = get_option($this->plugin_name . 'boughto-ow', ['status' => 'STOPPED']);
                update_option($this->plugin_name . 'boughto-ow', ['status' => 'READY', 'log_id' => $old_options['log_id']]);
            }
            exit;
        }else if($wp->request == 'api/v2/freestock'){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-free-stock.php';
            $free_stock = new Fbf_Importer_Free_Stock($this->plugin_name);
            $free_stock->run();
            exit;
        }else if($wp->request == 'api/v2/tyre_availability') {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-free-stock.php';
            $free_stock = new Fbf_Importer_Free_Stock($this->plugin_name);
            $free_stock->tyre_availability();
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
        }else if($wp->request == 'api/v2/tier1stock'){
            $path = $_SERVER['DOCUMENT_ROOT'] . '/../supplier/azure/tier_1_price_list/';
            $filename = 'PriceListwithStock.xlsx';
            $file = $path . $filename;
            if(file_exists($file)){
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="tier1stock.csv"');
                header('Cache-Control: max-age=0');
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();;
                $sheet = $reader->load($file);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($sheet);
                $writer->setDelimiter(',');

                /*$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, "Csv");
                $writer->setSheetIndex(0);   // Select which sheet to export.
                $writer->setDelimiter(',');  // Set delimiter.*/
                $writer->save('php://output');
            }
            exit;
        }else if($wp->request == 'api/v2/tier2stock') {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/../supplier/azure/tier_2_price_list/';
            $filename = 'PriceListwithStock.xlsx';
            $file = $path . $filename;
            if (file_exists($file)) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="tier2stock.csv"');
                header('Cache-Control: max-age=0');
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $sheet = $reader->load($file);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($sheet);
                $writer->setDelimiter(',');

                //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, "Csv");
                //$writer->setSheetIndex(0);   // Select which sheet to export.
                //$writer->setDelimiter(',');  // Set delimiter.
                $writer->save('php://output');
                /*
                $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($sheet);
                $objWriter->save('php://output');
                */
            }
            exit;
        }else if($wp->request == 'api/v2/update_ebay_packages'){
            $this->fbf_update_ebay_packages();
            exit;
        }else if($wp->request == 'api/v2/read_all_wheel_file'){
            echo 'reading all wheel file here';
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
