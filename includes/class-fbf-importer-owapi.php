<?php

class Fbf_Importer_Owapi
{
    const AUTH_URI = 'https://owapi.4x4tyres.co.uk/owapi/';
    private $token;

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

    public function __construct( $plugin_name, $version, $token )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->token = $token;
    }

    public function run_ow_prepare($log_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Starting OW comparison']);
        $report = [];

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_PREPARE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }
        $variants = $this->ow_curl('stock/variants?limit=200000', 'GET', 200);
        if($variants['status']!=='error'){
            $variants_a = json_decode($variants['response']);
            $ow_skus = array_column($variants_a, 'variantCode');
            $ow_ids = array_column($variants_a, 'variantID');

            // 1. Get the PD ow_ids where NOT discontinued
            $sql = $wpdb->prepare("SELECT * 
            FROM {$table}
            WHERE discontinued=%s", false);
            $all_pd_not_dc = $wpdb->get_results($sql, ARRAY_A);
            $in_ow = [];
            $not_in_ow = [];
            if(!empty($all_pd_not_dc)){
                foreach($all_pd_not_dc as $pd_item){
                    if(in_array($pd_item['primary_id'], $ow_skus)){
                        $in_ow[] = $pd_item;
                    }else{
                        $not_in_ow[] = $pd_item;
                    }
                }

                // For all items that ARE in OW, we need to check that the ow_id in PD is the same as the VariantID from OW
                foreach($in_ow as $in_ow_item){
                    $primary_id = $in_ow_item['primary_id'];
                    $variant_pos = array_search($primary_id, array_column($variants_a, 'variantCode'));
                    $variant = $variants_a[$variant_pos];

                    // Is the ow_id in PD the same as variantCode?
                    if((int)$in_ow_item['ow_id']!==$variant->variantID){
                        // Set ow_id to variantCode and unset updated so that we force an update
                        $u = $wpdb->update($table, [
                            'ow_id' => $variant->variantID,
                            'updated' => '0000-00-00 00:00:00',
                        ], [
                            'id' => $in_ow_item['id']
                        ]);
                        if($u){
                            $report['ow_id_updates']++;
                        }else{
                            $report['ow_id_update_errors']++;
                        }
                    }else{
                        // Do nothing - the primary_id exists in both places so will need to check update dates later to see if we need to update record in OW
                        $report['ow_id_update_not_required']++;
                    }
                }

                // For all items that ARE NOT in OW, we need to create them in OW, then get the variantID of the created record, store it in ow_id in PD and set updated & created to moment it was created in OW
                foreach($not_in_ow as $not_in_ow_item){
                    // Set ow_id to variantCode and unset updated so that we force an update
                    if(!is_null($not_in_ow_item['ow_id'])){
                        $u = $wpdb->update($table, [
                            'ow_id' => null,
                            'updated' => '0000-00-00 00:00:00',
                        ], [
                            'id' => $not_in_ow_item['id']
                        ]);
                        if($u){
                            $report['ow_id_null']++;
                        }else{
                            $report['ow_id_null_errors']++;
                        }
                    }else{
                        $report['ow_id_null_not_required']++;
                    }
                }
            }
            // Add the end time to the log entry
            $u = $wpdb->update($log_table, [
                'ended' => wp_date('Y-m-d H:i:s'),
                'log' => serialize($report)
            ], [
                'id' => $insert_id
            ]);
            update_option($this->plugin_name . '-mts-ow', ['status' => 'READYFOROWDISCONTINUE', 'log_id' => $log_id]);
        }
    }

    public function run_ow_create($log_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        $log = $wpdb->prefix . 'fbf_importer_pimberly_logs';
        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Creating new OW records']);
        $report = [];
        $created_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_CREATE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // First find the records that need to be created - i.e. they have no ow_id in PD and they are not discontinued
        $sql = $wpdb->prepare("SELECT * 
            FROM {$table}
            WHERE discontinued=%s AND ow_id IS NULL", false);
        $items_to_create = $wpdb->get_results($sql, ARRAY_A);

        $limit = null;
        $i = 0;

        foreach($items_to_create as $item_to_create){
            if(is_null($limit) || $i <= $limit){
                $code = 'OWCREATE_TEST_01_' . $i;
                $desc = 'OWCREATE_TEST_01 batch';
                $payload = $this->get_create_item_payload($item_to_create);
                $ow_insert = $this->ow_curl('variants?template_variant_id=107741', 'POST', 201, $payload, ['Content-Type:application/json']);
                if($ow_insert['status']==='success'){
                    $created_count++;
                    $ow_response = json_decode($ow_insert['response']);
                    $ow_id = $ow_response->variantInfo->id;
                    $u = $wpdb->update($table, [
                        'ow_id' => $ow_id,
                        'created' => wp_date('Y-m-d H:i:s'),
                        'updated' => wp_date('Y-m-d H:i:s')
                    ], [
                        'id' => $item_to_create['id']
                    ]);
                    $report['ow_create_created']++;
                    $report['ow_created_primary_ids'][] = $item_to_create['primary_id'];
                }else if($ow_insert['status']==='error'){
                    $errored_count++;
                    $report['ow_create_errors']++;
                    $report['ow_create_error_items'][] = [
                        'primary_id' => $item_to_create['primary_id'],
                        'errors' => $ow_insert['errors'],
                        'response' => $ow_insert['response'],
                        'ean' => unserialize($item_to_create['data'])->EAN,
                    ];
                }
                update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => sprintf('Creating new OW records, total: %s, created: %s, errored: %s', count($items_to_create), $created_count, $errored_count)]);
                $i++;
            }
        }

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED', 'log_id' => $log_id]);

        // Now set the status of the current log to completed
        $u = $wpdb->update($log, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'status' => 'COMPLETED'
        ], [
            'id' => $log_id
        ]);
    }

    public function run_ow_discontinue($log_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Discontinuing OW records']);
        $report = [];
        $discontinued_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_UPDATE_DCNT'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // First find the records that need to be discontinued - i.e. they have an ow_id in PD and they ARE discontinued
        $sql = $wpdb->prepare("SELECT * 
            FROM {$table}
            WHERE discontinued=%s AND ow_id IS NOT NULL", true);
        $items_to_discontinue = $wpdb->get_results($sql, ARRAY_A);

        foreach($items_to_discontinue as $item_to_discontinue){
            $payload = [
                "variantInfo" => [
                    "eanCode" => null // Remember that the eanCode is unset so that when the same eanCode crops up on a new Pimberly product, it does not cause an issue where OW reports that the ean already exists
                ],
                "variantSettings" => [
                    "discontinued" => true
                ]
            ];
            $ow_discontinue = $this->ow_curl('variants/' . $item_to_discontinue['ow_id'], 'PUT', 200, json_encode($payload), ['Content-Type:application/json']);
            if($ow_discontinue['status']==='success'){
                $discontinued_count++;
                $report['ow_discontinue_updated']++;
                $report['ow_discontinue_variant_ids'][] = $item_to_discontinue['ow_id'];
            }else if($ow_discontinue['status']==='error'){
                $errored_count++;
                $report['ow_discontinue_errors']++;
                $report['ow_discontinue_error_items'][] = [
                    'primary_id' => $item_to_discontinue['primary_id'],
                    'ow_id' => $item_to_discontinue['ow_id'],
                    'errors' => $ow_discontinue['errors'],
                    'response' => $ow_discontinue['response'],
                ];
            }
            update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => sprintf('Discontinuing OW records, total: %s, discontinued: %s, errored: %s', count($items_to_discontinue), $discontinued_count, $errored_count)]);
        }

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-mts-ow', ['status' => 'READYFOROWUPDATE', 'log_id' => $log_id]);
    }

    public function run_ow_update($log_id)
    {
        global $wpdb;
        $force_update = true;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Starting OW update - checking updates required']);
        $report = [];
        $updates_required = [];
        $update_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_UPDATE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }
        $variants = $this->ow_curl('stock/variants?limit=200000', 'GET', 200);
        if($variants['status']!=='error'){
            $variants_a = json_decode($variants['response']);
            $ow_skus = array_column($variants_a, 'variantCode');
            $ow_ids = array_column($variants_a, 'variantID');

            foreach($ow_ids as $ow_id){
                $pd_sql = $wpdb->prepare("SELECT * 
                    FROM {$table}
                    WHERE discontinued=%s AND ow_id=%s", false, $ow_id);
                $pd_item = $wpdb->get_results($pd_sql, ARRAY_A);

                if($pd_item){
                    // Get the Pimberly dateUpdated
                    $pimberly_update_datetime = new \DateTime(unserialize($pd_item[0]['data'])->dateUpdated);
                    $ow_update_datetime = new \DateTime($pd_item[0]['updated']);

                    if(!$force_update){
                        if($pimberly_update_datetime > $ow_update_datetime){ // If it's been updated on Pimberly after the update time/date on PD
                            $updates_required[] = $pd_item[0];
                            update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => sprintf('Starting OW update - %s updates required', count($updates_required))]);
                        }
                    }else{
                        $updates_required[] = $pd_item[0];
                        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => sprintf('Starting OW update - %s updates required', count($updates_required))]);
                    }

                }
            }

            foreach($updates_required as $item_to_update){
                $payload  = $this->get_create_item_payload($item_to_update, false);
                $ow_update = $this->ow_curl('variants/' . $item_to_update['ow_id'], 'PUT', 200, $payload, ['Content-Type:application/json']);

                if($ow_update['status']==='success'){
                    $update_count++;
                    $report['ow_update_updated']++;
                    $report['ow_update_variant_ids'][] = $item_to_update['ow_id'];
                    $u = $wpdb->update($table, [
                        'updated' => wp_date('Y-m-d H:i:s')
                    ], [
                        'id' => $item_to_update['id']
                    ]);
                }else{
                    $errored_count++;
                    $report['ow_update_errors']++;
                    $report['ow_update_error_items'][] = [
                        'primary_id' => $item_to_update['primary_id'],
                        'ow_id' => $item_to_update['ow_id'],
                        'errors' => $ow_update['errors'],
                        'response' => $ow_update['response'],
                        'ean' => unserialize($item_to_update['data'])->EAN,
                    ];
                }
                update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => sprintf('Updating OW records, total: %s, updated: %s, errored: %s', count($updates_required), $update_count, $errored_count)]);
            }


        }

        $eans = array_column($report['ow_update_error_items'], 'ean');

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-mts-ow', ['status' => 'READYFOROWCREATE', 'log_id' => $log_id]);
    }

    public function run_boughto_ow_prepare($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Starting OW comparison']);
        $report = [];

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_PREPARE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }
        $variants = $this->ow_curl('stock/variants?limit=200000', 'GET', 200);
        if($variants['status']!=='error'){
            $variants_a = json_decode($variants['response']);
            $ow_skus = array_column($variants_a, 'variantCode');
            $ow_ids = array_column($variants_a, 'variantID');

            // 1. Get the BD ow_ids where NOT discontinued
            $sql = $wpdb->prepare("SELECT * 
            FROM {$data_table}
            WHERE discontinued=%s", false);
            $all_bd_not_dc = $wpdb->get_results($sql, ARRAY_A);
            $in_ow = [];
            $not_in_ow = [];

            if(!empty($all_bd_not_dc)) {
                foreach ($all_bd_not_dc as $bd_item) {
                    if (in_array($bd_item['primary_id'], $ow_skus)) {
                        $in_ow[] = $bd_item;
                    } else {
                        $not_in_ow[] = $bd_item;
                    }
                }
            }

            // For all items that ARE in OW, we need to check that the ow_id in BD is the same as the VariantID from OW
            foreach($in_ow as $in_ow_item){
                $primary_id = $in_ow_item['primary_id'];
                $variant_pos = array_search($primary_id, array_column($variants_a, 'variantCode'));
                $variant = $variants_a[$variant_pos];

                // Is the ow_id in PD the same as variantCode?
                if((int)$in_ow_item['ow_id']!==$variant->variantID){
                    // Set ow_id to variantCode and unset updated so that we force an update
                    $u = $wpdb->update($data_table, [
                        'ow_id' => $variant->variantID,
                        'updated' => '0000-00-00 00:00:00',
                    ], [
                        'id' => $in_ow_item['id']
                    ]);
                    if($u){
                        $report['ow_id_updates']++;
                    }else{
                        $report['ow_id_update_errors']++;
                    }
                }else{
                    // Do nothing - the primary_id exists in both places so will need to check update dates later to see if we need to update record in OW
                    $report['ow_id_update_not_required']++;
                }
            }

            // For all items that ARE NOT in OW, we need to create them in OW, then get the variantID of the created record, store it in ow_id in PD and set updated & created to moment it was created in OW
            foreach($not_in_ow as $not_in_ow_item){
                // Set ow_id to variantCode and unset updated so that we force an update
                if(!is_null($not_in_ow_item['ow_id'])){
                    $u = $wpdb->update($data_table, [
                        'ow_id' => null,
                        'updated' => '0000-00-00 00:00:00',
                    ], [
                        'id' => $not_in_ow_item['id']
                    ]);
                    if($u){
                        $report['ow_id_null']++;
                    }else{
                        $report['ow_id_null_errors']++;
                    }
                }else{
                    $report['ow_id_null_not_required']++;
                }
            }
            // Add the end time to the log entry
            $u = $wpdb->update($log_table, [
                'ended' => wp_date('Y-m-d H:i:s'),
                'log' => serialize($report)
            ], [
                'id' => $insert_id
            ]);
            update_option($this->plugin_name . '-boughto-ow', ['status' => 'READYFOROWDISCONTINUE', 'log_id' => $log_id]);
        }
    }

    public function run_boughto_ow_discontinue($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Discontinuing OW records']);
        $report = [];
        $report = [];
        $discontinued_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_UPDATE_DCNT'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // First find the records that need to be discontinued - i.e. they have an ow_id in PD and they ARE discontinued
        $sql = $wpdb->prepare("SELECT * 
            FROM {$data_table}
            WHERE discontinued=%s AND ow_id IS NOT NULL", true);
        $items_to_discontinue = $wpdb->get_results($sql, ARRAY_A);

        foreach($items_to_discontinue as $item_to_discontinue) {
            $payload = [
                "variantInfo" => [
                    "eanCode" => null // Remember that the eanCode is unset so that when the same eanCode crops up on a new Pimberly product, it does not cause an issue where OW reports that the ean already exists
                ],
                "variantSettings" => [
                    "discontinued" => true
                ]
            ];
            $ow_discontinue = $this->ow_curl('variants/' . $item_to_discontinue['ow_id'], 'PUT', 200, json_encode($payload), ['Content-Type:application/json']);
            if($ow_discontinue['status']==='success'){
                $discontinued_count++;
                $report['ow_discontinue_updated']++;
                $report['ow_discontinue_variant_ids'][] = $item_to_discontinue['ow_id'];
            }else if($ow_discontinue['status']==='error'){
                $errored_count++;
                $report['ow_discontinue_errors']++;
                $report['ow_discontinue_error_items'][] = [
                    'primary_id' => $item_to_discontinue['primary_id'],
                    'ow_id' => $item_to_discontinue['ow_id'],
                    'errors' => $ow_discontinue['errors'],
                    'response' => $ow_discontinue['response'],
                ];
            }
            update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => sprintf('Discontinuing OW records, total: %s, discontinued: %s, errored: %s', count($items_to_discontinue), $discontinued_count, $errored_count)]);
        }

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'READYFOROWUPDATE', 'log_id' => $log_id]);
    }

    public function run_boughto_ow_update($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Starting OW update - checking updates required']);
        $report = [];
        $updates_required = [];
        $update_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_UPDATE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }
        $variants = $this->ow_curl('stock/variants?limit=200000', 'GET', 200);
        if($variants['status']!=='error'){
            $variants_a = json_decode($variants['response']);
            $ow_skus = array_column($variants_a, 'variantCode');
            $ow_ids = array_column($variants_a, 'variantID');

            foreach($ow_ids as $ow_id) {
                $pd_sql = $wpdb->prepare("SELECT * 
                    FROM {$data_table}
                    WHERE discontinued=%s AND ow_id=%s", false, $ow_id);
                $pd_item = $wpdb->get_results($pd_sql, ARRAY_A);

                if($pd_item) {
                    $updates_required[] = $pd_item[0];
                }
            }

            //$test_item = $updates_required[array_search('SRW10048520BKM45', array_column($updates_required, 'primary_id'))];
            //$updates_required = [$test_item];

            // Get the All Wheel file data for comparison purposes in the item payload
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-all-wheel-file.php';
            $google_sheet = new Fbf_Importer_All_Wheel_File();
            $all_wheel_data = $google_sheet->read('14Itwv5zfBk0-PwUWBME-dx-Z7wuNu2-QoiGGLQdtT7w', 'AW Update', true);

            foreach($updates_required as $item_to_update){
                // This is where we differ from the Pimberly data in that we cannot compare dates because Boughto does not have a modified date in its data - therefore we  have to assume that EVERYTHING requires an update
                // First we need to get the variant_sales_info_id - if we don't have it we will need to obtain it from OW
                $variant_sales_id = null;
                if(is_null($item_to_update['variant_sales_info_id'])){
                    $variant = $this->ow_curl('variants/' . $item_to_update['ow_id'] . '?include_sales_info=true', 'GET', 200);
                    if($variant['status']==='success'){
                        $variant_a = json_decode($variant['response']);
                        $variant_sales_id = $variant_a->variantSalesInfo[0]->Id;

                        $u = $wpdb->update(
                            $data_table,
                            [
                                'variant_sales_info_id' => $variant_sales_id
                            ],
                            [
                                'id' => $item_to_update['id']
                            ]
                        );
                    }
                }else{
                    $variant_sales_id = $item_to_update['variant_sales_info_id'];
                }

                if(!is_null($variant_sales_id)){
                    $payload  = $this->get_create_wheel_item_payload($item_to_update, $all_wheel_data, $variant_sales_id, false);
                    $ow_update = $this->ow_curl('variants/' . $item_to_update['ow_id'], 'PUT', 200, $payload, ['Content-Type:application/json']);

                    if($ow_update['status']==='success'){
                        $update_count++;
                        $report['ow_update_updated']++;
                        $report['ow_update_variant_ids'][] = $item_to_update['ow_id'];
                        $u = $wpdb->update($data_table, [
                            'updated' => wp_date('Y-m-d H:i:s')
                        ], [
                            'id' => $item_to_update['id']
                        ]);
                    }else{
                        $errored_count++;
                        $report['ow_update_errors']++;
                        $report['ow_update_error_items'][] = [
                            'primary_id' => $item_to_update['primary_id'],
                            'ow_id' => $item_to_update['ow_id'],
                            'errors' => $ow_update['errors'],
                            'response' => $ow_update['response']
                        ];
                    }
                    update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => sprintf('Updating OW records, total: %s, updated: %s, errored: %s', count($updates_required), $update_count, $errored_count)]);
                }else{
                    $errored_count++;
                    $report['ow_update_get_variant_errors']++;
                    $report['ow_update_get_variant_error_items'][] = [
                        'primary_id' => $item_to_update['primary_id'],
                        'ow_id' => $item_to_update['ow_id'],
                        'errors' => 'Variant Sales ID is null',
                    ];
                }
            }
        }

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'READYFOROWCREATE', 'log_id' => $log_id]);
    }

    public function run_boughto_ow_create($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        $log = $wpdb->prefix . 'fbf_importer_boughto_logs';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Creating new OW records']);
        $report = [];
        $created_count = 0;
        $errored_count = 0;

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'OW_IMPORT_CREATE'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // Get the All Wheel file data for comparison purposes in the item payload
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fbf-importer-all-wheel-file.php';
        $google_sheet = new Fbf_Importer_All_Wheel_File();
        $all_wheel_data = $google_sheet->read('14Itwv5zfBk0-PwUWBME-dx-Z7wuNu2-QoiGGLQdtT7w', 'AW Update', true);

        // First find the records that need to be created - i.e. they have no ow_id in BD and they are not discontinued
        $sql = $wpdb->prepare("SELECT * 
            FROM {$data_table}
            WHERE discontinued=%s AND ow_id IS NULL", false);
        $items_to_create = $wpdb->get_results($sql, ARRAY_A);

        $limit = null;
        $i = 0;

        foreach($items_to_create as $item_to_create) {
            if (is_null($limit) || $i <= $limit) {
                $payload = $this->get_create_wheel_item_payload($item_to_create, $all_wheel_data);
                $data = unserialize($item_to_create['data']);
                if($data['range']['material']=='alloy'){
                    $template_id = '107739';
                }else if($data['range']['material']=='steel'){
                    $template_id = '107740';
                }
                $ow_insert = $this->ow_curl('variants?template_variant_id=' . $template_id, 'POST', 201, $payload, ['Content-Type:application/json']);
                if($ow_insert['status']==='success'){
                    $created_count++;
                    $ow_response = json_decode($ow_insert['response']);
                    $ow_id = $ow_response->variantInfo->id;
                    $u = $wpdb->update($data_table, [
                        'ow_id' => $ow_id,
                        'created' => wp_date('Y-m-d H:i:s'),
                        'updated' => wp_date('Y-m-d H:i:s')
                    ], [
                        'id' => $item_to_create['id']
                    ]);
                    $report['ow_create_created']++;
                    $report['ow_created_primary_ids'][] = $item_to_create['primary_id'];
                }else if($ow_insert['status']==='error'){
                    $errored_count++;
                    $report['ow_create_errors']++;
                    $report['ow_create_error_items'][] = [
                        'primary_id' => $item_to_create['primary_id'],
                        'errors' => $ow_insert['errors'],
                        'response' => $ow_insert['response'],
                        'ean' => unserialize($item_to_create['data'])->EAN,
                    ];
                }
                update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => sprintf('Creating new OW records, total: %s, created: %s, errored: %s', count($items_to_create), $created_count, $errored_count)]);
                $i++;
            }
        }

        // Add the end time to the log entry
        $u = $wpdb->update($log_table, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'log' => serialize($report)
        ], [
            'id' => $insert_id
        ]);
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'STOPPED', 'log_id' => $log_id]);

        // Now set the status of the current log to completed
        $u = $wpdb->update($log, [
            'ended' => wp_date('Y-m-d H:i:s'),
            'status' => 'COMPLETED'
        ], [
            'id' => $log_id
        ]);
    }

    private function get_create_item_payload($item, $include_code=true)
    {
        $data = unserialize($item['data']);

        // Gather up all the data
        $description = sprintf('%1$s/%2$s/%3$s %4$s %5$s %6$s %7$s%8$s%9$s', $data->Width, $data->Profile, $data->Rim, $data->Brand, $data->Pattern, !empty($data->Terrain)?$data->Terrain:$data->Season, $data->{'Load Speed'}, $data->{'Run Flat'}==='Y'?' Run flat':'', !empty($data->{'OE Fitment'})?' ' . $data->{'OE Fitment'}:'');
        $depth = round($data->Width / 10, 2);
        $profile_muliplier = (float)('0.'.$data->Profile);
        $length = round(((($data->Width * $profile_muliplier) * 2) / 10 ) + ($data->Rim * 2.54), 2);
        $width = $length;
        $volume = round(($width * $length * $depth)/5000, 2);

        $payload = [
            "variantInfo" => [
                "code" => $data->primaryId,
                "eanCode" => $data->EAN,
                "description" => $description
            ],
            "variantSettings" => [
                "templateVariant" => false,
                "discontinued" => false
            ],
            "variantSalesInfo" => null,
            "variantDimensions" => [
                "weight" => $data->{'Weight (kg)'},
                "volume" => $volume,
                "length" => $length,
                "width" => $width,
                "depth" => $depth
            ],
            "analysis" => [
                "c_1" => $data->{'Load Speed'},
                "c_2" => $data->Brand,
                "c_3" => $data->Pattern,
                "c_4" => !empty($data->Terrain)?$data->Terrain:$data->Season,
                "c_6" => !empty($data->{'OE Fitment'})?$data->{'OE Fitment'}:'',
                "c_7" => $data->Width,
                "c_8" => !empty($data->Terrain)?$data->{'4x4Tyres Image Name'}:$data->{'Image 3Q (URL)'},
                "c_9" => $data->Rim,
                "c_10" => $data->Profile,
                "l_1" => !($data->{'3PMSF'} === 'N'),
                "l_2" => false,
                "l_3" => !($data->XL === 'N'),
                "l_4" => $data->{'White Lettering'}==='true',
                "l_5" => $data->{'Run Flat'}==='Y',
                "l_6" => $data->{'M+S'}==='Y',
                "l_7" => false,
                "l_10" => true,
                "m_1" => $data->{'Rolling Resistance'},
                "m_2" => "Tyre",
                "m_8" => $data->{'Wet Grip'},
                "m_9" => $data->dB,
                "n_2:0" => '1'
            ]
        ];
        if(!$include_code){
            unset($payload['variantInfo']['code']);
        }
        return json_encode($payload);
    }

    private function get_create_wheel_item_payload($item, $all_wheel_data, $variant_sales_id=false, $include_code=true)
    {
        $data = unserialize($item['data']);

        // Get the brand and figure out if it's a House Brand - this will dictate whether we are going to include the price in the Payload
        $brand = $data['range']['brand']['name'];

        if($variant_sales_id){
            $variantSalesInfo = null;
            if($brand_term = get_term_by('name', $brand, 'pa_brand-name')){
                $term_id = $brand_term->term_id;
                $is_house_brand = get_field('is_house_brand', 'term_' . $term_id);
                if($is_house_brand!==true){
                    $variantSalesInfo = [
                        [
                            'rspExcVat' => $data['price'],
                            'Id' => $variant_sales_id,
                        ]
                    ];
                }
            }
        }


        $width_orig = number_format($data['width'], 1);
        $width_p = explode('.', $width_orig);
        if(count($width_p) > 1 && (int)$width_p[1]){
            $width = round($width_p[0]) . '.' . round($width_p[1]) . '"';
        }else{
            $width = round($width_p[0]) . '"';
        }

        $diameter_p = explode('.', $data['diameter']);
        if(count($diameter_p) > 1 && (int)$diameter_p[1]){
            $diameter = round($diameter_p[0]) . '.' . round($diameter_p[1]) . '"';
        }else{
            $diameter = round($diameter_p[0]) . '"';
        }

        if($pcds = $data['pcds'][0]['pcd']){
            if(strstr($pcds, 'x')){
                $pcd_parts = explode('x', $pcds);
                if(round($pcd_parts[1]) == floatval($pcd_parts[1])){
                    // Number is whole
                    $pcd_part_2 = round($pcd_parts[1]);
                }else{
                    // Number is decimal
                    $pcd_part_2 = number_format($pcd_parts[1], 1);
                }
                $pcd = $pcd_parts[0] . '/' . $pcd_part_2;
            }
        }

        if(!is_null($data['unique_product_image'])){
            $image = $data['unique_product_image'];
        }else{
            if(!empty($data['range']['image_url'])){
                $image = $data['range']['image_url'];
            }else if(!empty($data['range']['thumbnail_url'])){
                $image = $data['range']['thumbnail_url'];
            }else{
                $image = '';
            }
        }


        if(!empty($data['range']['color'])){
            $color = ucwords(strtolower($data['range']['color']));
        }else if(!empty($data['range']['finish'])){
            $color = ucwords(strtolower($data['range']['finish']));
        }

        $name = sprintf('%s x %s %s %s %s - %s - %s ET%s%s', $diameter, $width, ucwords(strtolower($data['range']['brand']['name'])), ucwords(strtolower($data['range']['design'])), ucwords(strtolower($data['range']['material'])), $color, $pcd?:'', (int) round($data['offset_et']), !is_null($data['center_bore'])?' CB' . (float) $data['center_bore']:'');
        $description = $name;
        if($data['range']['material']=='alloy'){
            $material = 'Alloy Wheel';
        }else if($data['range']['material']=='steel'){
            $material = 'Steel Wheel';
        }
        $dim_length = (float) ($data['diameter'] / 2.54);
        $dim_width = $dim_length;
        $dim_depth = (float) ($data['width'] / 2.54);
        $dim_volume = (float) (($dim_width * $dim_length * $dim_depth) / 5000);

        $payload = [
            "variantInfo" => [
                "code" => $data['product_code'],
                "description" => $description
            ],
            "variantSettings" => [
                "templateVariant" => false,
                "discontinued" => false
            ],
            "variantSalesInfo" => $variantSalesInfo,
            "variantDimensions" => [
                "weight" => $data['weight']?:14,
                "volume" => round($dim_volume, 2),
                "length" => round($dim_length, 2),
                "width" => round($dim_width, 2),
                "depth" => round($dim_depth, 2)
            ],
            "analysis" => [
                "c_2" => ucwords(strtolower($data['range']['brand']['name'])),
                "c_3" => ucwords(strtolower($data['range']['design'])),
                "c_8" => $image,
                "m_2" => $material,
                "m_3" => $diameter,
                "m_4" => $width,
                "m_5" => $color,
                "m_6" => $data['load_rating'],
                "m_7" => (int) round($data['offset_et']),
                "m_10" => $pcd
            ]
        ];
        if($data['center_bore']){
            $payload['analysis']['n_3'] = $data['center_bore'];
        }
        if(!$include_code){
            unset($payload['variantInfo']['code']);
        }

        return json_encode($payload);
    }

    private function ow_curl($url, $method, $expected_response, $body=null, $headers=[])
    {
        $curl = curl_init();
        $resp = [];
        $opt_headers = [
            'Authorization: Bearer ' . $this->token
        ];
        if(!empty($headers)){
            foreach($headers as $header){
                $opt_headers[] = $header;
            }
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::AUTH_URI . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $opt_headers,
        ));
        if(!is_null($body)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $resp['status'] = 'error';
            $resp['errors'][] = curl_error($curl);
        }else {
            $resp['response'] = $response;
            $resp['response_code'] = curl_getinfo($curl)['http_code'];
            if(curl_getinfo($curl)['http_code']!==$expected_response){
                $resp['status'] = 'error';
                switch(curl_getinfo($curl)['http_code']){
                    case 204:
                        $resp['errors'][] = 'No content';
                        break;
                    case 400:
                        $resp['errors'][]= 'Bad request';
                        break;
                    case 401:
                        $resp['errors'][] = 'Not authorized';
                        break;
                    case 403:
                        $resp['errors'][] = 'Forbidden';
                        break;
                    case 500:
                        $resp['errors'][] = 'Internal server error';
                        break;
                }
            }else{
                $resp['status'] = 'success';
            }
        }

        curl_close($curl);
        return $resp;
    }
}
