<?php


class Fbf_importer_read_rsp_xml
{
    public static $sku_file = 'sku_xml.xml';
    public static function read_xml()
    {
        //return ABSPATH;
        include_once ABSPATH . 'wp-admin/includes/file.php';
        //return get_home_path();


        $file = file_get_contents(ABSPATH . '/../../supplier/' . self::$sku_file);
        header('Content-type: text/xml');
        return $file;
    }
}
