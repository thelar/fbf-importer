<?php


class Fbf_Importer_Stock_Processor
{
    private $plugin_name;
    private $errors = [];
    private $filters = [
        'STAPLETONS',
    ];
    const STOCK_FEED_LOCATION = ABSPATH . '../../supplier/stock/';
    const UPLOAD_LOCATION = ABSPATH . '../../supplier/stock/Upload/';
    public function __construct($plugin_name)
    {
        require ABSPATH . '../../vendor/autoload.php';
        $this->plugin_name = $plugin_name;
    }

    public function process($redirect=true)
    {
        $a = 1;
        $b = 2;

        //$file = file_get_contents('ssh2.sftp://4x4tyressftp:Evilct7848!@SFTP.wheelpros.com:22/CommonFeed/GBP/WHEEL/wheelInvPriceData.csv');

        $connection = ssh2_connect('SFTP.wheelpros.com', 22);
        ssh2_auth_password($connection, '4x4tyressftp', getenv('WHEELPROS_FTP_PASS'));
        $sftp = ssh2_sftp($connection);
        $stream = fopen("ssh2.sftp://$sftp/CommonFeed/GBP/WHEEL/wheelInvPriceData.csv", 'r');
        $contents = stream_get_contents($stream);
        file_put_contents(self::UPLOAD_LOCATION . 'wheelpros/wheelpros.csv', $contents);
        @fclose($stream);

//        var_dump(ABSPATH);
//        var_dump(__DIR__);
        /*file_put_contents(self::UPLOAD_LOCATION . "wheelwright/stockfile_00w0.csv", fopen("http://dealer.wheelwright.co.uk/wheelwright_stock_listing_00w0?token=7xUBeYEaoYPBzV7wVPkd", 'r'));
        file_put_contents(self::UPLOAD_LOCATION . "tyresdirect/tyresdirect.csv", fopen("https://www.dropbox.com/s/t6mxrb7f7m879qh/malatesta%20stock.csv?dl=1", 'r'));
        file_put_contents(self::UPLOAD_LOCATION . "tyresdirect/tyresdirect_fedima.csv", fopen("https://www.dropbox.com/s/p8s6zui83p2xhjk/fedima%20stock.csv?dl=1", 'r'));

        file_put_contents(self::UPLOAD_LOCATION . 'tyresdirect/tyresdirect_merged.csv',
            file_get_contents(self::UPLOAD_LOCATION . 'tyresdirect/tyresdirect.csv') .
            file_get_contents(self::UPLOAD_LOCATION . 'tyresdirect/tyresdirect_fedima.csv')
        );*/


        //file_put_contents(self::UPLOAD_LOCATION . 'wolfrace/wolfrace.csv', fopen("http://www.wolfrace.com/?dealer-tools=download-stock", 'r'));
        file_put_contents(self::UPLOAD_LOCATION . 'wheelwright/wheelwright.csv', fopen("http://www.wheelwright.co.uk/index.php?dealer-tools=download-stock", 'r'));


        //echo(STOCK_FEED_LOCATION);
        /** \PhpOffice\PhpSpreadsheet\Spreadsheet */
//include('\PhpOffice\PhpSpreadsheet\Spreadsheet/\PhpOffice\PhpSpreadsheet\Spreadsheet.php');

        /** \PhpOffice\PhpSpreadsheet\Writer\Xlsx */
//include('\PhpOffice\PhpSpreadsheet\Spreadsheet/\PhpOffice\PhpSpreadsheet\Spreadsheet/Writer/Excel2007.php');
//include('\PhpOffice\PhpSpreadsheet\Spreadsheet/\PhpOffice\PhpSpreadsheet\Spreadsheet/IOFactory.php');



        $headers_array = array('STCODE', 'SPCODE', 'COST', 'QTY'); //A2, C2, E2, F2
        $data_columns_array = array('1', '3', '5', '6'); //A2, C2, E2, F2

        $supplier_array = array();

        $supplier_array[0]['name'] = "MICHELDEVER";
        $supplier_array[0]['read_filename'] = "3WHEELWH.CSV";
        $supplier_array[0]['write_filename'] = "3wheelwh";
        $supplier_array[0]['cell_1a'] = "SOUTHAMT";
        $supplier_array[0]['data_start_row'] = "1";
        $supplier_array[0]['mapping_array'] = array('1', '2', '39', '41');
        $supplier_array[0]['delimiter'] = ",";

        /*$supplier_array[1]['name'] = "BOND";
        $supplier_array[1]['read_filename'] = "bond.csv";
        $supplier_array[1]['write_filename'] = "bond";
        $supplier_array[1]['cell_1a'] = "BONDINTE";
        $supplier_array[1]['data_start_row'] = "1";
        $supplier_array[1]['mapping_array'] = array('0', '1', '24', '23');
        $supplier_array[1]['delimiter'] = ",";*/

        $supplier_array[2]['name'] = "STAPLETONS";
        $supplier_array[2]['read_filename'] = "EXP48419S.CSV";
        $supplier_array[2]['write_filename'] = "stapletons";
        $supplier_array[2]['cell_1a'] = "STPTYRES";
        $supplier_array[2]['data_start_row'] = "1";
        $supplier_array[2]['mapping_array'] = array('0', '16', '13', '15');
        $supplier_array[2]['delimiter'] = ",";

        $supplier_array[3]['name'] = "MALVERN";
        $supplier_array[3]['read_filename'] = "cooper.xls";
        $supplier_array[3]['write_filename'] = "malvern";
        $supplier_array[3]['cell_1a'] = "MALVERN";
        $supplier_array[3]['data_start_row'] = "3";
        $supplier_array[3]['mapping_array'] = array('0', '1', '4', '7');
        $supplier_array[3]['delimiter'] = ",";

        /*$supplier_array[4]['name'] = "DELDO";
        $supplier_array[4]['read_filename'] = "deldo47545.csv"; //"TOPGEAR.csv";
        $supplier_array[4]['write_filename'] = "deldo";
        $supplier_array[4]['cell_1a'] = "DELDO";
        $supplier_array[4]['data_start_row'] = "2";
        $supplier_array[4]['mapping_array'] = array('0', '19', '16', '15');
        $supplier_array[4]['delimiter'] = ";";*/

        $supplier_array[5]['name'] = "NANKANG";
        $supplier_array[5]['read_filename'] = "nankang.csv";
        $supplier_array[5]['write_filename'] = "nankang";
        $supplier_array[5]['cell_1a'] = "WESTLAND";
        $supplier_array[5]['data_start_row'] = "1";
        $supplier_array[5]['mapping_array'] = array('0', '4', '2', '3');
        $supplier_array[5]['delimiter'] = ",";

        /*$supplier_array[6]['name'] = "yoko";
        $supplier_array[6]['read_filename'] = "yoko.csv";
        $supplier_array[6]['write_filename'] = "yokohama";
        $supplier_array[6]['cell_1a'] = "YOKOH";
        $supplier_array[6]['data_start_row'] = "2";
        $supplier_array[6]['mapping_array'] = array('0', '0', '16', '11');
        $supplier_array[6]['delimiter'] = ",";*/

        $supplier_array[7]['name'] = "ets";
        $supplier_array[7]['read_filename'] = "edentyresales.csv";
        $supplier_array[7]['write_filename'] = "edentyresales";
        $supplier_array[7]['cell_1a'] = "EDENTYRE";
        $supplier_array[7]['data_start_row'] = "1";
        $supplier_array[7]['mapping_array'] = array('0', '2', '4', '17');
        $supplier_array[7]['delimiter'] = ",";

        $supplier_array[8]['name'] = "tyrespot";
        $supplier_array[8]['read_filename'] = "4x4TYR03.csv";
        $supplier_array[8]['write_filename'] = "tyrespot";
        $supplier_array[8]['cell_1a'] = "TYRESPOT";
        $supplier_array[8]['data_start_row'] = "2";
        $supplier_array[8]['mapping_array'] = array('1', '2', '36', '37');
        $supplier_array[8]['delimiter'] = ",";

        /*$supplier_array[9]['name'] = "vandeban";
        $supplier_array[9]['read_filename'] = "565921.csv";
        $supplier_array[9]['write_filename'] = "vandeban";
        $supplier_array[9]['cell_1a'] = "VANDEBAN";
        $supplier_array[9]['data_start_row'] = "2";
        $supplier_array[9]['mapping_array'] = array('0', '2', '13', '14');
        $supplier_array[9]['delimiter'] = ";";*/

//commented, because we using merged csv with fedima [16]
        //$supplier_array[10]['name'] = "tyresdirect";
        //$supplier_array[10]['read_filename'] = "tyresdirect.csv";
        //$supplier_array[10]['write_filename'] = "tyresdirect";
        //$supplier_array[10]['cell_1a'] = "TYRDIR";
        //$supplier_array[10]['data_start_row'] = "1";
        //$supplier_array[10]['mapping_array'] = array('0', '0', '2', '1');
        //$supplier_array[10]['delimiter'] = ",";

        /*$supplier_array[10]['name'] = "tyresdirect";
        $supplier_array[10]['read_filename'] = "tyresdirect_merged.csv";
        $supplier_array[10]['write_filename'] = "tyresdirect";
        $supplier_array[10]['cell_1a'] = "TYRDIR";
        $supplier_array[10]['data_start_row'] = "1";
        //$supplier_array[10]['mapping_array'] = array('0', '0', '3', '2');
        $supplier_array[10]['mapping_array'] = array('0', '0', '2', '1');
        $supplier_array[10]['delimiter'] = ",";*/

        /*$supplier_array[11]['name'] = "WOLFRACE";
        $supplier_array[11]['read_filename'] = "wolfrace_trade_gold.csv";
        $supplier_array[11]['write_filename'] = "wolfrace";
        $supplier_array[11]['cell_1a'] = "WOLFR";
        $supplier_array[11]['data_start_row'] = "1";
        $supplier_array[11]['mapping_array'] = array('3', '3', '20', '2');
        $supplier_array[11]['delimiter'] = ",";*/

        $supplier_array[12]['name'] = "wheelwright";
        $supplier_array[12]['read_filename'] = "wheelwright.csv";
        $supplier_array[12]['write_filename'] = "wheelwright";
        $supplier_array[12]['cell_1a'] = "WHEELWRI";
        $supplier_array[12]['data_start_row'] = "1";
        $supplier_array[12]['mapping_array'] = array('0', '0', '18', '14');
        $supplier_array[12]['delimiter'] = ",";

        //$supplier_array[13]['name'] = "Reifen";
        //$supplier_array[13]['read_filename'] = "Gundlach.csv";
        //$supplier_array[13]['write_filename'] = "Reifen";
        //$supplier_array[13]['cell_1a'] = "REIFEN";
        //$supplier_array[13]['data_start_row'] = "1";
        //$supplier_array[13]['mapping_array'] = array('0', '21', '19', '20');
        //$supplier_array[13]['delimiter'] = ";";

        /*$supplier_array[13]['name'] = "kahn_design";
        $supplier_array[13]['read_filename'] = "KahnDesignWheelsStock.csv";
        $supplier_array[13]['write_filename'] = "kahn_design";
        $supplier_array[13]['cell_1a'] = "AKAHN";
        $supplier_array[13]['data_start_row'] = "1";
        $supplier_array[13]['mapping_array'] = array('0', '0', '4', '2');
        $supplier_array[13]['delimiter'] = ",";*/

        //$supplier_array[14]['name'] = "Sixonetwo";
        //$supplier_array[14]['read_filename'] = "sixonetwo wheels stocklist.xls";
        //$supplier_array[14]['write_filename'] = "Sixonetwo";
        //$supplier_array[14]['cell_1a'] = "SIXONETW";
        //$supplier_array[14]['data_start_row'] = "1";
        //$supplier_array[14]['mapping_array'] = array('0', '0', '10', '10');
        //$supplier_array[14]['delimiter'] = ",";

        $supplier_array[14]['name'] = "Sixonetwo";
        $supplier_array[14]['read_filename'] = "sixonetwo wheels daily stockist pricing (full spec).csv";
        $supplier_array[14]['write_filename'] = "sixonetwo";
        $supplier_array[14]['cell_1a'] = "SIXONETW";
        $supplier_array[14]['data_start_row'] = "1";
        $supplier_array[14]['mapping_array'] = array('0', '0', '12', '11');
        $supplier_array[14]['delimiter'] = ",";

        /*$supplier_array[15]['name'] = "deklok";
        $supplier_array[15]['read_filename'] = "deklok47.csv";
        $supplier_array[15]['write_filename'] = "deklok";
        $supplier_array[15]['cell_1a'] = "DEKLOK";
        $supplier_array[15]['data_start_row'] = "0";
        $supplier_array[15]['mapping_array'] = array('2', '21', '19', '20');
        $supplier_array[15]['delimiter'] = ";";*/

        /*$supplier_array[16]['name'] = "etb";
        $supplier_array[16]['read_filename'] = "ZTG005.CSV";
        $supplier_array[16]['write_filename'] = "etb";
        $supplier_array[16]['cell_1a'] = "ETBWORCE";
        $supplier_array[16]['data_start_row'] = "1";
        $supplier_array[16]['mapping_array'] = array('1', '2', '39', '41');
        $supplier_array[16]['delimiter'] = ",";*/

        // $supplier_array[16]['name'] = "etb";
        // $supplier_array[16]['read_filename'] = "ZTG005.csv";
        // $supplier_array[16]['write_filename'] = "etb";
        // $supplier_array[16]['cell_1a'] = "ETB";
        // $supplier_array[16]['data_start_row'] = "1";
        // $supplier_array[16]['mapping_array'] = array('1', '1', '39', '40');
        // $supplier_array[16]['delimiter'] = ",";

        //$supplier_array[16]['name'] = "etb";
        //$supplier_array[16]['read_filename'] = "vrd-001-68409.csv";
        //$supplier_array[16]['write_filename'] = "etb";
        //$supplier_array[16]['cell_1a'] = "INTEURO";
        //$supplier_array[16]['data_start_row'] = "1";
        //$supplier_array[16]['mapping_array'] = array('0', '18', '14', '16');
        //$supplier_array[16]['delimiter'] = ";";


        //removed 2016-07-07 because merged tyresdirect with fedima [16]
        //$supplier_array[15]['name'] = "tyresdirect_fedima";
        //$supplier_array[15]['read_filename'] = "tyresdirect_fedima.csv";
        //$supplier_array[15]['write_filename'] = "tyresdirect_fedima";
        //$supplier_array[15]['cell_1a'] = "TYRDIR";
        //$supplier_array[15]['data_start_row'] = "1";
        //$supplier_array[15]['mapping_array'] = array('0', '0', '2', '1');
        //$supplier_array[15]['delimiter'] = ",";

        /*$supplier_array[17]['name'] = "COMPAUTO";
        $supplier_array[17]['read_filename'] = "stock.csv";
        $supplier_array[17]['write_filename'] = "compauto";
        $supplier_array[17]['cell_1a'] = "COMPAUTO";
        $supplier_array[17]['data_start_row'] = "1";
        $supplier_array[17]['mapping_array'] = array('0', '0', '13', '10');
        $supplier_array[17]['delimiter'] = ",";*/

        /*$supplier_array[18]['name'] = "BICKERS";
        $supplier_array[18]['read_filename'] = "trade_prices.csv";
        $supplier_array[18]['write_filename'] = "bickers";
        $supplier_array[18]['cell_1a'] = "BICKERS";
        $supplier_array[18]['data_start_row'] = "1";
        $supplier_array[18]['mapping_array'] = array('0', '0', '3', '2');
        $supplier_array[18]['delimiter'] = ",";*/

        $supplier_array[19]['name'] = "Wheelpros";
        $supplier_array[19]['read_filename'] = "wheelpros.csv";
        $supplier_array[19]['write_filename'] = "wheelpros";
        $supplier_array[19]['cell_1a'] = "WHEELPRO";
        $supplier_array[19]['data_start_row'] = "1";
        $supplier_array[19]['mapping_array'] = array('0', '0', '15', '21');
        $supplier_array[19]['delimiter'] = ",";

        if (!function_exists('tep_xls_to_csv_single_file')){
            function tep_xls_to_csv_single_file($inputfile, $outputfile) {

                $excel = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputfile);
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($excel, 'Csv');
                $writer->setDelimiter(",");
                $writer->setEnclosure("");
                $writer->save($outputfile);
            }
        }
        $oponeo_mapping_array = ['1', '2', '3', '8', '10', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '41', '42', '43'];

