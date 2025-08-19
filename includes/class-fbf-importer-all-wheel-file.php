<?php

/**
 * Class for reading the All Wheel file from Google Sheets
 * Reference: https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api
 */
class Fbf_Importer_All_Wheel_File
{
    private $client;
    private $service;

    public function __construct()
    {
        // include your composer dependencies
        if(function_exists('get_home_path')){
            require_once get_home_path() . '../vendor/autoload.php';
        }else{
            require_once ABSPATH . '../../vendor/autoload.php';
        }

        $this->client = new \Google_Client();
        $this->client->setApplicationName('Google Sheets API');
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        $path = get_template_directory() . '/../config/google_service_account.json';
        $this->client->setAuthConfig($path);

        // configure the Sheets Service
        $this->service = new \Google_Service_Sheets($this->client);

    }

    public function read($spreadsheetID, $sheet, $first_row_column_labels=false)
    {
        $range = $sheet; // here we use the name of the Sheet to get all the rows
        $response = $this->service->spreadsheets_values->get($spreadsheetID, $range);

        $values = $response->getValues();
        if($first_row_column_labels){
            $labels = $values[0];
            $data = [];
            for($i=1;$i<=count($values);$i++){
				if(isset($values[$i])){
					$row = $values[$i];
					$row_data = [];
					if(is_array($row)){
						foreach($row as $dk => $dv){
							$row_data[$labels[$dk]] = $dv;
						}
					}
					$data[] = $row_data;
				}
            }
            return $data;
        }
        return $values;
    }
}
