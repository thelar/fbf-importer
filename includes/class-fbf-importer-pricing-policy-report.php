<?php
/**
 * Class responsible for generating pricing policy report.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */

use PhpOffice\PhpSpreadsheet\IOFactory;

class Fbf_Importer_Pricing_Policy_Report {

    public function __construct($plugin_name)
    {
        global $wp;
        $this->plugin_name = $plugin_name;
    }

    public function get_report()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fbf_importer_tmp_products';
        $data = [];
        // Get all active tyres
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '=',
                ],
                [
                    'key' => '_stock_status',
                    'value' => 'onbackorder',
                    'compare' => '=',
                ],
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'tyre',
                ]
            ]
        ];
        $tyre_ids = get_posts($args);



        foreach($tyre_ids as $tyre_id){
            //$product = wc_get_product($tyre_id);
            $sku = get_post_meta($tyre_id, '_sku', true);
            $brand = get_the_terms($tyre_id, 'pa_brand-name')[0]->name;
            $tyre_type = get_the_terms($tyre_id, 'pa_tyre-type')[0]->name;
            $q = $wpdb->prepare("SELECT * FROM {$table} WHERE sku = %s", $sku);
            $r = $wpdb->get_row($q);
            $price = get_post_meta($tyre_id, '_regular_price', true);

            $row = [
                $tyre_id,
                $sku,
                $brand ?:'',
                $tyre_type ?:'',
            ];




            if($r){
                if($price_data = $r->price_data){
                    $price_data = unserialize($price_data);
                    $row[] = $price_data['supplier_cost'];
                    $row[] = $price_data['regular_price'];
                    $row[] = $price_data['regular_price_inc_vat'];
                    $row[] = $price_data['use_rsp_rules'];
                    $row[] = (bool)$price_data['is_price_matched'];
                    if($price_data['is_price_matched']){
                        $row[] = $price_data['pm_cheapest_name'];
                        $row[] = $price_data['pm_calculated_price'];
                        $row[] = $price_data['pm_addition'];
                        $row[] = $price_data['pm_raw_price'];
                        foreach($price_data['pm_matched_prices'] as $pm){
                            $row[] = $pm['name'];
                            $row[] = $pm['price'];
                        }
                    }
                }
            }

            $data[] = $row;
        }
        $data[0] = [
            'PRODUCT_ID',
            'SKU',
            'BRAND',
            'TYRE_TYPE',
            'SUPPLIER_COST',
            'REGULAR_PRICE',
            'REGULAR_PRICE_INC_VAT',
            'USE_RSP',
            'IS_PRICE_MATCHED',
            'CHEAPEST_SUPPLIER_NAME',
            'CHEAPEST_SUPPLIER_COST',
            'PRICE_MATCH_ADDITION',
            'PRICE_MATCH_RAW',
            'PM_SUPPLIER_1_NAME',
            'PM_SUPPLIER_1_PRICE',
            'PM_SUPPLIER_2_NAME',
            'PM_SUPPLIER_2_PRICE',
            'PM_SUPPLIER_3_NAME',
            'PM_SUPPLIER_3_PRICE'
        ];


        return $data;
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
}
