<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class Fbf_Importer_Batch_Processor
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $stages = [
        'get_rsp_rules',
        'build_price_match_data',
        'build_stock_array',
        'import_stock',
    ];
    private $stage;
    private $info;
    private $batch;
    private $next_batch;
    private $max_batch;
    private $errors = [];
    protected $rsp_rules;
    private $rsp = [];
    protected $min_stock;
    protected $flat_fee;
    private $fitting_cost;
    private $price_match_data = [];
    private $price_match_fp;
    private $tmp_products_table;
    private $batch_ids;
    private $log_id;
    private $logger;

    public function __construct($plugin_name)
    {
        global $wpdb;

        $this->tmp_products_table = $wpdb->prefix . 'fbf_importer_tmp_products';
        $this->plugin_name = $plugin_name;
        $this->batch = get_option($this->plugin_name)['batch'];
        $this->log_id = get_option($this->plugin_name)['log_id'];

        // Get the max batch from the tmp products db
        $q = "SELECT MAX(batch) as m FROM {$this->tmp_products_table}";
        $r = $wpdb->get_row($q);
        if($r){
            $this->max_batch = $r->m;
        }

        if(function_exists('get_home_path')){
            $this->price_match_fp = get_home_path() . '../supplier/competitor_monitor/';
        }else{
            $this->price_match_fp = ABSPATH . '../../supplier/competitor_monitor/';
        }

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-logger.php';
        $this->logger = new Fbf_Importer_Logger($this->plugin_name);
    }

    public function run()
    {
        if($this->batch <= $this->max_batch){
            //$start = time();
            $dt = new DateTime();
            $tz = new DateTimeZone("Europe/London");
            $dt->setTimezone($tz);
            $start = $dt->getTimestamp();

            //Loop through $this->stages executing each in turn and fail and return if any errors occur
            foreach($this->stages as $stage){
                $stage_start = microtime(true);
                $this->stage = $stage;
                $log_info = [
                    'start' => $start,
                    'batch' => $this->batch,
                    'max_batch' => $this->max_batch,
                ];

                // Update the option with the current stage
                update_option($this->plugin_name, ['status' => 'PROCESSING', 'batch' => $this->batch, 'max_batch' => $this->max_batch, 'stage' => $stage, 'log_id' => $this->log_id]);

                $this->{$stage}();
                $stage_end = microtime(true);
                $exec_time = $stage_end - $stage_start;

                $this->info[$stage]['Start time'] = $stage_start;
                $this->info[$stage]['End time'] = $stage_end;
                $this->info[$stage]['Execution time'] = $exec_time;

                if($count = $this->has_errors($stage)){ //Any errors at any stage will break the run script immediately
                    // $this->log_info($start, false, $auto); TODO: come back to logging
                    $log_info['error_count'] = $count;
                    $log_info['errors'] = $this->errors[$stage];
                }

                $this->logger->log_info($stage . '_' . $this->batch, $log_info, $this->log_id);
            }
        }else{
            // Batch processing finished - ready to clean up
            update_option($this->plugin_name, ['status' => 'READYTOCLEANUP', 'log_id' => $this->log_id]);
        }


        //If we get to here then the script has run successfully
        // $this->log_info($start, true); TODO: come back to logging
    }

    private function get_rsp_rules()
    {
        if(!is_plugin_active('fbf-rsp-generator/fbf-rsp-generator.php')){
            $this->errors[$this->stage] = ['RSP Generator plugin - not active'];
            $this->info[$this->stage]['errors'] = ['RSP Generator plugin - not active'];
        }else {
            $this->rsp_rules = Fbf_Rsp_Generator_Admin::fbf_rsp_generator_generate_rules();
            $this->min_stock = get_option('fbf_rsp_generator_min_stock');
            $this->flat_fee = get_option('fbf_rsp_generator_flat_fee');
            $this->fitting_cost = get_option('fbf_rsp_generator_fitting_cost');
        }
    }

    private function has_errors($stage)
    {
        if(array_key_exists($stage, $this->errors)){
            if(is_array($this->errors[$stage])){
                return count($this->errors[$stage]);
            }
        }
        return false;
    }

    private function build_price_match_data()
    {
        $files = array_diff(scandir($this->price_match_fp, SCANDIR_SORT_DESCENDING), array('.', '..', '.ftpquota')); // Also exclude .ftpquota file
        $updates = 0;
        $log = [];
        $latest_ctime = 0;

        foreach($files as $cfile){
            if (is_file($this->price_match_fp.$cfile) && filectime($this->price_match_fp.$cfile) > $latest_ctime){
                $latest_ctime = filectime($this->price_match_fp.$cfile);
                $latest_filename = $cfile;
                $newest_file = $latest_filename;
            }
        }

        if(!is_null($newest_file)){
            $inputFileType = IOFactory::identify($this->price_match_fp . $newest_file);
            $spreadsheet = IOFactory::load( $this->price_match_fp . $newest_file );
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            $update_count = 0;

            if(!empty($rows)){
                foreach($rows as $ri => $rv){
                    if($ri > 0){
                        if(!empty($rv[3])&&!empty($rv[4])){
                            if(isset($this->price_match_data[strtoupper($rv[4])]) && $this->price_match_data[strtoupper($rv[4])]){
                                // Check to see if price is lower
                                $this->price_match_data[strtoupper($rv[4])]['count']++;
                                if($rv[3] < $this->price_match_data[strtoupper($rv[4])]['price']){
                                    $this->price_match_data[strtoupper($rv[4])]['price'] = $rv[3];
                                    $this->price_match_data[strtoupper($rv[4])]['cheapest'] = $rv[7];
                                    $this->price_match_data[strtoupper($rv[4])]['matched_prices'][] = [
                                        'name' => $rv[7],
                                        'price' => $rv[3],
                                    ];
                                }
                            }else{
                                $this->price_match_data[strtoupper($rv[4])] = [
                                    'price' => $rv[3],
                                    'count' => 1,
                                    'cheapest' => $rv[7],
                                    'matched_prices' => [
                                        [
                                            'name' => $rv[7],
                                            'price' => $rv[3]
                                        ]
                                    ]
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    private function build_stock_array()
    {
        global $wpdb;
        if($this->batch <= $this->max_batch){
            // Start by building an array of all the ID's in the tmp product table
            $q = $wpdb->prepare("SELECT id
                FROM {$this->tmp_products_table}
                WHERE batch = %s", $this->batch);
            $ids = $wpdb->get_col($q);
            if(!empty($ids)){
                $this->batch_ids = $ids;
            }
        }
    }

    private function import_stock()
    {
        global $wpdb;
        $i = 1;
        foreach($this->batch_ids as $id){
            $q = $wpdb->prepare("SELECT *
                FROM {$this->tmp_products_table}
                WHERE id = %s", $id);
            if($row = $wpdb->get_results($q, ARRAY_A)){
				$item = $row['item'];
                $option = get_option($this->plugin_name);
                $option['num_items'] = count($this->batch_ids);
                $option['current_item'] = $i;
                update_option($this->plugin_name, $option);

                $item = unserialize($item[0]);

                // Process the item here
                if(is_array($item) && !empty($item)){
                    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-item-import.php';
                    $importer = new Fbf_Importer_Item_Import($this->plugin_name, $id, $this->min_stock, $this->rsp_rules, $this->price_match_data, $this->fitting_cost, $this->flat_fee);
                    $import = $importer->import($item);
                }
            }
            $i++;
        }

        // After processing all the items set the status back to READYTOPROCESS incrementing the batch number by 1
        update_option($this->plugin_name, ['status' => 'READYTOPROCESS', 'batch' => $this->batch + 1, 'log_id' => $this->log_id]);
    }
}
