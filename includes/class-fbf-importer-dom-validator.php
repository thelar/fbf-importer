<?php

/**
 * Class Fbf_Importer_Dom_Validator
 *
 * @author Kevin Price-Ward
 * @see https://www.codementor.io/surajudeenakande/validating-xml-against-xsd-in-php-6f56rwcds
 */
class Fbf_Importer_Dom_Validator
{
    /**
     * @var string
     */
    protected $feedSchema;
    /**
     * @var int
     */
    public $feedErrors = 0;
	/**
	 * Handler info
	 *
	 * @var DOMDocument
	 */
	private $handler;
    /**
     * Formatted libxml Error details
     *
     * @var array
     */
    public $errorDetails;
    /**
     * Validation Class constructor Instantiating DOMDocument
     *
     * @param \DOMDocument $handler [description]
     */
    public function __construct()
    {
        $this->handler = new \DOMDocument('1.0', 'utf-8');
        if(function_exists('get_home_path')){
            $this->feedSchema = get_home_path() . '../supplier/stock.xsd';
        }else{
            $this->feedSchema = ABSPATH . '../../supplier/stock.xsd';
        }

    }
    /**
     * @param \libXMLError object $error
     *
     * @return string
     */
    private function libxmlDisplayError($error)
    {
        $errorString = "Error $error->code in $error->file (Line:{$error->line}):";
        $errorString .= trim($error->message);
        return $errorString;
    }
    /**
     * @return array
     */
    private function libxmlDisplayErrors()
    {
        $errors = libxml_get_errors();
        $result    = [];
        foreach ($errors as $error) {
            $result[] = $this->libxmlDisplayError($error);
        }
        libxml_clear_errors();
        return $result;
    }
    /**
     * Validate Incoming Feeds against Listing Schema
     *
     * @param resource $feeds
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function validateFeeds($feeds)
    {
        if (!class_exists('DOMDocument')) {
            throw new \DOMException("'DOMDocument' class not found!");
            return false;
        }
        if (!file_exists($this->feedSchema)) {
            throw new \Exception('Schema is Missing, Please add schema to feedSchema property');
            return false;
        }
        libxml_use_internal_errors(true);
        if (!($fp = fopen($feeds, "r"))) {
            die("could not open XML input");
        }

        $contents = fread($fp, filesize($feeds));
        fclose($fp);

        $this->handler->loadXML($contents, LIBXML_NOBLANKS);
        if (!$this->handler->schemaValidate($this->feedSchema)) {
            $this->errorDetails = $this->libxmlDisplayErrors();
            $this->feedErrors   = 1;
        } else {
            //The file is valid
            return true;
        }
    }
    /**
     * Display Error if Resource is not validated
     *
     * @return array
     */
    public function displayErrors()
    {
        return $this->errorDetails;
    }
}
