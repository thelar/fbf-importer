<?php

class Fbf_Importer_Free_Stock
{
    private $plugin_name;
    private $filepath;
    public function __construct($plugin_name)
    {

        $this->plugin_name = $plugin_name;


        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/azure/free_stock_for_website/';
        }else{
            $this->filepath = ABSPATH . '../../supplier/azure/free_stock_for_website/';
        }
    }

    public function run()
    {
        $files = array_diff(scandir($this->filepath, SCANDIR_SORT_DESCENDING), array('.', '..'));
        if(!empty($files)){
            $newest_file = $files[0];
        }

        $xl = $this->filepath . $newest_file;

        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($this->filepath . $newest_file);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $this->filepath . $newest_file );
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

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'files' => $files,
            'newest' => $newest_file,
            'updated' => $update_count
        ]);
    }
}
