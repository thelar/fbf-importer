<?php

class Fbf_Importer_Logger
{
    private $plugin_name;
    private $id; // The id of the log
    private $option_name = 'fbf_importer';
    private $table_name;

    public function __construct($plugin_name)
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fbf_importer_log';
        $this->plugin_name = $plugin_name;

        // Set the $id to the log_id from the option if it exists
        if(isset(get_option($this->plugin_name)['log_id'])){
            $this->id = get_option($this->plugin_name)['log_id'];
        }
    }

    public function start()
    {
        global $wpdb;

        $et = new DateTime();
        $etz = new DateTimeZone('Europe/London');
        $et->setTimezone($etz);
        $offset = $et->getOffset();
        $start_time = $et->getTimestamp();

        // Create a new entry in the log and grab the id
        $id = $wpdb->insert(
            $this->table_name,
            [
                'starttime' => date('Y-m-d H:i:s', $start_time + $offset),
            ]
        );
        return $wpdb->insert_id;
    }

    private function log_option_id($id)
    {
        $option = get_option($this->plugin_name);
        $option+= ['log_id' => $id];
        update_option($this->plugin_name, $option);
    }

    public function log_info($stage, $info, $id, $complete = false)
    {
        global $wpdb;
        $q = $wpdb->prepare("SELECT log FROM {$this->table_name}
            WHERE id = %s", $id);
        $log = $wpdb->get_row($q);

        $dt = new DateTime();
        $tz = new DateTimeZone("Europe/London");
        $dt->setTimezone($tz);
        $offset = $dt->getOffset();
        $info['end'] = $dt->getTimestamp();

        if(empty($log->log)){
            $current_log = [];
        }else{
            $current_log = unserialize($log->log);
        }

        $current_log[$stage] = $info;

        $data = [
            'endtime' => date('Y-m-d H:i:s', $dt->getTimestamp() + $offset),
            'log' => serialize($current_log),
        ];
        if($complete){
            $data+= ['success' => true];
        }

        $wpdb->update(
            $this->table_name,
            $data,
            [
                'id' => $id,
            ]
        );
        return $id;
    }
}
