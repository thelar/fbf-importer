<?php

class Fbf_Importer_All_Wheel_File
{
    private $client;
    private $service;

    public function __construct()
    {
        // include your composer dependencies
        require_once plugin_dir_path(WP_PLUGIN_DIR . '/fbf-importer/fbf-importer.php') . 'vendor/autoload.php';
        $this->client = new \Google_Client();
        $this->client->setApplicationName('Google Sheets API');
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        $path = plugin_dir_path(WP_PLUGIN_DIR . '/fbf-importer/fbf-importer.php') . 'credentials.json';
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
                $row = $values[$i];
                $row_data = [];
                if(is_array($row)){
                    foreach($row as $dk => $dv){
                        $row_data[$labels[$dk]] = $dv;
                    }
                }
                $data[] = $row_data;
            }
            return $data;
        }
        return $values;
    }
}
