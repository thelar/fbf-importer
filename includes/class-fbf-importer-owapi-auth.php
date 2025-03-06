<?php

class Fbf_Importer_Owapi_Auth
{
    const AUTH_URI = 'https://4x4tyres.orderwisecloud.com/owapi/';
    const CLIENT_USERNAME = 'API';
    const CLIENT_PASSWORD = '4x4Tyres7848!!';
    const TOKEN_EXPIRY_DAYS = 28; //OW auth tokens are good for 30 days, setting to 28 for safety

    static $config_file;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        self::$config_file = get_template_directory() . '/../config/owapi_oauthtokens.txt';
    }

    public function get_valid_token()
    {
        //First check for config file
        if (!file_exists(self::$config_file)) {
            file_put_contents(self::$config_file, '');
        }
        $fh = file_get_contents(self::$config_file, 'r');
        $a = unserialize($fh);
        if($a===false){
            $m = $this->mint_token();
            if($m){
                return $m['access_token'];
            }
            return false;
        }else{
            $now = time();
            if($a['access_token_expires'] > $now){
                return $a['access_token'];
            }else{
                $m = $this->mint_token();
                if($m){
                    return $m['access_token'];
                }
                return false;
            }
        }
    }

    private function mint_token()
    {
        //refresh token
        $auth = base64_encode(self::CLIENT_USERNAME.':'.self::CLIENT_PASSWORD);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::AUTH_URI . 'token/gettoken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $auth
            ),
        ));

        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return false;
        }else{
            $r = json_decode($result);
            if(property_exists($r, 'error')) {
                return false;
            }else{
                // Here if successfully minted new token
                $a = [
                    'access_token' => $r,
                    //'access_token_expires' => time() + (self::TOKEN_EXPIRY_DAYS * DAY_IN_SECONDS), Commented out so that it mints a new token every time - for some reason setting to 28 days wasn't working
                    'access_token_expires' => time(),
                ];
                file_put_contents(self::$config_file, serialize($a));
                return $a;
            }
        }
        curl_close($ch);
    }

    private function get_token()
    {
        $fh = file_get_contents(self::$config_file, 'r');
        $a = unserialize($fh);
        return $a['access_token'];
    }
}