        foreach($supplier_array as $supplier_id => $supplier_data) {

            $row = 0;
            $write_array = array();
            $opneo_write_array = [];

            if(pathinfo($supplier_data['read_filename'], PATHINFO_EXTENSION) == 'xls' || pathinfo($supplier_data['read_filename'], PATHINFO_EXTENSION) == 'XLS') {
                tep_xls_to_csv_single_file(self::STOCK_FEED_LOCATION . 'Upload/' . $supplier_data['write_filename'] . '/' . $supplier_data['read_filename'], self::STOCK_FEED_LOCATION . 'Upload/' . $supplier_data['write_filename'] . '/' . $supplier_data['write_filename'] . '.csv');
                $supplier_data['read_filename'] = $supplier_data['write_filename'] . '.csv';
            }

            if (($handle = fopen(self::STOCK_FEED_LOCATION . 'Upload/' . $supplier_data['write_filename'] . '/' . $supplier_data['read_filename'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, $supplier_data['delimiter'])) !== FALSE) {
                    $filter = false; // Initially don't filter

                    if($supplier_id == 0 && $row == 0){
                        $oponeo_row = [];
                        foreach ($oponeo_mapping_array as $i) {
                            $oponeo_row[] = $data[$i];
                        }
                        $oponeo_row[] = 'PRICE'; // Hardcode last column because I can't think of a better way
                        $opneo_write_array[] = $oponeo_row;
                    }

                    if($row >= (int)$supplier_data['data_start_row']) {
                        $num = count($data);

                        if(in_array($supplier_data['name'], $this->filters)){
                            $filter = $this->filter_data($supplier_data['name'], $data);
                        }
                        if($filter===false){
                            for ($i=0, $n=sizeof($data_columns_array); $i<$n; $i++) {
                                if ($supplier_id == 13) {
                                    $write_array[$row][$data_columns_array[$i]] = str_replace("£","",$data[$supplier_data['mapping_array'][$i]]);
                                } else {
                                    $write_array[$row][$data_columns_array[$i]] = $data[$supplier_data['mapping_array'][$i]];
                                }
                            }

                            if($supplier_id == 0) {
                                $oponeo_row = [];
                                foreach ($oponeo_mapping_array as $i) {
                                    $oponeo_row[] = $data[$i];
                                }
                                $opneo_write_array[] = $oponeo_row;
                            }

                            //Supplier Part No. = A
                            //IP Code = C
                            //Cost = E
                            //Qty = F
                            //What we are saying is that when we create this file IF column C = BLANK then we populate with Column A instead
                            if($write_array[$row][$data_columns_array[1]] == '' || $write_array[$row][$data_columns_array[1]] == ' ') {
                                $write_array[$row][$data_columns_array[1]] = $write_array[$row][$data_columns_array[0]];
                            }

                            if($write_array[$row][$data_columns_array[3]] == '>  20') {
                                $write_array[$row][$data_columns_array[3]] = 50;
                            }

                            if($supplier_id == 12) {

                                if(strlen($write_array[$row][$data_columns_array[0]]) == 4) {
                                    $write_array[$row][$data_columns_array[0]] = 'WW'.$write_array[$row][$data_columns_array[0]];
                                }

                                if(strlen($write_array[$row][$data_columns_array[1]]) == 4) {
                                    $write_array[$row][$data_columns_array[1]] = 'WW'.$write_array[$row][$data_columns_array[1]];
                                }

                            }

                            if ($supplier_id == 13) {
                                $write_array[$row][$data_columns_array[2]] =  str_replace("£","",$write_array[$row][$data_columns_array[2]]);
                            }

                            if($supplier_id == 4) {
                                $write_array[$row][$data_columns_array[2]] = str_replace(",",".",$write_array[$row][$data_columns_array[2]]);
                            }

                            //if($supplier_id == 6) {
                            $write_array[$row][$data_columns_array[2]] = str_replace(",",".",$write_array[$row][$data_columns_array[2]]);
                            //}

                            //no price
                            //if($supplier_id == 15) {
                            //	$write_array[$row][$data_columns_array[3]] = '0';
                            //}

                            //if($supplier_id == 15) {
                            //	if($write_array[$row][$data_columns_array[1]] == '' || $write_array[$row][$data_columns_array[1]] == ' ') {
                            //	$write_array[$row][$data_columns_array[1]] = $write_array[$row][$data_columns_array[0]];
                            //	}
                            //}

                            if($supplier_id == 19){
                                $write_array[$row][$data_columns_array[2]] = round($write_array[$row][$data_columns_array[2]] * 0.75, 2);
                            }
                        }
                    }
                    $row++;
                }
                fclose($handle);
            }else{
                $this->errors[] = 'Could not open supplier stock file: <strong>' . $supplier_id['write_filename'] . '/' . $supplier_data['read_filename'] . '</strong>';
            }


            if(!empty($this->errors)){
                wp_redirect(get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '&fbf_importer_status=error&fbf_importer_message=' . $this->get_errors());
            }

            // Create new \PhpOffice\PhpSpreadsheet\Spreadsheet object
            //echo date('H:i:s') . " Create new \PhpOffice\PhpSpreadsheet\Spreadsheet object\n";
            $objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // Set properties
            //echo date('H:i:s') . " Set properties\n";
            $objPHPExcel->getProperties()->setCreator("4x4tyres");
            $objPHPExcel->getProperties()->setLastModifiedBy("4x4tyres");
            $objPHPExcel->getProperties()->setTitle("Stock Feed");
            $objPHPExcel->getProperties()->setSubject("Stock Feed");
            $objPHPExcel->getProperties()->setDescription("Stock Feed");

            // Add some data
            //echo date('H:i:s') . " Add some data\n";
            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()->SetCellValue('A1', $supplier_data['cell_1a']);

            $letter_array = array('a','b','c','d','e','f','g','h');
            $row = 3;
            foreach($write_array as $write_row) {

                //if($write_row[$data_columns_array[2]] >= 5 && (int)$write_row[$data_columns_array[3]] > 0) {
                if($write_row[$data_columns_array[2]] >= 5) { //removed qty check

                    if($supplier_id == 0) {
                        if($write_row[$data_columns_array[1]] == '') {
                            $write_row[$data_columns_array[1]] = $write_row[$data_columns_array[0]];
                        }
                    }

                    foreach($write_row as $k => $data) {

                        $csv_output = '';

                        if($k == 3) {
                            $objPHPExcel->getActiveSheet()->setCellValueExplicit($letter_array[($k-1)] . $row, $data, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        } else {
                            $objPHPExcel->getActiveSheet()->SetCellValue($letter_array[($k-1)] . $row, $data);
                        }
                    }
                    $row++;

                }
            }

            // Rename sheet
            //echo date('H:i:s') . " Rename sheet\n";
            $objPHPExcel->getActiveSheet()->setTitle('Simple');


            //unlink(STOCK_FEED_LOCATION . 'Excel/' . $supplier_data['write_filename'] . '.xlsx');
            // Save Excel 2007 file
            //echo date('H:i:s') . " Write to Excel2007 format\n";
            $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
            $objWriter->save(self::STOCK_FEED_LOCATION . 'Excel/' . $supplier_data['write_filename'] . '.xlsx');



            // Echo done
            //echo(date('H:i:s') .$supplier_data['write_filename'] . " Done writing file.<br />");

            // connect and login to FTP server
            //$ftp_server="waws-prod-db3-023.ftp.azurewebsites.windows.net";
            //$ftp_user_name="4x4tyres\website4x4tyres";
            //$ftp_user_pass="rjWdYsLzpfwT9gRJ";
            //$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
            //$login = ftp_login($ftp_conn, $ftp_user_name, $ftp_user_pass);
            //$file = STOCK_FEED_LOCATION . 'Excel/' . $supplier_data['write_filename'] . '.xlsx'; //to be uploaded
            //$remote_file = $supplier_data['write_filename'] . '.xlsx';
            //echo $file;
            //echo $remote_file;
            // upload file
            //if (ftp_put($ftp_conn, $remote_file, $file, FTP_ASCII))
            //  {
            //  echo("Successfully uploaded $file." . "<br />");
            //  }
            //else
            //  {
            //  echo("Error uploading $file." . "<br />");
            //  }

            // close connection
            //ftp_close($ftp_conn);


            $x = 2;

            // Duplicate Micheldever file, re-arrange columns and send to oponeo
            if($supplier_id === 0){
                // Read the data
                $oponeo_file = self::STOCK_FEED_LOCATION . 'Upload/oponeo/oponeo_prices.xlsx';
                if(file_exists($oponeo_file)){
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $spreadsheet = $reader->load($oponeo_file);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();

                    // loop through the oponeo data
                    foreach($opneo_write_array as $o_key => $o_data){
                        if($o_key !== 0){
                            $stcode = $o_data[0];
                            if($o_stcode_row = array_search($stcode, array_column($rows, 0))){
                                if($o_stcode_row){
                                    $opneo_write_array[$o_key][] = str_replace('£', '', $rows[$o_stcode_row][21]);
                                }
                            }else{
                                unset($opneo_write_array[$o_key]);
                            }
                        }
                    }
                }
                /*$spreadsheet = $reader->load(self::STOCK_FEED_LOCATION . 'Upload/oponeo/' . $supplier_data['write_filename'] . '.xlsx');

                $highestRow = $worksheet->getHighestRow(); // e.g. 10
                $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
                // Increment the highest column letter
                $highestColumn++;*/

                // Rearrange the columns
                /*$data = [];
                for ($row = 1; $row <= $highestRow; $row++) {
                    $row_data = [];
                    if($row > 2){
                        for ($col = 'A'; $col != $highestColumn; ++$col) {
                            if($col=='C'||$col=='F'){
                                $row_data[] = (string)$worksheet->getCell($col.$row)->getValue();
                            }
                        }
                        $data[] = $row_data;
                    }
                }*/

                // Save the sheet
                $opono_file = 'opono_ftp.csv';
                $opono_path = self::STOCK_FEED_LOCATION . 'Excel/' . $opono_file;
                $opono_ftp_path = 'stock/' . $opono_file;
                $mySpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $mySpreadsheet->getActiveSheet();
                $sheet->fromArray($opneo_write_array);
                $opono_writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($mySpreadsheet);
                $opono_writer->save($opono_path);

                // FTP it
                $ftp = ftp_connect('ftp.oponeo.pl');
                $login_result = ftp_login($ftp, '4x4tyresUK', 'Midh98476!626aAS');
                if (($ftp) && ($login_result)) {
                    $upload = ftp_put($ftp, $opono_ftp_path, $opono_path, FTP_BINARY);
                }
                ftp_close($ftp);

                // Delete the file
                unlink($opono_path);
            }
        }

        if(!empty($this->errors)){
            if($redirect){
                wp_redirect(get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '&fbf_importer_status=error&fbf_importer_message=' . $this->get_errors());
            }else{
                return false;
            }
        }else{
            if($redirect){
                wp_redirect(get_admin_url() . 'options-general.php?page=' . $this->plugin_name . '&fbf_importer_status=success&fbf_importer_message=' . $this->get_success());
            }else{
                return true;
            }
        }
    }

    private function get_errors()
    {
        return urlencode(implode('<br/>', $this->errors));
    }

    private function get_success()
    {
        return urlencode('<strong>Success</strong> - all supplier stock files processed' );
    }

    private function filter_data($supplier_name, $data)
    {
        $name = $supplier_name;
        if($supplier_name==='STAPLETONS'){
            if(str_contains(strtolower($data[2]), 'maxxis')){
                return false;
            }
        }
        return true;
    }
}
