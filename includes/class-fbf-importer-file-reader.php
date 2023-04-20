<?php
/**
 * Class responsible for reading file.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */

class Fbf_Importer_File_Reader
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $filename;
    private $filepath;
    public $stock_num;
    private $mapping;
    private $batch;
    private $logger;

    public function __construct($plugin_name)
    {
        global $wp;
        $this->plugin_name = $plugin_name;
        $this->mapping = [
            'VariantCode' => 'Product Code',
            'StockQty' => 'Stock Qty',
            'PriceExcTax' => 'RSP Exc Vat',
            'AvgCost' => 'Cost Price',
            'Weight' => 'Weight KG',
            'c1' => 'Load/Speed Rating',
            'c2' => 'Brand Name',
            'c3' => 'Model Name',
            'c4' => 'Tyre Type',
            'c5' => 'Accessory Type',
            'c6' => 'Tyre Vehicle Specific',
            'c7' => 'Tyre Width',
            'c8' => 'Image name',
            'c9' => 'Tyre Size',
            'c10' => 'Tyre Profile',
            'n2' => 'List on eBay',
            'l1' => 'Three Peaks',
            'l3' => 'Tyre XL',
            'l4' => 'Tyre White Lettering',
            'l5' => 'Tyre Runflat',
            'l6' => 'Mud Snow',
            'l7' => 'Fit On Drive',
            'l8' => '360 Degree Photo',
            'l9' => 'Wheel TUV',
            'l10' => 'Include in Price Match',
            'm1' => 'EC Label Fuel',
            'm2' => 'Wheel Tyre Accessory',
            'm3' => 'Wheel Size',
            'm4' => 'Wheel Width',
            'm5' => 'Wheel Colour',
            'm6' => 'Wheel Load Rating',
            'm7' => 'Wheel Offset',
            'm8' => 'EC Label Wet Grip',
            'm9' => 'Tyre Label Noise',
            'm10' => 'Wheel PCD',
            'n1' => 'Ebay Price',
            'n3' => 'Centre Bore',
            'Length' => 'Length CM',
            'Width' => 'Width CM',
            'Depth' => 'Depth CM',
            'EAN' => 'EAN',
            'Suppliers' => [
                'Name' => 'Supplier Name',
                'Cost' => 'Supplier Cost Price',
                'StockQty' => 'Supplier Stock Qty',
                'LeadTime' => 'Supplier Lead Time',
                'ID' => 'Supplier ID',
                'l2' => 'Main Supplier'
            ],
            'PurchaseOrders' => [
                'Name' => 'PO Name',
                'PONumber' => 'PO Number',
                'FreeStockQty' => 'PO Free Stock',
                'PromisedDate' => 'PO Promised Date'
            ]
        ];
        $this->batch = get_option($this->option_name . '_batch', 1000);

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-logger.php';
        $this->logger = new Fbf_Importer_Logger($this->plugin_name);
    }

    public function check_file_uploaded()
    {
        $this->filename = get_option($this->option_name . '_file');
        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/' . $this->filename;
        }else{
            $this->filepath = ABSPATH . '../../supplier/' . $this->filename;
        }

        if(file_exists($this->filepath)){
            $file_m_time = filemtime($this->filepath);
            $current_time = time();
            $age_in_seconds = $current_time - $file_m_time;

            if($age_in_seconds > (2 * MINUTE_IN_SECONDS)){ // Check that file is older than 2 minutes
                $log_id = $this->logger->start();
                $this->process_file($log_id);
            }else{
                echo 'Still uploading';
            }
        }
    }

    private function process_file($log_id)
    {
        update_option($this->plugin_name, ['status' => 'PROCESSINGXML', 'log_id' => $log_id]);

        // For logging
        $dt = new DateTime();
        $tz = new DateTimeZone("Europe/London");
        $dt->setTimezone($tz);
        $start = $dt->getTimestamp();
        $log_info = [
            'start' => $start,
            'items' => 0,
            'white_letter_tyres' => 0
        ];


        global $wpdb;
        // Firstly rename the file by adding timestamp and delete original
        $file_info = pathinfo($this->filepath);
        $ts = time();
        $new_file = $file_info['dirname'] . '/' . $file_info['filename'] . '_' . $ts . '.' . $file_info['extension'];
        if(rename($this->filepath, $new_file)){
            // Empty the tmp_product database
            $table_name = $wpdb->prefix . 'fbf_importer_tmp_products';
            $delete = $wpdb->query("TRUNCATE TABLE $table_name");

            // Now we can move the XML to the tmp database
            $path = $file_info['dirname'] . '/' . $file_info['filename'] . '_' . $ts . '.' . $file_info['extension'];
            $log_info['file'] = $file_info['filename'] . '_' . $ts . '.' . $file_info['extension'];

            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-dom-validator.php';
            $validator = new Fbf_Importer_Dom_Validator();
            if($validator->validateFeeds($path)){
                $xml = new XMLReader();
                $doc = new DOMDocument;
                $xml->open($path);
                while($xml->read()){
                    if($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'Variant'){
                        $this->stock_num+=1;
                        $log_info['items']++;
                        $node = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $item = $this->map_item($node);
                        $insert = $wpdb->insert(
                            $table_name,
                            [
                                'batch' => ceil($this->stock_num/$this->batch),
                                'sku' => $item['Product Code'],
                                'item' => serialize($item)
                            ]
                        );

                        // Handle white lettering
                        if(key_exists('Tyre White Lettering', $item)){
                            $is_white_lettering = (string) $item['Tyre White Lettering']==='True';
                            if($is_white_lettering){
                                // Set the original item to white lettering = False
                                $log_info['white_letter_tyres']++;
                                $black_lettering_item = $item;
                                $black_lettering_item['Tyre White Lettering'] = 'False';
                                $update = $wpdb->update(
                                    $table_name,
                                    [
                                        'item' => serialize($black_lettering_item)
                                    ],
                                    [
                                        'id' => $wpdb->insert_id
                                    ]
                                );

                                $this->stock_num+=1;
                                $item_white = $item;
                                $item_white['Product Code'] = $item_white['Product Code'] . '_white';

                                if(!empty($item_white['Image name'])){
                                    $white_image_info = pathinfo($item_white['Image name']);
                                    $item_white['Image name'] = $white_image_info['filename'] . '_white.' . $white_image_info['extension'];
                                }else{
                                    $p = 1;
                                }

                                $insert_white = $wpdb->insert(
                                    $table_name,
                                    [
                                        'batch' => ceil($this->stock_num/$this->batch),
                                        'sku' => $item_white['Product Code'],
                                        'item' => serialize($item_white),
                                        'is_white_lettering' => $is_white_lettering,
                                    ]
                                );
                            }
                        }
                    }
                }
            }

            // Move the file
            $new_file_info = pathinfo($new_file);
            $moved_file = $new_file_info['dirname'] . '/imported_stock/' . $new_file_info['filename'] . '.' . $new_file_info['extension'];
            rename($new_file, $moved_file);
        }

        $id = $this->logger->log_info('processingxml', $log_info, $log_id);
        update_option($this->plugin_name, ['status' => 'READYTOPROCESS', 'batch' => 1, 'log_id' => $id]);
    }

    private function map_item(SimpleXMLElement $node)
    {
        $item = [];
        foreach($this->mapping as $map_key => $map_label){
            if(isset($node->{$map_key})){

                $data = (string) $node->{$map_key};
                $is_zero = $data==="0";
                $has_data = !empty($data);

                if($has_data||$is_zero){
                    if($map_key != 'Suppliers' && $map_key != 'PurchaseOrders' && $map_key != 'VariantCode' && $map_key != 'c8' && $map_key != 'l4'){
                        $item[$map_label] = (string) $node->{$map_key};
                    }else{
                        if($map_key == 'VariantCode' || $map_key == 'c8' || $map_key == 'l4'){
                            $item[$map_label] = (string) $node->{$map_key};
                        }else if($map_key == 'Suppliers'){
                            //parse suppliers here
                            $suppliers = $node->xpath('Suppliers/Supplier');
                            $supplier_mapping = $this->mapping[$map_key];
                            $supplier_data = [];
                            foreach($suppliers as $supplier){
                                foreach($supplier_mapping as $supplier_map_key => $supplier_map_label){
                                    if(!empty($supplier->{$supplier_map_key})){
                                        $supplier_data[$supplier_map_label] = (string) $supplier->{$supplier_map_key};
                                    }
                                }
                                $item[$map_key][] = $supplier_data;
                            }
                        }else if($map_key == 'PurchaseOrders'){
                            //parse suppliers here
                            $purchase_orders = $node->xpath('PurchaseOrders/PurchaseOrder');
                            $purchase_order_mapping = $this->mapping[$map_key];
                            $purchase_order_data = [];
                            foreach($purchase_orders as $purchase_order) {
                                foreach ($purchase_order_mapping as $purchase_order_map_key => $purchase_order_map_label) {
                                    if (!empty($purchase_order->{$purchase_order_map_key})) {
                                        $purchase_order_data[$purchase_order_map_label] = (string) $purchase_order->{$purchase_order_map_key};
                                    }
                                }
                                $item[$map_key][] = $purchase_order_data;
                            }
                        }
                    }
                }
            }
        }
        return $item;
    }
}
