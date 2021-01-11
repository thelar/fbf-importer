<?php


class Fbf_Importer_Free_Stock
{
    private $plugin_name;
    private $filename = 'FreeStock.csv';
    private $filepath;
    public function __construct($plugin_name)
    {
        require ABSPATH . '../../vendor/autoload.php';
        $this->plugin_name = $plugin_name;


        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/' . $this->filename;
        }else{
            $this->filepath = ABSPATH . '../../supplier/' . $this->filename;
        }
    }

    public function run()
    {
        $free_stock = [];
        $csv = fopen($this->filepath, 'r');
        $update_count = 0;
        if($csv){
            while(($line = fgetcsv($csv))!==false){
                $free_stock[$line[0]] = [
                    'free_stock' => $line[1],
                    'fbf_stock' => $line[2]
                ];
            }
        }
        fclose($csv);

        if(!empty($free_stock)){
            foreach($free_stock as $sk => $sv){
                $sku = $sk;
                $free_stock = $sv['free_stock'];
                $fbf_stock = $sv['fbf_stock'];

                if ($product_id = wc_get_product_id_by_sku($sku)) {
                    $update_free_stock = update_post_meta($product_id, '_free_stock', $free_stock);
                    $update_fbf_stock = update_post_meta($product_id, '_fbf_stock', $fbf_stock);
                    $update_stock_time = update_post_meta($product_id, '_fbf_stock_time', time());
                    $update_count+= 1;
                }
            }
        }
        return $update_count;
    }
}