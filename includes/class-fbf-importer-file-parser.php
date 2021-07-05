<?php
/**
 * Class responsible for reading file.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */

class Fbf_Importer_File_Parser {
    private $plugin_name;
    private $option_name = 'fbf_importer';
    public $errors = [];
    public $info = [];
    private $filename;
    private $filepath;
    private $xml;
    private $doc;
    private $stages = [
        'file_exists',
        'file_valid',
        'build_stock_array',
        'get_rsp_rules',
        'import_stock',
        'update_ebay_packages',
        'rotate_stock_files',
        'write_rsp_xml',
        'collate_suppliers'
    ];
    private $stage;
    public $stock;
    public $stock_num;
    private $mapping;
    private $rsp_rules;
    private $rsp = [];
    private $min_stock;
    private $flat_fee;
    private $suppliers;
    private $supplier_stock_errors;
    private $max_items = 10; //If set, processing will exit after this number of items, making it quick - for testing purposes
    private static $sku_file = 'sku_xml.xml';
    private $save_stock_files_to = 'imported_stock';
    private $days_to_keep = 7;

    public function __construct($plugin_name)
    {
        global $wp;
        $this->plugin_name = $plugin_name;
        $this->filename = get_option($this->option_name . '_file');

        if(function_exists('get_home_path')){
            $this->filepath = get_home_path() . '../supplier/' . $this->filename;
        }else{
            $this->filepath = ABSPATH . '../../supplier/' . $this->filename;
        }

        $this->xml = new XMLReader();
        $this->doc = new DOMDocument;
        $this->suppliers = $this->build_suppliers();
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
            'l3' => 'Tyre XL',
            'l4' => 'Tyre White Lettering',
            'l5' => 'Tyre Runflat',
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
                'ID' => 'Supplier ID'
            ],
            'PurchaseOrders' => [
                'Name' => 'PO Name',
                'PONumber' => 'PO Number',
                'FreeStockQty' => 'PO Free Stock',
                'PromisedDate' => 'PO Promised Date'
            ]
        ];
    }

    private function file_exists()
    {
        if(!file_exists($this->filepath)){
            //return [$this->filepath . ' - File does not exist'];
            $this->errors[$this->stage] = [$this->filepath . ' - File does not exist'];
            $this->info[$this->stage]['errors'] = [$this->filepath . ' - File does not exist'];
        }
    }

    private function file_valid()
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fbf-importer-dom-validator.php';
        $validator = new Fbf_Importer_Dom_Validator();
        if(!$validator->validateFeeds($this->filepath)){
            $this->errors[$this->stage] = $validator->errorDetails;
            $this->info[$this->stage]['errors'] = $validator->errorDetails;
        }

