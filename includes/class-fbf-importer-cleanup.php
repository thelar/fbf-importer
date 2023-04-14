<?php

class Fbf_Importer_Cleanup
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $tmp_products_table;
    private $stages = [
        'hide_products',
        'hide_products_without_images',
        'write_rsp_xml',
    ];
    private $stage;
    private $products_to_hide;

    public function __construct($plugin_name)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->tmp_products_table = $wpdb->prefix . 'fbf_importer_tmp_products';
        $this->products_to_hide = $this->setup_products_to_hide();
    }

    public function clean()
    {
        global $wpdb;

        // Set status to CLEANING
        update_option($this->plugin_name, ['status' => 'CLEANING']);

        //Loop through $this->stages executing each in turn and fail and return if any errors occur
        foreach($this->stages as $stage) {
            $stage_start = microtime(true);
            $this->stage = $stage;

            update_option($this->plugin_name, ['status' => 'CLEANING', 'stage' => $stage]);
            $this->{$stage}();
            $stage_end = microtime(true);
            $exec_time = $stage_end - $stage_start;
        }


        // Remove option
        delete_option($this->plugin_name);
    }

    private function setup_products_to_hide()
    {

        $this->products_to_hide = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [ //Added to exclude packages
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => ['package'],
                    'operator' => 'NOT IN'
                ]
            ]
        ]);
    }

    private function hide_products()
    {
        foreach($this->products_to_hide as $hide_id){
            $status = [];
            $status['action'] = 'Hide';
            $product_to_hide = new WC_Product($hide_id);
            $sku = $product_to_hide->get_sku();
            $name = $product_to_hide->get_title();

            $product_to_hide->set_catalog_visibility('hidden');
            $product_to_hide->set_stock_quantity(0); // Removes ability to sell product
            $product_to_hide->set_backorders('no');
            if(!$product_to_hide->save()){
                $status['errors'] = 'Could not ' . wc_strtolower($status['action']) . ' ' . $name;
            }
            //$stock_status[$sku] = $status;
            $this->info['import_stock']['stock_status'][$sku] = $status;
        }
    }
}
