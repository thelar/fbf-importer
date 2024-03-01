<?php

class Fbf_Importer_Item_Import
{
    private $plugin_name;
    private $db_id;
    private $option_name = 'fbf_importer';
    private $min_stock;
    private $do_images = true;
    private $rsp_rules;
    private $price_match_data;
    private $fitting_cost;
    private $flat_fee;
    private $tmp_products_table;
    private $suppliers;

    public function __construct($plugin_name, $id, $min_stock, $rsp_rules, $price_match_data, $fitting_cost, $flat_fee)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->db_id = $id;
        $this->min_stock = $min_stock;
        $this->rsp_rules = $rsp_rules;
        $this->price_match_data = $price_match_data;
        $this->fitting_cost = $fitting_cost;
        $this->flat_fee = $flat_fee;
        $this->tmp_products_table = $wpdb->prefix . 'fbf_importer_tmp_products';

        $this->suppliers = $this->build_suppliers();
    }

    public function import($item)
    {
        global $wpdb;
        $sku = (string)$item['Product Code'];
        $name_gpf = null; // need to set to null for looping

        $is_variable = false;

        $status = [];

        //VALIDATION - First check the necessary data is present:
        $mandatory = [
            'Brand Name',
            'Model Name',
            'Wheel Tyre Accessory',
            'List on eBay',
            'Include in Price Match',
        ];
        if (isset($item['Wheel Tyre Accessory'])) {
            if ($item['Wheel Tyre Accessory'] == 'Tyre'||$item['Wheel Tyre Accessory'] == 'tyre') {
                //It's a Tyre
                $white_lettering = (string) $item['Tyre White Lettering'] ?? 'False';
                $runflat = (string) $item['Tyre Runflat'] ?? 'False';
                array_push($mandatory, 'Load/Speed Rating', 'Tyre Type', 'Tyre Width', 'Tyre Size', 'Tyre Profile', 'Tyre XL', 'Tyre White Lettering', 'Tyre Runflat');

                // Remove the word Tyres from the brand name if it's there
                if(isset($item['Brand Name'])){
                    $brand_title = str_ireplace(' tyres', '', (string) $item['Brand Name']);
                }else{
                    $brand_title = '';
                }

                // Search for Tyre type in Model name and remove if found
                if(isset($item['Tyre Type'])){
                    $model_title = (string) $item['Model Name'];
                    $type_search = ' ' . strtolower((string)$item['Tyre Type']);
                    $model_title = str_ireplace($type_search, '', $model_title);
                }else{
                    $model_title = '';
                }

                $name = sprintf('%s/%s%s %s %s %s %s %s %s Tyre', $item['Tyre Width'] ?? '', $item['Tyre Profile']!='-'?$item['Tyre Profile'].'R':'', $item['Tyre Size'] ?? '', $brand_title, $model_title, $item['Tyre Vehicle Specific'] ?? '',  isset($item['Tyre Type'])&&(string)$item['Tyre Type']!=='Summer'&&(string)$item['Tyre Type']!=='All Year' ? $item['Tyre Type'] : '', $white_lettering == 'True' ? 'White Letter' : '', $item['Load/Speed Rating'] ?? '');
                $name_gpf = sprintf('%s/%s%s %s %s %s %s %s %s', $item['Tyre Width'] ?? '', $item['Tyre Profile']!='-'?$item['Tyre Profile'].'R':'', $item['Tyre Size'] ?? '', (string) $item['Brand Name'] , $model_title, $item['Tyre Vehicle Specific'] ?? '', isset($item['Tyre Type'])&&(string)$item['Tyre Type']!=='Summer'&&(string)$item['Tyre Type']!=='All Year' ? $item['Tyre Type'] : '', $white_lettering == 'True' ? 'White Letter' : '', $item['Load/Speed Rating'] ?? '');

                if(in_array((string)$item['Tyre Type'], ['All Terrain', 'Mud Terrain', 'All Season', 'Winter']) && !empty($model_title)){
                    $name_display = sprintf('%s %s %s %s %s', $brand_title, $model_title, (string)$item['Tyre Type'], $white_lettering == 'True' ? 'White Letter' : '', $runflat == 'True' ? 'Runflat' : '');
                    $name_display = str_ireplace(['   ', '  '], ' ', $name_display);
                }else if(!empty($model_title)){
                    $name_display = sprintf('%s %s %s %s', $brand_title, $model_title, $white_lettering == 'True' ? 'White Letter' : '', $runflat == 'True' ? 'Runflat' : '');
                    $name_display = str_ireplace(['   ', '  '], ' ', $name_display);
                }

                $name = str_ireplace(['   ', '  '], ' ', $name);
                $name_gpf = str_ireplace(['   ', '  '], ' ', $name_gpf);

                $attrs = [
                    'Load/Speed Rating' => 'load-speed-rating',
                    'Brand Name' => 'brand-name',
                    'Model Name' => 'model-name',
                    'Tyre Type' => 'tyre-type',
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
                    ],
                    'Three Peaks' => 'three-peaks',
                    'Mud Snow' => 'mud-snow',
                    'Fit On Drive' => 'fit-on-drive',
                ];
            } else if ($item['Wheel Tyre Accessory'] != 'Accessories') {
                //It's a Wheel
                array_push($mandatory, 'Wheel TUV', 'Wheel Size', 'Wheel Width', 'Wheel Colour', 'Wheel Load Rating', 'Wheel Offset', 'Wheel PCD');

                if(isset($item['Model Name'])){
                    $model_title = (string) $item['Model Name'];
                    $model_title = str_ireplace([' steel', 'steel', ' alloy', 'alloy', ' wheel', 'wheel', ' High X', 'High X'], '', $model_title);
                }else{
                    $model_title = '';
                }

                $name = sprintf('%s %s %s %s x %s ET%s %s', isset($item['Brand Name']) ? $item['Brand Name'] : '', $model_title, isset($item['Wheel Tyre Accessory']) ? $item['Wheel Tyre Accessory'] : '', isset($item['Wheel Size']) ? $item['Wheel Size'] : '', isset($item['Wheel Width']) ? $item['Wheel Width'] : '', isset($item['Wheel Offset']) ? $item['Wheel Offset'] : '', isset($item['Wheel Colour']) ? $item['Wheel Colour'] : '');
                $name_display = sprintf('%s %s %s x %s ET%s %s', $model_title, isset($item['Wheel Tyre Accessory']) ? $item['Wheel Tyre Accessory'] : '', isset($item['Wheel Size']) ? $item['Wheel Size'] : '', isset($item['Wheel Width']) ? $item['Wheel Width'] : '', isset($item['Wheel Offset']) ? $item['Wheel Offset'] : '', isset($item['Wheel Colour']) ? $item['Wheel Colour'] : '');
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
                $name_display = null;
                $attrs = [
                    'Brand Name' => 'brand-name',
                    'Model Name' => 'model-name',
                    'Accessory Type' => 'accessory-type',
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
                    $product = new WC_Product($product_id);
                } else {
                    //Create the product
                    $status['action'] = 'Create';
                    $product = new WC_Product();
                }

                $product->set_name($name);
                $this->add_to_yoast_seo($product_id, '', $name, '');

                //Set GPF title if set
                if (isset($name_gpf)){
                    update_post_meta($product->get_id(), '_fbf_gpf_product_title', $name_gpf);
                }else{
                    delete_post_meta($product->get_id(), '_fbf_gpf_product_title');
                }

                //Set display title if set
                if (isset($name_display)){
                    update_post_meta($product->get_id(), '_fbf_display_product_title', $name_display);
                }else{
                    delete_post_meta($product->get_id(), '_fbf_display_product_title');
                }



                //Set the title in the post meta - this is for eBay Package searches and other searches where we need to filter by both SKU and Title
                update_post_meta($product->get_id(), '_fbf_product_title', $name);

                $product->set_sku($sku);
                $product->set_catalog_visibility('visible');
                //$product->set_regular_price(round((string)$item['RSP Exc Vat'], 2));

                //Price
                $product->set_regular_price($this->set_price($product, (string)$item['Wheel Tyre Accessory'], round((string)$item['RSP Exc Vat'], 2)));


                //Category
                //Correct the misuse of word tyre - DAN!!!!
                if ($pc_id = $this->get_product_category($product, ucfirst($item['Wheel Tyre Accessory']))) {
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

                                    // If load contains a slash split it and take highest value as load
                                    if(strpos($load, '/')!==false){
                                        $loads = explode('/', $load);
                                        $load = max($loads);
                                    }

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

                if (!empty($new_attrs) && !in_array(false, $new_attrs)) {
                    $product->set_attributes($new_attrs);
                }

                //Ebay price
                if (isset($item['Ebay Price'])){
                    if((float) $item['Ebay Price'] > 0){
                        update_post_meta($product_id, '_ebay_price', (float) $item['Ebay Price']);
                    }else{
                        delete_post_meta($product_id, '_ebay_price');
                    }
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
                //Dan request 22 Jul 2021 - now need to exclude Alloy wheels too
                if((string)$cat=='Steel Wheel' || (string)$cat=='Alloy Wheel'){
                    $product->set_backorders('notify');
                    $product->update_meta_data('_went_out_of_stock_on', '');

                    // If the stock is back up to 4 or more - and the initial stock was less than or equal to 0 - it's just come back into stock - so mark accordingly
                    if($initial_stock <= 0 && $product->get_stock_quantity() >= 4){
                        $product->update_meta_data('_back_in_stock_date', time());
                    }

                    // For non house brands (Wheels), if there is no stock, hide it!
                    $house_wheel_brands = [
                        'Challenger',
                        'DV8',
                        'DV8 Works',
                        'OEM Style',
                        'Tuff Torque',
                        'VBS'
                    ];
                    if(!in_array((string)$item['Brand Name'], $house_wheel_brands)){
                        if($product->get_stock_quantity()<=0){
                            $product->set_backorders('no');
                            $product->set_catalog_visibility('hidden');
                        }
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
                        // Mod 23 Nov 2022 - if Tyre is NOT AT or MT - don't allow backordering
                        if((string)$item['Tyre Type']=='All Terrain'||(string)$item['Tyre Type']=='Mud Terrain'){
                            $now = new DateTime('now');
                            $stock_date = new DateTime();
                            $stock_date->setTimestamp($product->get_meta('_went_out_of_stock_on'));

                            // Mod 25 Aug 2021 - diff'ing months only works if in same calendar year - check years first!!
                            $years = 0;
                            // Check whether product went out of stock 3 or more months ago (6 months for Tyres - requested by Dan 29th March 21)
                            if((string)$cat=='Tyre'){
                                $months = 6;
                            }else{
                                $months = 3;
                            }
                            if($stock_date->diff($now)->y === $years){
                                if($stock_date->diff($now)->m >= $months){
                                    $product->set_backorders('no');
                                }else{
                                    $product->set_backorders('notify');
                                }
                            }else{
                                $product->set_backorders('no');
                            }
                        }else{
                            $product->set_backorders('no');
                        }
                    }else{
                        // Here if there is stock

                        // Mod 13 Jan 2023 - if it's NOT All Terrain or Mud Terrain - no backordering!
                        if((string)$item['Tyre Type']=='All Terrain'||(string)$item['Tyre Type']=='Mud Terrain') {
                            $product->update_meta_data('_went_out_of_stock_on', '');
                            $product->set_backorders('notify');

                            // If the stock is back up to 4 or more - and the initial stock was less than or equal to 0 - it's just come back into stock - so mark accordingly
                            if ($initial_stock <= 0 && $product->get_stock_quantity() >= 4) {
                                $product->update_meta_data('_back_in_stock_date', time());
                            }
                        }else{
                            $product->set_backorders('no');
                            $product->delete_meta_data('_went_out_of_stock_on');
                            $product->delete_meta_data('_back_in_stock_date');
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
                    //Store the stockist array for lead times
                    update_post_meta($product_id, '_stockist_lead_times', $this->get_supplier_lead_times($product, $item));

                    //Product saved - handle the product image
                    //include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-product-image.php';

                    if($this->do_images){
                        include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-product-gallery.php';

                        if (isset($item['Image name'])) {
                            // TODO: handle white images - probably do this when creating rows in tmp product database, so unnecessary here
                            /*if($is_white){
                                $image_name = $this->stock_white_changes[$orig_sku]['image_white'];
                            }else{
                                $image_name = (string)$item['Image name'];
                            }*/

                            $image_name = (string)$item['Image name'];

                            // Is it an external image?
                            if(strpos($image_name, 'cdn.boughtofeed.co.uk')!==false||strpos($image_name, 'assets.micheldever.co.uk')!==false){
                                // Remove main image
                                delete_post_thumbnail($product_id);
                                // Remove any gallery
                                delete_post_meta($product_id, '_product_image_gallery');
                                // Remove eBay
                                delete_post_meta($product_id, '_fbf_ebay_images');

                                if(strpos($image_name, 'cdn.boughtofeed.co.uk')){
                                    $boughto_parts = pathinfo($image_name);
                                    $boughto_dirname = $boughto_parts['dirname'];
                                    update_post_meta($product_id, '_external_product_image', $boughto_dirname);
                                }else if(strpos($image_name, 'assets.micheldever.co.uk')){
                                    update_post_meta($product_id, '_external_product_image', $image_name);
                                }

                            }else{
                                // Remove external image
                                delete_post_meta($product_id, '_external_product_image');

                                // Handle our images in normal way
                                $image_gallery = new Fbf_Importer_Product_Gallery($product_id, $image_name, $this->plugin_name);

                                $main_image_result = $image_gallery->process($status['action']);

                                if (isset($main_image_result['errors'])) {
                                    $status['errors'] = $main_image_result['errors'];
                                } else {
                                    $status['image_info'] = $main_image_result['info'];
                                }

                                $image_gallery_result = $image_gallery->gallery_process($status['action']);

                                /*$image_handler = new Fbf_Importer_Product_Image($product_id, (string)$item['Image name']);
                                $image_import = $image_handler->process($status['action']);*/
                                if (isset($image_gallery_result['errors'])) {
                                    $status['errors'] = $image_gallery_result['errors'];
                                } else {
                                    $status['gallery_info'] = $image_gallery_result['gallery_info'];
                                    $s = [];
                                    if(!empty($image_gallery_result['gallery_image_info'])){
                                        foreach($image_gallery_result['gallery_image_info'] as $gal_item_info){
                                            $s[] = $gal_item_info[0];
                                        }
                                    }
                                    //$status['gallery_image_info'] = '[' . implode(', ' , $s) . ']';
                                }

                                // ebay images
                                $ebay_images = $image_gallery->ebay_process($status['action']);
                                if(!empty($ebay_images)){
                                    update_post_meta($product_id, '_fbf_ebay_images', $ebay_images);
                                }else{
                                    delete_post_meta($product_id, '_fbf_ebay_images');
                                }
                            }
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
                        $rsp = $this->get_rsp($item, $product_id, (float)$product->get_regular_price());
                        if($rsp['price_match']){
                            $rsp_price = round($rsp['price'], 2);
                        }else{
                            $rsp_price = round($rsp['price'] * 1.2, 2);
                        }
                        $rsp_price_match = $rsp['price_match'];
                    }else{
                        $rsp_price = round((float)$item['RSP Exc Vat'] * 1.2, 2); //Added vat here
                        $rsp_price_match = false;
                    }

                    // Set meta on product for price match
                    update_post_meta($product_id, '_price_match', $rsp_price_match);

                    //Handle zero here - throw a warning and don't add to RSP

                    if($rsp_price!==(float)0){
                        $this->set_rsp([
                            'Variant_Code' => $sku,
                            'RSP_Inc' => $rsp_price,
                            'Price_Match' => $rsp_price_match
                        ]);
                    }else{
                        $status['errors'][] = 'RSP was calculated as zero';
                        //Just set the RSP to the PriceExcVat in the stock file
                        $this->set_rsp([
                            'Variant_Code' => $sku,
                            'RSP_Inc' => round((float)$item['RSP Exc Vat'] * 1.2, 2),
                            'Price_Match' => $rsp_price_match
                        ]);
                    }
                }

                // Update the product_id
                $this->set_product_id($product->get_id());

            } else {
                //Data is not valid
                $status['data_valid'] = false;
                $status['errors'] = $data_valid;
            }
            $stock_status[$sku] = $status;

        } else {
            $status['errors'][] = 'Category is not set';
        }

        $this->set_status($status);
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

    private function add_to_yoast_seo($post_id, $metatitle, $metadesc, $metakeywords)
    {
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

    private function set_price(WC_Product $product, $category, $in_price)
    {
        if($category==='Accessories'){
            return $in_price;
        }
        $tax = 1.2;

        // First add VAT
        $in_price_plus_vat = $in_price * $tax;

        // Get the decimal amount
        $fln = $in_price_plus_vat - floor($in_price_plus_vat);

        // If it's a tyre round to nearest 0.49 or 0.99 - https://stackoverflow.com/questions/47389399/round-number-up-to-49-or-99
        if($category==='Tyre'){
            if($fln > 0 && $fln < 0.5){
                $fln = 0.49;
            }else{
                $fln = 0.99;
            }
        }else if($category==='Alloy Wheel' || $category==='Steel Wheel'){
            // If it's a wheel round up or down
            $fln = $in_price_plus_vat - floor($in_price_plus_vat);
            if($fln > 0 && $fln < 0.5){
                $fln = 0;
            }else{
                $fln = 1;
            }
        }
        $price = floor($in_price_plus_vat) + $fln;
        $base_price = $price/$tax;

        return round($base_price, 4);
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

        if(strpos($term_value, '|')!==false){
            // Multiple values
            $term_values = explode('|', $term_value);


        }

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

        if(isset($term_values)&&!empty($term_values)){
            $term_ids = [];
            foreach($term_values as $term_value){
                if($existing_term = get_term_by('name', $term_value, 'pa_' . $attribute)){
                    $term_ids[] = $existing_term->term_id;
                }else{
                    //Assume global scope!
                    $insert = wp_insert_term($term, 'pa_' . $attribute);
                    if(!is_wp_error($insert)){
                        $term_ids[] = $insert['term_id'];
                    }
                }
            }
            if(!empty($term_ids)){
                $product_has_terms = true;
                foreach($term_ids as $term_id_m){
                    if(!$this->product_has_attribute_term($product, $attribute, $term_id_m)){
                        $product_has_terms = false; // break out of loop
                        break;
                    }
                }
                if(!$product_has_terms){
                    $wc_attribute = $this->product_set_attribute($product, $attribute, $term_ids);
                }else{
                    $wc_attribute = $wc_attributes['pa_' . $attribute];
                }
            }
        }else{
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
                    if(!is_wp_error($insert)){
                        $term_id = $insert['term_id'];
                        $wc_attribute = $this->product_set_attribute($product, $attribute, $term_id);
                    }
                }
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
            if(is_array($term_id)){
                $product_attribute->set_options($term_id);
            }else{
                $product_attribute->set_options([$term_id]);
            }
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

    private function get_cost($product, $item, $min)
    {
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

    private function get_rsp($item, $product_id, $price)
    {
        $sku = strtoupper((string) $item['Product Code']);

        // Price match
        /*foreach($this->rsp_rules as $rule){
            if($this->does_rule_apply($rule, $product_id)){
                if($rule['price_match']==='1'){
                    // Price match rule found - try to match SKU against data
                    if(key_exists($sku, $this->price_match_data)){
                        return [
                            'price_match' => true,
                            'price' => $this->price_match_data[$sku]['price'] - ($this->fitting_cost * 1.2),
                        ];
                    }
                }
            }
        }*/

        $s_price = $this->get_supplier_cost($item, $price);
        $pc = 0;

        if($s_price === (float)0){
            return [
                'price_match' => false,
                'price' => $s_price,
            ]; //Return with zero so we can catch error
        }

        if($s_price != $price){
            //1. Loop through the rules
            foreach ($this->rsp_rules as $rule) {
                if ($this->does_rule_apply($rule, $product_id)) {
                    if($rule['price_match']==='1'){
                        // Price match rule found - try to match SKU against data
                        if(key_exists($sku, $this->price_match_data)){
                            if($rule['price_match_addition']){
                                $addition = $rule['price_match_addition'];
                            }else{
                                $addition = 0;
                            }
                            return [
                                'price_match' => true,
                                'price' => ($this->price_match_data[$sku]['price'] + $addition) - ($this->fitting_cost * 1.2),
                            ];
                        }
                    }else{
                        if($rule['is_pc']==='1'){
                            return [
                                'price_match' => false,
                                'price' => (($rule['amount']/100) * $s_price) + $s_price + $this->flat_fee,
                            ];
                        }else{
                            return [
                                'price_match' => false,
                                'price' => $s_price + $rule['amount'] + $this->flat_fee,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'price_match' => false,
            'price' => $price,
        ];
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
            // If we are here then 4x4 have no stock and All suppliers have no stock - look for the main supplier with a cost price that isn't 0 OR if no main supplier, then take average (excluding 0 costs)
            /*if(isset($item['Suppliers'])){
                foreach($item['Suppliers'] as $supplier){
                    if((string)$supplier['Main Supplier']==='True' && (float)$supplier['Supplier Cost Price'] > 0){
                        return (float)$supplier['Supplier Cost Price'];
                    }
                }
                // If we are here then no suppliers are marked as the main supplier - get average cost
                $count = 0;
                $total = 0;
                foreach($item['Suppliers'] as $supplier){
                    if((float)$supplier['Supplier Cost Price'] > 0){
                        $total+= (float)$supplier['Supplier Cost Price'];
                        $count++;
                    }
                }
                $average = $total/$count;
                if($average > 0){
                    return $average;
                }
            }*/ // Commented out on 5/4/23 on request of DT
            return $price;
        }else{
            return $cheapest;
        }
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

    private function set_rsp($rsp)
    {
        global $wpdb;
        $wpdb->update(
            $this->tmp_products_table,
            [
                'rsp' => serialize($rsp)
            ],
            [
                'id' => $this->db_id
            ]
        );
    }

    private function set_status($status)
    {
        global $wpdb;
        $wpdb->update(
            $this->tmp_products_table,
            [
                'status' => serialize($status)
            ],
            [
                'id' => $this->db_id
            ]
        );
    }

    private function set_product_id($id)
    {
        global $wpdb;
        $wpdb->update(
            $this->tmp_products_table,
            [
                'product_id' => $id
            ],
            [
                'id' => $this->db_id
            ]
        );
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
}
