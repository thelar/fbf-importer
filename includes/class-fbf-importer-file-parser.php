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
    public function __construct($plugin_name)
    {
        $this->plugin_name = $plugin_name;
    }

    public function file_exists()
    {
        $filename = get_option($this->option_name . '_file');
        $path = get_home_path() . '../supplier/' . $filename;
        if(file_exists($path)){
            $x = new XMLReader();
            $doc = new DOMDocument;
            $x->open($path);
            $counter = 0;
            $array = [];

            // reading xml data...
            while($x->read()) {
                if ($x->nodeType == XMLReader::ELEMENT && $x->name == 'Variant') {
                    $counter+=1;
                    $node = simplexml_import_dom($doc->importNode($x->expand(), true));
                    $array[] = $node;
                }
            }
            return $array;
        }else{
            return false;
        }
    }
}