//        $this->xml->open($this->filepath);
//        $this->xml->setParserProperty(XMLReader::VALIDATE, true);
//        if(!$this->xml->isValid()){
//            $this->errors[$this->stage] = [$this->filepath . ' - is not valid XML'];
//        }
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
        if(!is_array($this->stock) || !count($this->stock)){
            $this->errors[$this->stage] = ['Stock is either empty or not an Array'];
            $this->info[$this->stage]['errors'] = ['Stock is either empty or not an Array'];
        }
    }

    private function build_suppliers()
    {
        $suppliers_a = null;
        $suppliers = get_posts([
            'post_type' => 'suppliers',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        if(!empty($suppliers)){
            foreach($suppliers as $supplier_id){
                $suppliers_a[get_field('supplier_id', $supplier_id)] = get_field('lead_time', $supplier_id);
            }
        }
        return $suppliers_a;
    }

    private function import_stock()
    {
        $stock_status = [];
        $products_to_hide = get_posts([
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
        $counter = 0;


        foreach ($this->stock as $item) {
            $sku = (string)$item['Product Code'];
            $is_variable = false;

            $status = [];

            //VALIDATION - First check the necessary data is present:
            $mandatory = [
                'Brand Name',
                'Model Name',
                'Wheel Tyre Accessory',
                'List on eBay',
                'Include in Price Match',
                //'EAN' - doesn't seem to be present on all items
            ];
            if (isset($item['Wheel Tyre Accessory'])) {
                if ($item['Wheel Tyre Accessory'] == 'Tyre') {
                    //Its a Tyre
                    array_push($mandatory, 'Load/Speed Rating', 'Tyre Type', 'Tyre Quality', 'Tyre Width', 'Tyre Size', 'Tyre Profile', 'Tyre XL', 'Tyre White Lettering', 'Tyre Runflat');
                    $name = sprintf('%s/%s/%s %s %s %s', isset($item['Tyre Width']) ? $item['Tyre Width'] : '', isset($item['Tyre Profile']) ? $item['Tyre Profile'] : '', isset($item['Tyre Size']) ? $item['Tyre Size'] : '', isset($item['Brand Name']) ? $item['Brand Name'] : '', isset($item['Model Name']) ? $item['Model Name'] : '', isset($item['Load/Speed Rating']) ? $item['Load/Speed Rating'] : '');
                    $attrs = [
                        'Load/Speed Rating' => 'load-speed-rating',
                        'Brand Name' => 'brand-name',
                        'Model Name' => 'model-name',
                        'Tyre Type' => 'tyre-type',
                        'Tyre Quality' => 'tyre-quality',
                        'Tyre Vehicle Specific' => 'tyre-vehicle-specific',
                        'Tyre Width' => 'tyre-width',
                        'Tyre Size' => 'tyre-size',
                        'Tyre Profile' => 'tyre-profile',
                        'List on eBay' => [
                            'slug' => 'list-on-ebay',
                            'scope' => 'global',
                            'mapping' => [
                                '0.0000' => 'False',
                                '1.0000' => 'True'
                            ]
                        ],
                        'Tyre XL' => 'tyre-xl',
                        'Tyre White Lettering' => 'tyre-white-lettering',
                        'Tyre Runflat' => 'tyre-runflat',
                        '360 Degree Photo' => '360-degree-photo',
                        'Include in Price Match' => 'include-in-price-match',
                        'EC Label Fuel' => 'ec-label-fuel',
                        'EC Label Wet Grip' => 'ec-label-wet-grip',
                        'Tyre Label Noise' => 'tyre-label-noise',
                        'EAN' => [
                            'slug' => 'ean',
                            'scope' => 'local'
                        ]
                    ];
                    //White lettering?
                    if($item['Tyre White Lettering'] == 'True'){
                        $is_variable = true;
                    }
                } else if ($item['Wheel Tyre Accessory'] != 'Accessories') {
                    //It's a Wheel
                    array_push($mandatory, 'Wheel TUV', 'Wheel Size', 'Wheel Width', 'Wheel Colour', 'Wheel Load Rating', 'Wheel Offset', 'Wheel PCD');
                    $name = sprintf('%s x %s %s %s %s ET%s', isset($item['Wheel Size']) ? $item['Wheel Size'] : '', isset($item['Wheel Width']) ? $item['Wheel Width'] : '', isset($item['Brand Name']) ? $item['Brand Name'] : '', isset($item['Model Name']) ? $item['Model Name'] : '', isset($item['Wheel Colour']) ? $item['Wheel Colour'] : '', isset($item['Wheel Offset']) ? $item['Wheel Offset'] : '');
                    $attrs = [
                        'Brand Name' => 'brand-name',
                        'Model Name' => 'model-name',
                        'List on eBay' => [
                            'slug' => 'list-on-ebay',
                            'scope' => 'global',
                            'mapping' => [
                                '0.0000' => 'False',
                                '1.0000' => 'True'
                            ]
                        ],
                        'Wheel TUV' => 'wheel-tuv',
                        'Include in Price Match' => 'include-in-price-match',
                        'Wheel Size' => 'wheel-size',
                        'Wheel Width' => 'wheel-width',
                        'Wheel Colour' => 'wheel-colour',
                        'Wheel Load Rating' => 'wheel-load-rating',
                        'Wheel Offset' => 'wheel-offset',
                        'Wheel PCD' => 'wheel-pcd',
                        'Centre Bore' => 'centre-bore',
                        'EAN' => [
                            'slug' => 'ean',
                            'scope' => 'local'
                        ]
                    ];
                } else {
                    //It's an Accessory
                    $name = sprintf('%s %s', isset($item['Brand Name']) ? $item['Brand Name'] : '', isset($item['Model Name']) ? $item['Model Name'] : '');
                    $attrs = [
                        'Brand Name' => 'brand-name',
                        'Model Name' => 'model-name',
                        'List on eBay' => [
                            'slug' => 'list-on-ebay',
                            'scope' => 'global',
                            'mapping' => [
                                '0.0000' => 'False',
                                '1.0000' => 'True'
                            ]
                        ]
                    ];
                }
                $data_valid = $this->validate($item, $mandatory);
                if ($data_valid === true) {
                    //Data is valid
                    $status['data_valid'] = true;

                    //Does the product exist?
                    if ($product_id = wc_get_product_id_by_sku($sku)) {
                        //Check if we need to update the product
                        $status['action'] = 'Update';
                        if($is_variable){
                            $product = new WC_Product_Variable($product_id);
                        }else{
                            $product = new WC_Product($product_id);
                        }



                        //Delete the product id from $all_products so that it doesn't get set to invisible
                        $key = array_search($product->get_id(), $products_to_hide);
                        if ($key !== false) {
                            unset($products_to_hide[$key]);
                        }
                    } else {
                        //Create the product
                        $status['action'] = 'Create';
                        if($is_variable){
                            $product = new WC_Product_Variable();
                        }else{
                            $product = new WC_Product();
                        }
                    }

                    $product->set_name($name);
                    $this->add_to_yoast_seo($product_id, '', $name, '');

                    //Set the title in the post meta - this is for eBay Package searches and other searches where we need to filter by both SKU and Title
                    update_post_meta($product->get_id(), '_fbf_product_title', $name);

                    $product->set_sku($sku);
                    $product->set_catalog_visibility('visible');
                    //$product->set_regular_price(round((string)$item['RSP Exc Vat'], 2));

                    if($is_variable){
                        $ch = $product->get_children();
                        $product->set_price(round((string)$item['RSP Exc Vat'], 2));
                        if(is_array($ch)){
                            foreach($ch as $ch_i){
                                update_post_meta($ch_i, '_price', round((string)$item['RSP Exc Vat'], 2));

                                update_post_meta($ch_i, '_regular_price', round((string)$item['RSP Exc Vat'], 2));
                            }
                        }
                    }else{
                        $product->set_regular_price(round((string)$item['RSP Exc Vat'], 2));
                    }


                    //Category
                    if ($pc_id = $this->get_product_category($product, $item['Wheel Tyre Accessory'])) {
                        $product->set_category_ids([$pc_id]);
                    } else {
                        $status['errors'][] = 'Error setting product category';
                    }

                    //Attributes
                    $wc_attrs = $product->get_attributes();
                    $new_attrs = [];
                    foreach ($attrs as $ak => $av) {
                        if (isset($item[$ak])) {
                            try {
                                //If it's a tyre and has a key of 'Load/Speed Rating' need to split it out into Load and Speed
                                if($ak==='Load/Speed Rating'){
                                    preg_match('/[a-zA-Z]+/i', $item[$ak], $matches, PREG_OFFSET_CAPTURE);
                                    if(count($matches)===1){
                                        $pos = $matches[0][1];
                                        $load = substr($item[$ak], 0, $pos);
                                        $speed = substr($item[$ak], $pos);

                                        $new_load_attr = $this->check_attribute($product, 'tyre-load', $load, $wc_attrs);
                                        $new_attrs['pa_tyre-load'] = $new_load_attr;
                                        $new_speed_attr = $this->check_attribute($product, 'tyre-speed', $speed, $wc_attrs);
                                        $new_attrs['pa_tyre-speed'] = $new_speed_attr;

                                    }
                                }

                                //If it's a tyre and has a key of 'Tyre Size' then we need to add a further attribute for size label which is a combination of {tyre_width}/{tyre_profile}/{tyre_size}
                                if($ak==='Tyre Size'){
                                    $combined_size = sprintf('%s/%s/%s', (string)$item['Tyre Width'], (string)$item['Tyre Profile'], (string)$item['Tyre Size']);
                                    $new_size_attr = $this->check_attribute($product, 'tyre-size-label', $combined_size, $wc_attrs);
                                    $new_attrs['pa_tyre-size-label'] = $new_size_attr;
                                }

                                $new_attr = $this->check_attribute($product, $av, $item[$ak], $wc_attrs);
                                if ($new_attr) {
                                    if (is_array($av)) {
                                        $new_attrs['pa_' . $av['slug']] = $new_attr;
                                    } else {
                                        $new_attrs['pa_' . $av] = $new_attr;
                                    }
                                } else {
                                    $status['errors'][] = 'Check attribute returned false for ' . $av;
                                }
                            } catch (Exception $e) {
                                $status['errors'][] = $e->getMessage();
                            }
                        }
                    }

                    //White lettering
                    if($is_variable){
                        //Here if we need to add white lettering option basically
                        $variable_attribute = new WC_Product_Attribute();
                        $variable_attribute->set_id(0);
                        $variable_attribute->set_name('lettering');
                        $variable_attribute->set_options([
                            'Black Lettering',
                            'White Lettering'
                        ]);
                        $variable_attribute->set_position(0);
                        $variable_attribute->set_variation(1);

                        $new_attrs['lettering'] = $variable_attribute;
                    }

                    if (!empty($new_attrs) && !in_array(false, $new_attrs)) {
                        $product->set_attributes($new_attrs);
                    }



                    //Weight and dimensions
                    if (isset($item['Weight KG'])) {
                        $product->set_weight((string)$item['Weight KG']);
                    }
                    if (isset($item['Length CM'])) {
                        $product->set_length((string)$item['Length CM']);
                    }
                    if (isset($item['Width CM'])) {
                        $product->set_width((string)$item['Width CM']);
                    }
                    if (isset($item['Depth CM'])) {
                        $product->set_height((string)$item['Depth CM']);
                    }

                    //Stock level
                    $initial_stock = $product->get_stock_quantity();
                    $this->set_stock($product, $item);

                    $cat = $item['Wheel Tyre Accessory'];

                    //Set the back in stock date for everything - this is needed because we need to show it on low stock items as well as out of stock
                    $back_in_stock = $this->get_back_in_stock_date($product, $item);
                    if($back_in_stock && ((string)$cat=='Steel Wheel'||(string)$cat=='Alloy Wheel')){
                        $product->update_meta_data('_expected_back_in_stock_date', $back_in_stock);
                    }else{
                        $product->update_meta_data('_expected_back_in_stock_date', false);
                    }

                    //Dan request 1 Mar 2021 - need to exclude Steel wheels from 3 month rule
                    if((string)$cat=='Steel Wheel'){
                        $product->set_backorders('notify');
                        $product->update_meta_data('_went_out_of_stock_on', '');

                        // If the stock is back up to 4 or more - and the initial stock was less than or equal to 0 - it's just come back into stock - so mark accordingly
                        if($initial_stock <= 0 && $product->get_stock_quantity() >= 4){
                            $product->update_meta_data('_back_in_stock_date', time());
                        }
                    }else{
                        if($product->get_stock_quantity()<=0){
                            // Here if there isn't stock
                            $went_out_of_stock_on = $product->get_meta('_went_out_of_stock_on');

                            // Only set the out of stock date if it's currently empty
                            if(empty($went_out_of_stock_on)){
                                $product->update_meta_data('_went_out_of_stock_on', time());
                            }

                            // Set backordering based on when the product went out of stock - if it's been out of stock for
                            // more than 3 months, no backordering
                            $now = new DateTime('now');
                            $stock_date = new DateTime();
                            $stock_date->setTimestamp($product->get_meta('_went_out_of_stock_on'));

                            // Check whether product went out of stock 3 or more months ago (6 months for Tyres - requested by Dan 29th March 21)
                            if((string)$cat=='Tyre'){
                                $months = 6;
                            }else{
                                $months = 3;
                            }
                            if($stock_date->diff($now)->m >= $months){
                                $product->set_backorders('no');
                            }else{
                                $product->set_backorders('notify');
                            }
                        }else{
                            // Here if there is stock
                            $product->update_meta_data('_went_out_of_stock_on', '');
                            $product->set_backorders('notify');

                            // If the stock is back up to 4 or more - and the initial stock was less than or equal to 0 - it's just come back into stock - so mark accordingly
                            if($initial_stock <= 0 && $product->get_stock_quantity() >= 4){
                                $product->update_meta_data('_back_in_stock_date', time());
                            }
                        }
                    }


                    // Add tyre shipping class to tyres
                    if($shipping_class_id = WC_Product_Data_Store_CPT::get_shipping_class_id_by_slug('tyre')){
                        if ($item['Wheel Tyre Accessory'] == 'Tyre'){
                            $product->set_shipping_class_id($shipping_class_id);
                        }
                    }

                    // Add spacer shipping class to spacers
                    if($shipping_class_id = WC_Product_Data_Store_CPT::get_shipping_class_id_by_slug('spacer')) {
                        $match = preg_match('/^TTSPACE.*$/i', $sku); //If the SKU begins with TTSPACE
                        if ($match === 1) {
                            $product->set_shipping_class_id($shipping_class_id);
                        }
                    }

                    /*if($cat=='Alloy Wheel'||$cat=='Steel Wheel'){ // For now just turn backordering on for wheels
                        $product->set_backorders('notify');
                    }else{
                        $product->set_backorders('no');
                    }*/

                    if (!$product_id = $product->save()) {
                        $status['errors'][] = 'Could not ' . wc_strtolower($status['action']) . ' ' . $name;
                    } else {

                        //White lettering available
                        if($is_variable){
                            //Does the product have any children (variants)
                            $children = $product->get_children();
                            $white_lettering_attrs = [];
                            if(is_array($children)){
                                foreach($children as $child){
                                    $var = new WC_Product_Variation($child);
                                    $var_attrs = $var->get_variation_attributes();
                                    if(array_key_exists('attribute_lettering', $var_attrs)){
                                        $white_lettering_attrs[] = $var_attrs['attribute_lettering'];
                                    }
                                }
                            }

                            if(!in_array('White Lettering', $white_lettering_attrs)){
                                $variation_yes = new WC_Product_Variation();
                                $variation_yes->set_regular_price((string)$item['RSP Exc Vat']);
                                $variation_yes->set_parent_id($product_id);
                                $variation_yes->set_attributes(array(
                                    'lettering' => 'White Lettering', // -> removed 'pa_' prefix
                                ));
                                $variation_yes->save();
                            }

                            if(!in_array('Black Lettering', $white_lettering_attrs)){
                                $variation_no = new WC_Product_Variation();
                                $variation_no->set_regular_price((string)$item['RSP Exc Vat']);
                                $variation_no->set_parent_id($product_id);
                                $variation_no->set_attributes(array(
                                    'lettering' => 'Black Lettering', // -> removed 'pa_' prefix
                                ));
                                $variation_no->save();
                            }

                            $default_attrs = [
                                'lettering' => 'Black Lettering'
                            ];
                            update_post_meta($product_id, '_default_attributes', $default_attrs);
                        }

                        //Store the stockist array for lead times
                        update_post_meta($product_id, '_stockist_lead_times', $this->get_supplier_lead_times($product, $item));

                        //Product saved - handle the product image
                        include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-product-image.php';
                        if (isset($item['Image name'])) {
                            $image_handler = new Fbf_Importer_Product_Image($product_id, (string)$item['Image name']);
                            $image_import = $image_handler->process($status['action']);
                            if (isset($image_import['errors'])) {
                                $status['errors'] = $image_import['errors'];
                            } else {
                                $status['image_info'] = $image_import['info'];
                            }
                        }
                    }

                    // Add meta for profit margin
                    $cost = $this->get_cost($product, $item, $this->min_stock);
                    if(!empty($cost)){
                        if($item['Wheel Tyre Accessory'] != 'Accessories'){
                            $delivery_cost = $this->flat_fee;
                        }else{
                            $delivery_cost = 0;
                        }
                        if($cost['code'] > 1){
                            $status['errors'][] = 'Profit margin error code ' . $cost['code'] . ': ' . $cost['msg'];
                            /*if($cost['code'] === 2){
                                update_post_meta($product_id, '_item_cost', $cost['cost'] + $delivery_cost);
                            }*/
                        }else{
                            $status['margin'] = 'Profit margin set to: ' . $cost['cost'];
                            update_post_meta($product_id, '_item_cost', $cost['cost'] + $delivery_cost);
                        }
                    }else{
                        $status['errors'][] = 'Profit margin error code 0: $cost was empty';
                    }

                    /*    update_post_meta($product_id, '_item_cost', $cost);
                    } catch (Throwable $e) {
                        $status['errors'][] = $e->getMessage();
                    }*/

                    //RSP calculation
                    if ($item['Wheel Tyre Accessory'] == 'Tyre') {
                        if((string)$item['Include in Price Match']=='True'){
                            // $rsp_price = round($this->get_rsp($item, $product_id, $is_variable ? (float)wc_get_product($children[0])->get_regular_price() : (float)$product->get_regular_price()) * 1.2,2); //Added vat here, 12-05-20 dealt with sending regular price of variant

                            if($is_variable){
                                if(isset($children[0]) && wc_get_product($children[0])!==false){
                                    $reg_price = wc_get_product($children[0])->get_regular_price();
                                }else{
                                    $reg_price = $product->get_price();
                                }
                                $rsp_price = round($this->get_rsp($item, $product_id, (float)$reg_price) * 1.2, 2);
                            }else{
                                $rsp_price = round($this->get_rsp($item, $product_id, (float)$product->get_regular_price()) * 1.2, 2);
                            }
                        }else{
                            $rsp_price = round((float)$item['RSP Exc Vat'] * 1.2, 2); //Added vat here
                        }

                        //Handle zero here - throw a warning and don't add to RSP
                        if($rsp_price!==(float)0){
                            $this->rsp[] = [
                                'Variant_Code' => $sku,
                                'RSP_Inc' => $rsp_price
                            ];
                        }else{
                            $status['errors'][] = 'RSP was calculated as zero';
                            //Just set the RSP to the PriceExcVat in the stock file
                            $this->rsp[] = [
                                'Variant_Code' => $sku,
                                'RSP_Inc' => round((float)$item['RSP Exc Vat'] * 1.2, 2)
                            ];
                        }
                    }



                } else {
                    //Data is not valid
                    $status['data_valid'] = false;
                    $status['errors'] = $data_valid;
                }
                $stock_status[$sku] = $status;
            } else {
                $status['errors'][] = 'Category is not set';
            }
            $counter++;
        }

        //Loop through the remaining $products_to_hide and set visibility to hidden
        foreach($products_to_hide as $hide_id){
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
            $stock_status[$sku] = $status;
        }
        $this->info[$this->stage]['stock_status'] = $stock_status;
    }

    private function update_ebay_packages()
    {
        $packages = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [ //Added to exclude packages
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => ['package'],
                    'operator' => 'IN'
                ]
            ]
        ]);

        //Loop through the packages
        if(is_plugin_active('fbf-ebay-packages/fbf-ebay-packages.php')){
            foreach($packages as $package){
                $update = Fbf_Ebay_Packages_Admin::update_package($package);
            }
        }
    }

    private function rotate_stock_files()
    {
        if(function_exists('get_home_path')){
            $read_path = get_home_path() . '../supplier/' . $this->save_stock_files_to . '/';
            $save_path = get_home_path() . '../supplier/' . $this->save_stock_files_to . '/imported_' .date('m-d-Y_hia') . '.xml';
        }else{
            $read_path = ABSPATH . '../../supplier/' . $this->save_stock_files_to . '/';
            $save_path = ABSPATH . '../../supplier/' . $this->save_stock_files_to . '/imported_' .date('m-d-Y_hia') .
                '.xml';
        }
        $copy = copy($this->filepath, $save_path);

        //Now delete files older than $days_to_keep
        if($h = opendir($read_path)){
            while(false !== ($file = readdir($h))){
                if (is_file($read_path.$file)){
                    if (filemtime($read_path.$file) < ( time() - ( $this->days_to_keep * 24 * 60 * 60 ) ) )
                    {
                        // Do the deletion
                        unlink($read_path.$file);
                    }
                }
            }
        }
    }

    private function write_rsp_xml()
    {
        $xml = new DOMDocument();
        $root = $xml->createElement("Variants");
        $xml->appendChild($root);
        foreach($this->rsp as $node){
            $variant = $xml->createElement("Variant");
            $variant_code = $xml->createElement("Variant_Code", $node['Variant_Code']);
            $RSP_Inc = $xml->createElement("RSP_Inc", $node['RSP_Inc']);
            $variant->appendChild($variant_code);
            $variant->appendChild($RSP_Inc);
            $root->appendChild($variant);
        }
        if(function_exists('get_home_path')){
            $xml->save(get_home_path() . '../supplier/' . self::$sku_file);
        }else{
            $xml->save(ABSPATH . '../../supplier/' . self::$sku_file);
        }
    }

    private function collate_suppliers()
    {
        $e = $this->supplier_stock_errors;
        return $e;
    }

    private function get_rsp_rules()
    {
        if(!is_plugin_active('fbf-rsp-generator/fbf-rsp-generator.php')){
            $this->errors[$this->stage] = ['RSP Generator plugin - not active'];
            $this->info[$this->stage]['errors'] = ['RSP Generator plugin - not active'];
        }else {
            $this->rsp_rules = Fbf_Rsp_Generator_Admin::fbf_rsp_generator_generate_rules();
            $this->min_stock = get_option('fbf_rsp_generator_min_stock');
            $this->flat_fee = get_option('fbf_rsp_generator_flat_fee');
        }
    }

    public static function fbf_importer_file_parser_read_xml()
    {
        $file = file_get_contents(get_home_path() . '../supplier/' . self::$sku_file);
        echo $file;
    }

    private function get_rsp($item, $product_id, $price)
    {
        $s_price = $this->get_supplier_cost($item, $price);
        $pc = 0;

        if($s_price === (float)0){
            return $s_price; //Return with zero so we can catch error
        }

        if($s_price != $price){
            //1. Loop through the rules
            foreach ($this->rsp_rules as $rule) {
                if ($this->does_rule_apply($rule, $product_id)) {
                    $pc = $rule['amount'];
                    break;
                } else {
                    $pc = 0;
                }
            }
        }
        if($pc){
            return (($pc/100) * $s_price) + $s_price + $this->flat_fee;
        }else{
            return $price;
        }
    }

    private function get_supplier_cost($item, $price)
    {
        if((int) $item['Stock Qty'] >= 2){
            //Here we are going to look at the AvgPrice and put in a failsafe
            $failsafe = (float)30;
            if((float)$item['Cost Price'] < $failsafe){
                return $price;
            }else{
                //return (float)$item['Cost Price'];
                // Return cheapest supplier with stock or Avg whichever is less
                $cheapest = (float)$item['Cost Price'];
                if(isset($item['Suppliers'])){
                    foreach($item['Suppliers'] as $supplier){
                        if((int) $supplier['Supplier Stock Qty'] >= $this->min_stock){
                            if($cheapest===null){
                                $cheapest = (float) $supplier['Supplier Cost Price'];
                            }else{
                                if((float) $supplier['Supplier Cost Price'] < $cheapest){
                                    $cheapest = (float) $supplier['Supplier Cost Price'];
                                }
                            }

                        }
                    }
                }
                return $cheapest;
            }
        }
        //Get the cheapest supplier with at least the $min_stock
        $cheapest = null;
        if(isset($item['Suppliers'])){
            foreach($item['Suppliers'] as $supplier){
                if((int) $supplier['Supplier Stock Qty'] >= $this->min_stock){
                    if($cheapest===null){
                        $cheapest = (float) $supplier['Supplier Cost Price'];
                    }else{
                        if((float) $supplier['Supplier Cost Price'] < $cheapest){
                            $cheapest = (float) $supplier['Supplier Cost Price'];
                        }
                    }

                }
            }
        }
        if($cheapest===null){
            return $price;
        }else{
            return $cheapest;
        }
    }

    private function get_cost($product, $item, $min){
        // $failsafe = 30;
        $result = [];
        if((int) $item['Stock Qty'] >= $min){
            $cost = (float)$item['Cost Price'];
        }else{
            //Get the cheapest supplier with at least the $min_stock
            $cost = null;
            if(isset($item['Suppliers'])){
                foreach($item['Suppliers'] as $supplier){
                    if((int) $supplier['Supplier Stock Qty'] >= $this->min_stock){
                        if($cost===null){
                            $cost = (float) $supplier['Supplier Cost Price'];
                        }else{
                            if((float) $supplier['Supplier Cost Price'] < $cost){
                                $cost = (float) $supplier['Supplier Cost Price'];
                            }
                        }
                    }
                }

                //If we get here and $cost is still null look for cheapest supplier but WITHOUT stock
                if($cost===null){
                    foreach($item['Suppliers'] as $supplier){
                        if($cost===null){ // First time
                            if((float) $supplier['Supplier Cost Price'] > 0){
                                $cost = (float) $supplier['Supplier Cost Price'];
                            }
                        }else{
                            if((float) $supplier['Supplier Cost Price'] < $cost){
                                if((float) $supplier['Supplier Cost Price'] > 0) {
                                    $cost = (float)$supplier['Supplier Cost Price'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if($cost===null){
            $result = [
                'code' => 4,
                'cost' => (float)$item['Cost Price'],
                'msg' => 'Cost is null'
            ];
        }else if($cost == 0){
            $result = [
                'code' => 5,
                'cost' => $cost,
                'msg' => 'Cost is 0'
            ];
        }else{
            $result = [
                'code' => 1,
                'cost' => $cost,
                'msg' => 'In stock'
            ];
        }

        return $result;
    }

    private function get_supplier_lead_times($product, $item)
    {
        $suppliers = [];
        if(array_key_exists('Suppliers', $item)){
            foreach($item['Suppliers'] as $item_supplier){
                $supplier_id = (string)$item_supplier['Supplier ID'];
                $supplier_cost = (string)$item_supplier['Supplier Cost Price'];
                if(array_key_exists('Supplier Lead Time', $item_supplier)){
                    $supplier_lead_time = (string)$item_supplier['Supplier Lead Time'];
                }else{
                    $supplier_lead_time = '0';
                }
                $supplier_stock = (int)$item_supplier['Supplier Stock Qty'];
                $supplier_name = (string)$item_supplier['Supplier Name'];

                if($supplier_stock > 0){
                    if(array_key_exists($supplier_id, $this->suppliers) && !empty($this->suppliers[$supplier_id])){
                        $supplier_lead_time = $this->suppliers[$supplier_id];
                    }

                    $suppliers[$supplier_id] = [
                        'stock' => $supplier_stock,
                        'cost' => $supplier_cost,
                        'lead_time' => $supplier_lead_time,
                        'name' => $supplier_name
                    ];

                    if((int)$supplier_lead_time===0){
                        $this->supplier_stock_errors[$supplier_id][] = [
                            'sku' => $product->get_sku(),
                            'stock' => $supplier_stock,
                        ];
                    }
                }
            }
        }
        return $suppliers;
    }

    private function does_rule_apply($rule, $id)
    {
        //If the rule has taxonomy terms
        if(!is_null($rule['rules'])){
            //Loop through all the taxonomy terms and see if the item has them - note it needs all of them for the rule to apply
            foreach($rule['rules'] as $taxonomy => $term){
                if(!has_term($term, $taxonomy, $id)){
                    return false;
                }
            }
            return true;
        }else{
            return true;
        }
    }

    private function validate($item, $checks)
    {
        foreach($checks as $check){
            if(isset($item[$check])){
                if(empty((string) $item[$check]) && (string) $item[$check]!=="0"){
                    $errors[] = $check . ' is not set';
                }
            }else{
                $errors[] = $check . ' is not set (isset)';
            }
        }
        if(isset($errors)){
            return $errors;
        }else{
            return true;
        }
    }

    private function set_stock(WC_Product $product, $item)
    {
        $qty = 0;
        $fbf_qty = 0;
        $supplier_qty = 0;
        $product->set_manage_stock(true);
        if(isset($item['Stock Qty'])&&(int) $item['Stock Qty']>0){
            $product->update_meta_data('_instock_at_fbf', 'yes'); //Need this for next day delivery option
            $product->update_meta_data('_fbf_stock', (int) $item['Stock Qty']);
        }else{
            $product->update_meta_data('_instock_at_fbf', 'no'); //Need this for next day delivery option
            $product->update_meta_data('_fbf_stock', 0);
        }

        //Stock here - grand total of 4x4 AND all supplier stock
        $fbf_qty+= (int) $item['Stock Qty'];
        if(isset($item['Suppliers'])){
            foreach($item['Suppliers'] as $supplier){
                $supplier_qty+= (int) $supplier['Supplier Stock Qty'];
            }
        }
        $product->set_stock_quantity($fbf_qty + $supplier_qty);
    }

    private function get_back_in_stock_date(WC_Product $product, $item)
    {
        $today = new DateTime();
        if(array_key_exists('PurchaseOrders', $item)){
            foreach($item['PurchaseOrders'] as $purchaseOrder){
                $po_free_stock = (string)$purchaseOrder['PO Free Stock'];
                $po_date = new DateTime((string)$purchaseOrder['PO Promised Date']);

                if($po_date>$today){
                    if((!isset($earliest) || $po_date<$earliest) && $po_free_stock >= 5){
                        $earliest = $po_date;
                    }
                }
            }
        }
        if(!is_null($earliest)){
            return $earliest->format('Y-m-d');
        }else{
            return false;
        }
    }

    private function get_name($item)
    {
        $errors = [];
        if(!empty((string) $item['Brand Name'])){
            $brand_name = (string) $item['Brand Name'];
        }else{
            $errors[] = 'Brand name not set';
        }
        if(!empty((string) $item['Model Name'])){
            $model_name = (string) $item['Model Name'];
        }else{
            $errors[] = 'Model name not set';
        }
        if((string) $item['Wheel Tyre Accessory']=='Tyre'){
            if(!empty((string) $item['Tyre Width'])){
                $tyre_width = (string) $item['Tyre Width'];
            }else{
                $errors[] = 'Tyre width not set';
            }
            if(!empty((string) $item['Tyre Profile'])){
                $tyre_profile = (string) $item['Tyre Profile'];
            }else{
                $errors[] = 'Tyre profile not set';
            }
            if(!empty((string) $item['Tyre Size'])){
                $tyre_size = (string) $item['Tyre Size'];
            }else{
                $errors[] = 'Tyre size not set';
            }
            if(!empty((string) $item['Load/Speed Rating'])){
                $load_speed_rating = (string) $item['Load/Speed Rating'];
            }else{
                $errors[] = 'Load speed rating not set';
            }
            if(!empty($errors)){
                return [
                    'data_present' => false,
                    'errors' => $errors
                ];
            }else{
                return [
                    'data_present' => true,
                    'title' => $tyre_width . '/' . $tyre_profile . '/' . $tyre_size . ' ' . $brand_name . ' ' . $model_name . ' ' . $load_speed_rating
                ];
            }
        }else if((string) $item['Wheel Tyre Accessory']=='Accessories'){
            if(!empty($errors)){
                return [
                    'data_present' => false,
                    'errors' => $errors
                ];
            }else{
                return [
                    'data_present' => true,
                    'title' => $brand_name . ' ' . $model_name
                ];
            }
        }else{
            if(!empty((string) $item['Wheel Size'])){
                $wheel_size = (string) $item['Wheel Size'];
            }else{
                $errors[] = 'Wheel size not set';
            }
            if(!empty((string) $item['Wheel Width'])){
                $wheel_width = (string) $item['Wheel Width'];
            }else{
                $errors[] = 'Wheel width not set';
            }
            if(!empty((string) $item['Wheel Colour'])){
                $wheel_colour = (string) $item['Wheel Colour'];
            }else{
                $errors[] = 'Wheel colour not set';
            }
            if(!empty((string) $item['Wheel Offset'])){
                $wheel_offset = (string) $item['Wheel Offset'];
            }else{
                $errors[] = 'Wheel offset not set';
            }
            if(!empty($errors)){
                return [
                    'data_present' => false,
                    'errors' => $errors
                ];
            }else{
                return [
                    'data_present' => true,
                    'title' => $wheel_size . ' x ' . $wheel_width . ' ' . $brand_name . ' ' . $model_name . ' ' . $wheel_colour . ' ET' . $wheel_offset
                ];
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

    /**
     * Sets or updates the product category
     *
     * @param WC_Product $product
     * @param String $category
     * @return bool
     */
    private function get_product_category(WC_Product $product, String $category)
    {
        //Remove all the categories from the product first
        $product_id = $product->get_id();
        $terms = get_the_terms($product_id, 'product_cat');
        if($terms!==false && count($terms)){
            $product->set_category_ids([]);
        }
        /*foreach($terms as $term){
            wp_remove_object_terms($product_id, $term->term_id, 'product_cat');
        }*/

        //Check if the category exists
        if($ct = get_term_by('name', $category, 'product_cat')){
            //Category exists
            $ct_id = $ct->term_id;

        }else{
            //Category does not exist
            $insert = wp_insert_term($category, 'product_cat');
            if(is_wp_error($insert)){
                return false;
            }
            $ct_id = $insert['term_id'];
        }
        return $ct_id;
    }

    private function check_attribute(WC_Product $product, $attribute_value, String $term_value, Array $wc_attributes)
    {
        global $wpdb;

        if(is_array($attribute_value)){
            $attribute = $attribute_value['slug'];
            if(isset($attribute_value['mapping'])){
                $term = $attribute_value['mapping'][$term_value];
            }else{
                $term = $term_value;
            }
        }else{
            $attribute = $attribute_value;
            $term = $term_value;
        }

        //Get the term
        if($existing_term = get_term_by('name', $term, 'pa_' . $attribute)){
            //Term exists
            $term_id = $existing_term->term_id;
            //Only need to check if term is on product if the term already exists
            if(!$this->product_has_attribute_term($product, $attribute, $term_id)){
                $wc_attribute = $this->product_set_attribute($product, $attribute, $term_id);
            }else{
                $wc_attribute = $wc_attributes['pa_' . $attribute];
            }
        }else{
            //Only create a term if scope is global
            if(is_array($attribute_value)&&$attribute_value['scope']=='local'){
                $wc_attribute = $this->product_set_local_attribute($product, $attribute, $term);
            }else{
                //Assume global scope!
                $insert = wp_insert_term($term, 'pa_' . $attribute);
                $term_id = $insert['term_id'];
                $wc_attribute = $this->product_set_attribute($product, $attribute, $term_id);
            }
        }

        return $wc_attribute;
    }

    private function product_has_attribute_term(WC_Product $product, $attribute, $term_id)
    {
        $term_ids = wc_get_product_terms( $product->get_id(), 'pa_' . $attribute, ['fields' => 'ids']);
        if(in_array($term_id, $term_ids)){
            return true;
        }else{
            return false;
        }
    }

    private function product_set_attribute(WC_Product $product, $attribute, $term_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $sql = 'SELECT * FROM ' . $table . ' WHERE attribute_name = \'' . $attribute . '\';';
        $row = $wpdb->get_row($sql);
        if($row){
            $attribute_id = $row->attribute_id;
            $product_attribute = new WC_Product_Attribute();
            $product_attribute->set_id($attribute_id);
            $product_attribute->set_name('pa_' . $attribute);
            $product_attribute->set_options([$term_id]);
            return $product_attribute;
        }else{
            return false;
        }
    }

    private function product_set_local_attribute(WC_Product $product, $name, $value)
    {
        $product_attribute = new WC_Product_Attribute();
        $product_attribute->set_id(0);
        $product_attribute->set_name($name);
        $product_attribute->set_options([$value]);
        return $product_attribute;
    }

    public function run($auto)
    {
        //$start = time();
        $dt = new DateTime();
        $tz = new DateTimeZone("Europe/London");
        $dt->setTimezone($tz);
        $start = $dt->getTimestamp();

        //Loop through $this->stages executing each in turn and fail and return if any errors occur
        foreach($this->stages as $stage){
            $stage_start = microtime(true);
            $this->stage = $stage;
            $this->{$stage}();
            $stage_end = microtime(true);
            $exec_time = $stage_end - $stage_start;

            $this->info[$stage]['Start time'] = $stage_start;
            $this->info[$stage]['End time'] = $stage_end;
            $this->info[$stage]['Execution time'] = $exec_time;

            if($this->hasErrors($stage)){ //Any errors at any stage will break the run script immediately
                $this->log_info($start, false, $auto);
                if(!$auto){
                    $this->redirect_to_settings();
                }
                break;
            }
        }

        //If we get to here then the script has run successfully
        $this->log_info($start, true, $auto);

        if(!$auto){
            $this->redirect_to_settings();
        }else{
            return true;
        }
    }

    private function redirect_to_settings()
    {
        wp_redirect(get_admin_url() . 'options-general.php?page=' . $this->plugin_name);
    }

    private function log_info($start_time, $success, $auto){
        global $wpdb;
        $table_name = $wpdb->prefix . 'fbf_importer_log';

        $et = new DateTime();
        $etz = new DateTimeZone('Europe/London');
        $et->setTimezone($etz);
        $end_time = $et->getTimestamp();
        $offset = $et->getOffset();

        $inserted = $wpdb->insert(
            $table_name,
            [
                'starttime' => date('Y-m-d H:i:s', $start_time + $offset),
                'endtime' => date('Y-m-d H:i:s', $end_time + $offset),
                'success' => $success,
                'type' => $auto?'automatic':'manual',
                'log' => json_encode($this->info)
            ]
        );
        if(!$inserted){
            //There was an error with the insert to the log
            die('There was an error creating the log');
        }
    }

    private function hasErrors($stage)
    {
        if(array_key_exists($stage, $this->errors)){
            if(is_array($this->errors[$stage])){
                return count($this->errors[$stage]);
            }
        }
        return false;
    }

    private function report_errors()
    {
        include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-error-reporting.php';
        $errorsToReport = new Fbf_Importer_Error_Reporting();
        $errorsToReport->allErrors = $this->errors;
        $errorsToReport->errorReportEmail = get_option($this->option_name . '_email');
        $errorsToReport->fbf_report_any_errors();
    }

    private function add_to_yoast_seo($post_id, $metatitle, $metadesc, $metakeywords){
        $ret = false;

        $desc = sprintf('Buy %1$s online today with next day delivery available & no fuss return policy. We won\'t be beaten on price.', $metadesc);

        $updated_desc = update_post_meta($post_id, '_yoast_wpseo_metadesc', $desc);
        //$updated_kw = update_post_meta($post_id, '_yoast_wpseo_metakeywords', $metakeywords);

        //if($updated_title && $updated_desc && $updated_kw){
        if($updated_desc){
            $ret = true;
        }
        return $ret;
    }
}
