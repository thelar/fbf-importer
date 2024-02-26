<?php

class Fbf_Importer_Boughto_Ow
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $boughto_api_url = 'https://boughtofeed.co.uk/api';
    private $api_key = 'a0dae1b780d44d9cb3a42f328d6a581a';
    private $location_key = 7;
    private $bearer_token = 'a5785ee35b10c0a179a40ed5567f367235cd28ffb115460b3821bdbcec677d9b';
    private $boughto_items;

    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;
    }

    public function run($log_id)
    {
        global $wpdb;
        $data_table = $wpdb->prefix . 'fbf_importer_boughto_data';
        $log_items_table = $wpdb->prefix . 'fbf_importer_boughto_log_items';
        update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Importing products from Boughto']);
        $report = [];

        // Create the log entry
        $i = $wpdb->insert($log_items_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'BOUGHTO_IMPORT'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // Get all of the existing primary_ids from the table
        $q = $wpdb->prepare("SELECT primary_id
            FROM {$data_table}");
        $pd_pids = $wpdb->get_col($q);

        // Start by reading all the Brands on Boughto
        $headers = [
            'headers' => [
                "Authorization" => "Bearer " . $this->bearer_token
            ],
            'timeout' => 20
        ];
        $url = sprintf('%s/brands/enabled', $this->boughto_api_url);
        $response = wp_remote_get($url, $headers);
        sleep(1);
        $brand_products = [];
        if (is_array($response) && wp_remote_retrieve_response_code($response)===200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);

            //Check response
            if($data['status']==='success'){
                $brands = $data['brands'];

                // Loop through the returned brands
                foreach($brands as $brand){
                    $brand_name = $brand['name'];
                    $report['brand_count']++;
                    $report['brands_names'][] = $brand_name;
                    update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Reading ' . $brand_name . ' products from Boughto']);

                    // Now get all the individual wheels in each brand
                    $url = sprintf('%s/search/wheels?brand=%s', $this->boughto_api_url, $brand['name']);
                    $response = wp_remote_get($url, $headers);
                    sleep(1);
                    if(is_array($response) && wp_remote_retrieve_response_code($response)===200){
                        $brand_data = json_decode(wp_remote_retrieve_body($response), true);
                        $pages = $brand_data['pagination']['total_pages'];
                        $products = $brand_data['results']; // First page
                        $report['product_count']+= $brand_data['pagination']['total'];
                        if($pages > 1){
                            for($i=2;$i<=$pages;$i++){
                                $url = sprintf('%s/search/wheels?brand=%s&page=%s', $this->boughto_api_url, $brand['name'], $i);
                                $response = wp_remote_get($url, $headers);
                                sleep(1);
                                if(is_array($response)){
                                    $brand_data = json_decode(wp_remote_retrieve_body($response), true);
                                    $products = array_merge($products, $brand_data['results']);
                                }else{
                                    $report['errors'] = $response->get_error_message();
                                    break 2;
                                }
                            }
                        }

                        $brand_products[$brand_name] = $products;

                        // now either add to database or update
                        foreach($products as $product){
                            $primary_id = $product['product_code'];
                            $sd = serialize($product);
                            $this->boughto_items[] = $primary_id;

                            if(in_array($primary_id, $pd_pids)){
                                // Update the record and unset
                                $u = $wpdb->update($data_table, [
                                    'discontinued' => false,
                                    'last_seen' => wp_date('Y-m-d H:i:s'),
                                    'data' => $sd
                                ], [
                                    'primary_id' => $primary_id
                                ]);
                                if($u){
                                    $report['updates']++;
                                    unset($pd_pids[array_search($primary_id, $pd_pids)]);
                                }else{
                                    $report['update_errors']++;
                                }
                            }else{
                                // Insert the record
                                $i = $wpdb->insert($data_table,[
                                    'primary_id' => $primary_id,
                                    'discontinued' => false,
                                    'last_seen' => wp_date('Y-m-d H:i:s'),
                                    'data' => $sd
                                ]);
                                if($i){
                                    $report['inserts']++;
                                }else{
                                    $report['insert_errors']++;
                                }
                            }
                            update_option($this->plugin_name . '-boughto-ow', ['status' => 'RUNNING', 'stage' => 'Imported ' . count($this->boughto_items) . ' products from Boughto']);
                        }
                    }else if(wp_remote_retrieve_response_code($response)!==200){
                        $report['errors'][] = $url . ' - ' . wp_remote_retrieve_response_code($response) . ' '  . wp_remote_retrieve_response_message($response);
                    }else{
                        $report['errors'] = $response->get_error_message();
                        break;
                    }
                }
            }else{
                $report['errors'][] = 'Boughto returned ERROR when getting Brands';
            }
        }else if(is_wp_error($response)){
            $report['errors'][] = $response->get_error_message();
        }else if(wp_remote_retrieve_response_code($response)!==200){
            $report['errors'][] = $url . ' - ' . wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
         }


        if(!key_exists('errors', $report)){
            // If there's any items left in $pd_pids, set to discontinued
            if(count($pd_pids)){
                foreach($pd_pids as $pid){
                    // Update the record and unset
                    $u = $wpdb->update($data_table, [
                        'discontinued' => true,
                        'data' => null,
                    ], [
                        'primary_id' => $pid
                    ]);
                    if($u){
                        $report['discontinued']++;
                    }else{
                        $report['discontinue_errors']++;
                    }
                }
            }

            // Add the end time to the log entry
            $u = $wpdb->update($log_items_table, [
                'ended' => wp_date('Y-m-d H:i:s'),
                'log' => serialize($report)
            ], [
                'id' => $insert_id
            ]);
            update_option($this->plugin_name . '-boughto-ow', ['status' => 'READYFOROW', 'log_id' => $log_id]);
        }else{
            // Add the end time to the log entry
            $u = $wpdb->update($log_items_table, [
                'ended' => wp_date('Y-m-d H:i:s'),
                'log' => serialize($report)
            ], [
                'id' => $insert_id
            ]);
            update_option($this->plugin_name . '-boughto-ow', ['status' => 'STOPPED']);
        }
    }
}
