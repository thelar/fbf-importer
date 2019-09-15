<?php
// Show Errors

class Fbf_Importer_Error_Reporting {
    // Variables
    public $allErrors;
    public $errorReportEmail;
    //
    public function fbf_report_any_errors() {
        // Count Errors, if any
        $tempFileExists = $this->fbf_count_errors('File exists');
        $tempFileValid = $this->fbf_count_errors('File valid');
        //
        if ($tempFileExists > 0 || $tempFileValid > 0) {
            $this->fbf_send_error_email();
        }
    }
    //
    private function fbf_count_errors($keyName) {
        //
        if ( array_key_exists($keyName, $this->allErrors) ) {
            if(!is_null($this->allErrors[$keyName])){
                $errorNum = count($this->allErrors[$keyName]);
            }else{
                $errorNum = 0;
            }
        } else {
            $errorNum = 0;
        }
        //
        return $errorNum;
    }
    //
    private function fbf_send_error_email() {
        //
        // Set Up Message Data - Present Array in a Readable Format
        $errorDataForEmail = '';
        foreach( $this->allErrors as $aKey => $value ) {
            $errorDataForEmail .= "<br>";
            if ( is_array($value) ){
                $errorDataForEmail .= '<b style="font-size: 25px;">'.$aKey.'</b><br>';
                if ( count($value) == 0 ) {
                    $errorDataForEmail .= 'No errors reported<br>';
                } else {
                    foreach($value as $singleKey => $singleValue) {
                        if ( is_array($singleValue) ) {
                            $errorDataForEmail .= '<br><b>'.$singleKey.'</b><br>';
                            if ( count($singleValue) == 0 ) {
                                $errorDataForEmail .= 'No errors reported<br>';
                            } else {
                                foreach($singleValue as $singleValueData) {
                                    $errorDataForEmail .= $singleValueData.'<br>';
                                }
                            }
                        } else {
                            if ( empty($singleValue) ) {
                                $errorDataForEmail .= 'No errors reported<br>';
                            } else {
                                $errorDataForEmail .= $singleValue.'<br>';
                            }
                        }
                    }
                }
            } else {
                if ( empty($value) ) {
                    $errorDataForEmail .= '<b style="font-size: 25px;">'.$aKey.'</b><br>No errors reported<br>';
                } else {
                    $errorDataForEmail .= '<b style="font-size: 25px;">'.$aKey.'</b><br>'.$value.'<br>';
                }
            }
        }
        //
        // Get Date and Time of Error
        $reportTime = "Import Done: ".date('dS F Y - G:i');
        //
        // Prep the Email
        $from = 'web.alerts@4x4tyres.co.uk';
        $to = $this->errorReportEmail;
        $subject = '4x4 - Import Error Report';
        $headers = 'From: web.alerts@4x4tyres.co.uk' . "\r\n";
        $headers .= 'Reply-To: donotreply@4x4tyres.co.uk' . "\r\n";
        $headers .= "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1" . "\r\n";
        $message = '<html><body><p>'.$reportTime.'</p>'.$errorDataForEmail.'</body></html>';
        //
        // Send the eMail
        $sendMail = mail($to, $subject, $message, $headers, "-f".$from);
    }
}
?>
