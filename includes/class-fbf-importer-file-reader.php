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

    public function __construct($plugin_name)
    {
        global $wp;
        $this->plugin_name = $plugin_name;
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
                echo 'Ready to process';
            }else{
                echo 'Still uploading';
            }
        }
    }
}
