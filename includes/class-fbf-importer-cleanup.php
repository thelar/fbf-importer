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
    private static $sku_file = 'sku_xml.xml';
    private $rsp = [];
    public $info = [];
    private $log_id;
    private $logger;

    public function __construct($plugin_name)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->tmp_products_table = $wpdb->prefix . 'fbf_importer_tmp_products';
        $this->products_to_hide = $this->setup_products_to_hide();
        $this->log_id = get_option($this->plugin_name)['log_id'];

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-logger.php';
        $this->logger = new Fbf_Importer_Logger($this->plugin_name);
    }

    public function clean()
    {
        global $wpdb;

        $dt = new DateTime();
        $tz = new DateTimeZone("Europe/London");
        $dt->setTimezone($tz);
        $start = $dt->getTimestamp();

        // Set status to CLEANING
        update_option($this->plugin_name, ['status' => 'CLEANING']);

        $log_info = [
            'start' => $start,
        ];

        //Loop through $this->stages executing each in turn and fail and return if any errors occur
        foreach($this->stages as $stage) {
            $stage_start = microtime(true);
            $this->stage = $stage;

            update_option($this->plugin_name, ['status' => 'CLEANING', 'stage' => $stage]);
            $this->{$stage}($log_info);
            $stage_end = microtime(true);
            $exec_time = $stage_end - $stage_start;
        }


        // Remove option
        delete_option($this->plugin_name);
    }

    private function setup_products_to_hide()
    {
        global $wpdb;
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

        $q = 'SELECT product_id FROM ' . $this->tmp_products_table;
        $ids = $wpdb->get_col($q);

        foreach($this->products_to_hide as $k => $id){ // Loop through all Woo product_ids
            if(in_array($id, $ids)){
                // $id is IS in XML basically - remove it from products to hide
                unset($this->products_to_hide[$k]);
            }
        }

        return $this->products_to_hide;
    }

    private function hide_products($log_info)
    {
        $i = 1;
        foreach($this->products_to_hide as $hide_id){
            $option = get_option($this->plugin_name);
            $option['num_items'] = count($this->products_to_hide);
            $option['current_item'] = $i;
            update_option($this->plugin_name, $option);

            $status = [];
            $status['action'] = 'Hide';
            $product_to_hide = new WC_Product($hide_id);
            $sku = $product_to_hide->get_sku();
            $name = $product_to_hide->get_title();

            $product_to_hide->set_catalog_visibility('hidden');
            $product_to_hide->set_stock_quantity(0); // Removes ability to sell product
            $product_to_hide->set_backorders('no');
            $product_to_hide->save();

            $i++;
        }
        $log_info+= ['hidden' => $i - 1];
        $this->logger->log_info($this->stage, $log_info, $this->log_id);
    }

    private function hide_products_without_images($log_info)
    {
        $all = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [ //Added to exclude packages
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => ['package', 'tyre'],
                    'operator' => 'NOT IN'
                ],
                [
                    'taxonomy'  => 'product_visibility',
                    'field'     => 'name',
                    'terms'     => ['exclude-from-catalog'],
                    'operator'  => 'NOT IN',
                ]
            ]
        ]);

        $i = 1;
        foreach($all as $pid){
            $sku = get_post_meta($pid, '_sku', true);
            $status = [];
            $status['action'] = 'Hide';
            if(get_post_thumbnail_id($pid)===0){
                // No thumbnail ID - hide the product
                $product_to_hide = new WC_Product($pid);
                $name = $product_to_hide->get_title();

                $product_to_hide->set_catalog_visibility('hidden');
                $product_to_hide->set_stock_quantity(0); // Removes ability to sell product
                $product_to_hide->set_backorders('no');
                if(!$product_to_hide->save()){
                    $status['errors'] = 'Could not ' . wc_strtolower($status['action']) . ' ' . $name;
                }
                $this->info['import_stock']['stock_status'][$sku] = $status;
                $i++;
            }
        }
        $log_info+= ['hidden' => $i - 1];
        $this->logger->log_info($this->stage, $log_info, $this->log_id);
    }

    private function write_rsp_xml($log_info)
    {
        global $wpdb;
        $q = "SELECT rsp FROM {$this->tmp_products_table} WHERE rsp IS NOT NULL";
        $rsp = $wpdb->get_col($q);
        foreach($rsp as $data){
            $this->rsp[] = unserialize($data);
        }

        $xml = new DOMDocument();
        $root = $xml->createElement("Variants");
        $xml->appendChild($root);
        foreach($this->rsp as $node){
            $variant = $xml->createElement("Variant");
            $variant_code = $xml->createElement("Variant_Code", $node['Variant_Code']);
            $RSP_Inc = $xml->createElement("RSP_Inc", $node['RSP_Inc']);
            $price_match = $xml->createElement("Price_Match", (string)$node['Price_Match']);
            $variant->appendChild($variant_code);
            $variant->appendChild($RSP_Inc);
            $variant->appendChild($price_match);
            $root->appendChild($variant);
        }
        if(function_exists('get_home_path')){
            $xml->save(get_home_path() . '../supplier/' . self::$sku_file);
        }else{
            $xml->save(ABSPATH . '../../supplier/' . self::$sku_file);
        }
        $this->logger->log_info($this->stage, $log_info, $this->log_id, true);
    }
}
