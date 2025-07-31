<?php

class Fbf_Importer_Product_Gallery
{
    private $product_id;
    private $image_name;
    private $ebay_image_name;
    private $ebay_gallery_images;
    private $image_base;
    private $plugin_name;
    private $base_image_filepath;
    private static $source_image_dir = 'images';
    private $main_image;
    private $gallery_images;
    private $return_data = [];

    public function __construct($product_id, $image, $plugin_name)
    {
        $this->product_id = $product_id;
        $this->image_name = $image;
        $this->plugin_name = $plugin_name;
        $this->image_base = pathinfo($this->image_name, PATHINFO_FILENAME);
        $this->image_ext = pathinfo($this->image_name, PATHINFO_EXTENSION);
        if(function_exists('get_home_path')){
            $this->base_image_filepath = get_home_path() . '../supplier/' . self::$source_image_dir . '/';
        }else{
            $this->base_image_filepath = ABSPATH . '../../supplier/' . self::$source_image_dir . '/';
        }

        // Main image
        $this->main_image = $this->base_image_filepath . $this->image_name;

        // Get all of the images for the gallery
        $this->gallery_images = glob($this->base_image_filepath . $this->image_base . '_[0-9]*.{jpg,gif,png,webp}', GLOB_BRACE);

        // Ebay main image
        if(file_exists($this->base_image_filepath . $this->image_base . '_ebay' . '.' . $this->image_ext)){
            $this->ebay_image_name = $this->base_image_filepath . $this->image_base . '_ebay' . '.' . $this->image_ext;
        }

        // Get all of the images for eBay
        $this->ebay_gallery_images = glob($this->base_image_filepath . $this->image_base . '_ebay_[0-9]*.{jpg,gif,png,webp}', GLOB_BRACE);
    }

    public function process($action)
    {
        // Main image
        include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-product-image.php';
        include_once WP_PLUGIN_DIR . '/' . $this->plugin_name . '/includes/class-fbf-importer-product-gallery-image.php';
        $main_image_handler = new Fbf_Importer_Product_Image($this->product_id, $this->image_name);
        return $main_image_handler->process($action);
    }

    public function gallery_process($action)
    {
        // Gallery
        $gallery_ids = [];
        $gallery_sort = [];
        $response = [];
        foreach($this->gallery_images as $gallery_image){
            $gallery_image_handler = new Fbf_Importer_Product_Gallery_Image($this->product_id, basename($gallery_image));
            $gallery_image_import = $gallery_image_handler->process();

            if (isset($gallery_image_import['errors'])) {
                $response['errors'][] = $gallery_image_import['errors'];
            } else {
                $response['gallery_image_info'][] = $gallery_image_import['info'];
                $gallery_ids[] = $gallery_image_import['attach_id'];
                $gallery_sort[$gallery_image_import['attach_id']] = pathinfo($gallery_image, PATHINFO_FILENAME);
            }
        }

        if(sizeof($gallery_ids) > 0){
            asort($gallery_sort, SORT_NATURAL);
            $update_gallery = update_post_meta($this->product_id, '_product_image_gallery', implode(',', array_keys($gallery_sort)));
            if($update_gallery===true){
                $response['gallery_info'] = 'Gallery updated';
            }else if($update_gallery===false){
                $response['gallery_info'] = 'Gallery not updated';
            }else{
                $response['gallery_info'] = 'Gallery created';
            }
        }
        return $response;
    }

    public function ebay_process($action)
    {
        $ebay_images = [];
        $ebay_attach_ids = [];
        if(!empty($this->ebay_image_name)){
            $ebay_images[] = $this->ebay_image_name;
        }
        if(!empty($this->ebay_gallery_images)){
            foreach($this->ebay_gallery_images as $image){
                $ebay_images[] = $image;
            }
        }
        if(!empty($ebay_images)){
            foreach($ebay_images as $image){
                $ebay_image_handler = new Fbf_Importer_Product_Gallery_Image($this->product_id, basename($image));
                $upload = $ebay_image_handler->process_ebay();
                $ebay_attach_ids[] = $upload['attach_id'];
            }
        }
        return $ebay_attach_ids;
    }
}
