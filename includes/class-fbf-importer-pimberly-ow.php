<?php

class Fbf_Importer_Pimberly_Ow
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $pimberly_items = [];
    private $auth_code = 'H65kHhhmRKrFGfAOnkhdHnNM4TpvVprmrlG47CLzmVI5Lpn6rxPUgiAfr3WwXQyl';
    private $max_iterations = 500;

    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;
    }

    public function run($log_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_pimberly_data';
        $log_table = $wpdb->prefix . 'fbf_importer_pimberly_log_items';
        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Importing products from Pimberly']);
        $reponse_code = 200;
        $next = null;
        $count = 0;
        $i = 0;
        $output = [];
        $report = [];

        // Create the log entry
        $i = $wpdb->insert($log_table, [
            'log_id' => $log_id,
            'started' => wp_date('Y-m-d H:i:s'),
            'process' => 'PIMBERLY_IMPORT'
        ]);
        if($i){
            $insert_id = $wpdb->insert_id;
        }

        // Get all of the existing primary_ids from the table
        $q = "SELECT primary_id FROM {$table}";
        $pd_pids = $wpdb->get_col($q);

        do {
            $data = self::pimberly_curl($next);
            $pimberly_data = json_decode($data['response']);
            $next = $pimberly_data->maxId ?? null;
            $response_code = $data['response_code'];

            if($data['status']==='success'){
                if(isset($pimberly_data->data)&&is_array($pimberly_data->data)){
                    foreach($pimberly_data->data as $product){
                        /*if(isset($_GET['collection'])){
                            $collection = $_GET['collection'];
                            if(!is_null($product->{$collection})){
                                if(!in_array($product->{$collection}, $output)){
                                    $output[] = $product->{$collection};
                                }
                            }
                        }*/

                        $primary_id = $product->{'Primary ID'};
                        $sd = serialize($product);
                        $this->pimberly_items[] = $primary_id;

                        if(in_array($primary_id, $pd_pids)){
                            // Update the record and unset
                            $u = $wpdb->update($table, [
                                'discontinued' => false,
                                'last_seen' => wp_date('Y-m-d H:i:s'),
                                'data' => $sd
                            ], [
                                'primary_id' => $primary_id
                            ]);
                            if($u){
								if(isset($report['updates'])){
									$report['updates']++;
								}else{
									$report['updates'] = 1;
								}
                                unset($pd_pids[array_search($primary_id, $pd_pids)]);
                            }else{
								if($report['update_errors']){
									$report['update_errors']++;
								}else{
									$report['update_errors'] = 1;
								}
                            }
                        }else{
                            // Insert the record
                            $i = $wpdb->insert($table,[
                                'primary_id' => $primary_id,
                                'discontinued' => false,
                                'last_seen' => wp_date('Y-m-d H:i:s'),
                                'data' => $sd
                            ]);
                            if($i){
								if($report['inserts']){
									$report['inserts']++;
								}else{
									$report['inserts'] = 1;
								}
                            }else{
								if($report['insert_errors']){
									$report['insert_errors']++;
								}else{
									$report['insert_errors'] = 1;
								}
                            }
                        }

                        update_option($this->plugin_name . '-mts-ow', ['status' => 'RUNNING', 'stage' => 'Imported ' . count($this->pimberly_items) . ' products from Pimberly']);
                    }
                }
                $i++;
            }else{
                $report['errors'][] = 'Pimberly unreachable';
                break;
            }
        } while ($response_code !== 404 && $i <= $this->max_iterations);

        if(!key_exists('errors', $report)){
            // If there's any items left in $pd_pids, set to discontinued
            if(count($pd_pids)){
                foreach($pd_pids as $pid){
                    // Update the record and unset
                    $u = $wpdb->update($table, [
                        'discontinued' => true,
                        'data' => null,
                    ], [
                        'primary_id' => $pid
                    ]);
                    if($u){
						if(isset($report['discontinued'])){
							$report['discontinued']++;
						}else{
							$report['discontinued'] = 1;
						}
                    }else{
						if(isset($report['discontinue_errors'])){
							$report['discontinue_errors']++;
						}else{
							$report['discontinue_errors'] = 1;
						}
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
            update_option($this->plugin_name . '-mts-ow', ['status' => 'READYFOROW', 'log_id' => $log_id]);
        }else{
            // Add the end time to the log entry
            $u = $wpdb->update($log_table, [
                'ended' => wp_date('Y-m-d H:i:s'),
                'log' => serialize($report)
            ], [
                'id' => $insert_id
            ]);
            update_option($this->plugin_name . '-mts-ow', ['status' => 'STOPPED']);
        }
    }

    private function pimberly_curl($next = null)
    {
        $resp = [];
        $url = 'https://api.pimberly.io/core/products?extendResponse=1';
        $headers = [
            'Authorization: ' . $this->auth_code,
        ];
        if(!is_null($next)){
            $headers[] = 'sinceId: ' . $next;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $resp['status'] = 'error';
            $resp['errors'] = curl_error($curl);
        }else{
            $resp['status'] = 'success';
            $resp['response'] = $response;
            $resp['response_code'] = curl_getinfo($curl)['http_code'];
        }
        curl_close($curl);
        return $resp;
    }
}
