<?php

class Fbf_Importer_Remove_Variable_Products
{
    private $plugin_name;
    private $option_name = 'fbf_importer';
    private $xml;
    private $doc;
    public $errors = [];
    public $info = [];
    private $filename;
    private $filepath;
    public $stock;
    public $stock_num;
    private $mapping;
    private $variable_products = [];

    public function __construct($plugin_name)
    {
        $this->plugin_name = $plugin_name;
        $this->filename = get_option($this->option_name . '_file');
        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/' . $this->filename;
        }else{
            $this->filepath = ABSPATH . '../../supplier/' . $this->filename;
        }
        $this->xml = new XMLReader();
        $this->doc = new DOMDocument;
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
            'c5' => 'Tyre Quality',
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
    }

    private function build_stock_array()
    {
        $this->xml->open($this->filepath);
        while($this->xml->read()){
            if($this->xml->nodeType == XMLReader::ELEMENT && $this->xml->name == 'Variant'){
                $this->stock_num+=1;
                $node = simplexml_import_dom($this->doc->importNode($this->xml->expand(), true));
                $item = $this->map_item($node);
                $this->stock[] = $item;
            }
        }
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
                    if($map_key != 'Suppliers' && $map_key != 'PurchaseOrders'){
                        $item[$map_label] = $node->{$map_key};
                    }else{
                        if($map_key == 'Suppliers'){
                            //parse suppliers here
                            $suppliers = $node->xpath('Suppliers/Supplier');
                            $supplier_mapping = $this->mapping[$map_key];
                            $supplier_data = [];
                            foreach($suppliers as $supplier){
                                foreach($supplier_mapping as $supplier_map_key => $supplier_map_label){
                                    if(!empty($supplier->{$supplier_map_key})){
                                        $supplier_data[$supplier_map_label] = $supplier->{$supplier_map_key};
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
                                        $purchase_order_data[$purchase_order_map_label] = $purchase_order->{$purchase_order_map_key};
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

    private function get_variable_products()
    {
        foreach($this->stock as $item){
            $sku = (string)$item['Product Code'];
            if ($product_id = wc_get_product_id_by_sku($sku)) {
                $product = wc_get_product($product_id);

                if($product && $product->is_type('variable')){
                    $this->variable_products[] = $product_id;
                }
            }
        }
    }

    public function run()
    {
        $this->build_stock_array();
        $this->get_variable_products();

        $results = [];

        if(!empty($this->variable_products)){
            // Delete them here
            foreach($this->variable_products as $del_id){
                $results[$del_id] = $this->wh_deleteProduct($del_id, true);
            }
        }

        var_dump($results);
    }

    /**
     * Method to delete Woo Product - https://stackoverflow.com/questions/46874020/delete-a-product-by-id-using-php-in-woocommerce
     *
     * @param int $id the product ID.
     * @param bool $force true to permanently delete product, false to move to trash.
     * @return \WP_Error|boolean
     */
    public function wh_deleteProduct($id, $force = false)
    {
        $product = wc_get_product($id);

        if(empty($product))
            return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

        // If we're forcing, then delete permanently.
        if($force){
            if ($product->is_type('variable')){
                foreach ($product->get_children() as $child_id){
                    $child = wc_get_product($child_id);
                    $child->delete(true);
                }
            }elseif ($product->is_type('grouped')){
                foreach ($product->get_children() as $child_id){
                    $child = wc_get_product($child_id);
                    $child->set_parent_id(0);
                    $child->save();
                }
            }

            $product->delete(true);
            $result = $product->get_id() > 0 ? false : true;
        }else{
            $product->delete();
            $result = 'trash' === $product->get_status();
        }

        if(!$result){
            return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
        }

        // Delete parent product transients.
        if ($parent_id = wp_get_post_parent_id($id)){
            wc_delete_product_transients($parent_id);
        }
        return true;
    }
}
