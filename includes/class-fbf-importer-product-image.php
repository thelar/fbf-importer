<?php


class Fbf_Importer_Product_Image
{
    private $product_id;
    private $image_name;
    private $image_filepath;
    private static $source_image_dir = 'source_images';
    private $return_data = [];

    public function __construct($product_id, $image)
    {
        $this->product_id = $product_id;
        $this->image_name = $image;
        $this->image_filepath = get_home_path() . '../supplier/' . self::$source_image_dir . '/' . $this->image_name;
    }
    public function process($product_action){
        if($this->source_image_exists()){
            //if we are creating the product, we only need to worry about creating the product image, otherwise we need to check the existing product image and compare it before processing
            if($product_action=='Create'){
                $this->return_data['info'][] = 'New product therefore no image exists';
                $this->product_add_image();
            }else if($product_action=='Update'){
                if(!$this->product_has_image()){
                    $this->return_data['info'][] = 'Product has no image';
                    $this->product_add_image();
                }else{
                    if($this->images_differ()){
                        $this->return_data['info'][] = 'Source image and product image do not match';
                        if($this->delete_attachment()){
                            $this->product_add_image();
                        }else{
                            $this->return_data['errors'][] = 'Original product image could not be deleted - new image not added';
                        }
                    }else{
                        $this->return_data['info'][] = 'Source image and product image match';
                    }
                }
            }
        }else{
            $this->return_data['errors'][] = 'Source image: ' . $this->image_name . ' does not exist';
        }
        return $this->return_data;
    }

    /**
     * Checks the source images folder for if the image exists
     *
     * @return bool
     */
    private function source_image_exists()
    {
        if(file_exists($this->image_filepath)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Checks whether the product has an image
     *
     * @return bool
     */
    private function product_has_image()
    {
        if(has_post_thumbnail($this->product_id)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Adds the image to the product
     *
     * @return mixed either true or string containing error
     */
    private function product_add_image()
    {
        $filename = $this->image_name;
        $file = $this->image_filepath;
        $parent_post_id = $this->product_id;

        $upload_file = wp_upload_bits($filename, null, file_get_contents($file));
        if(!$upload_file['error']){
            $wp_filetype = wp_check_filetype($filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => $parent_post_id,
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id,  $attachment_data );
                set_post_thumbnail( $parent_post_id, $attachment_id );
                $this->return_data['info'][] = $filename . ' added to media library';
            }else{
                $this->return_data['errors'][] = 'wp_insert_attachement error';
            }
        }else{
            $this->return_data['errors'][] = 'wp_upload_bits returned an error: ' . $upload_file['error'];
        }
    }

    private function delete_attachment()
    {
        //Removes the product image and deletes it from the media library entirely including the uploads dir
        $del = wp_delete_attachment(get_post_thumbnail_id($this->product_id), true);
        if($del!==false){
            return true;
        }else{
            return $del;
        }
    }

    /**
     * Checks if the source image is different to the attached image - just checks the filename and the filesize so not that sophisticated, it's unlikely that 2 different images will have exactly the same file name and bytesize really
     *
     * @return bool
     */
    private function images_differ()
    {
        $src_filesize = filesize($this->image_filepath);
        $src_name = preg_replace('/\.[^.]+$/', '', basename($this->image_filepath));

        $attach_id = get_post_thumbnail_id($this->product_id);
        $attach_title = get_the_title($attach_id);
        $attach_filepath = wp_get_attachment_image_src(get_post_thumbnail_id($this->product_id), 'full')[0];

        $wp_upload_dir = wp_upload_dir();
        $attach_name = basename($attach_filepath);
        $attach_filesize = filesize($wp_upload_dir['path'] . '/' . $attach_name);

        if($src_filesize!=$attach_filesize||$src_name!=$attach_title){
            return true;
        }else{
            return false;
        }
    }
}
