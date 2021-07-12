<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class Fbf_Importer_Free_Stock
{
    private $plugin_name;
    private $filepath;
    private $tyre_availability_fp;
    public function __construct($plugin_name)
    {

        $this->plugin_name = $plugin_name;


        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/azure/free_stock_for_website/';
            $this->tyre_availability_fp = get_home_path() . '../supplier/azure/tyre_availability/';
        }else{
            $this->filepath = ABSPATH . '../../supplier/azure/free_stock_for_website/';
            $this->tyre_availability_fp = ABSPATH . '../../supplier/azure/tyre_availability/';
        }
    }

    public function run()
    {
        $files = array_diff(scandir($this->filepath, SCANDIR_SORT_DESCENDING), array('.', '..'));
        if(!empty($files)){
            $newest_file = $files[0];
        }

        $xl = $this->filepath . $newest_file;

        $inputFileType = IOFactory::identify($this->filepath . $newest_file);
        $spreadsheet = IOFactory::load( $this->filepath . $newest_file );
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $update_count = 0;

        if(!empty($rows)){
            foreach($rows as $sk => $sv){
                if($sk > 0){
                    $sku = $sv[0];
                    $free_stock = $sv[1];
                    $fbf_stock = $sv[2];
                    if($fbf_stock > 0){
                        if ($product_id = wc_get_product_id_by_sku($sku)) {
                            $update_free_stock = update_post_meta($product_id, '_free_stock', $free_stock);
                            $update_fbf_stock = update_post_meta($product_id, '_fbf_stock', $fbf_stock);
                            $update_stock_time = update_post_meta($product_id, '_fbf_stock_time', time());
                            $update_count+= 1;
                        }
                    }
                }
            }
        }

        //Delete files other than newest
        foreach($files as $file){
            if($file!==$newest_file){
                //unlink file here
                unlink($this->filepath . $file);
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'files' => $files,
            'newest' => $newest_file,
            'updated' => $update_count
        ]);
    }

    public function tyre_availability()
    {
        $files = array_diff(scandir($this->tyre_availability_fp, SCANDIR_SORT_DESCENDING), array('.', '..'));
        $updates = 0;
        $log = [];
        if(!empty($files)){
            $newest_file = $files[0];
        }

        if($newest_file){
            $xl = $this->tyre_availability_fp . $newest_file;
            $inputFileType = IOFactory::identify($this->tyre_availability_fp . $newest_file);
            $spreadsheet = IOFactory::load( $this->tyre_availability_fp . $newest_file );
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            foreach($rows as $ri => $row){
                $test_cols = [0, 2, 3];
                foreach($test_cols as $ci => $col){
                    //echo $row[$col];

                    if($product_id = wc_get_product_id_by_sku($row[$col])){
                        $product = wc_get_product($product_id);
                        $pc = (int)$row[11];
                        if($pc===0){

                            if(!empty($row[12])){
                                //Date
                                try {
                                    $date = new DateTime($row[12]);
                                } catch (Exception $e) {
                                    exit(1);
                                }

                                // This corresponds to similar for wheels in import script - line 443
                                update_post_meta($product_id, '_expected_back_in_stock_date', $date->format('Y-m-d'));
                                $updates++;
                                $log[] = [
                                    'id' => $product_id,
                                    'date' => $date->format('Y-m-d')
                                ];
                            }
                        }
                    }
                }
            }
        }

        header('Content-Type: application/json');

        echo json_encode([
            'status' => 'success',
            'files' => $files,
            'newest' => $newest_file,
            'updated' => $updates,
            'log' => $log
        ]);
    }
}
