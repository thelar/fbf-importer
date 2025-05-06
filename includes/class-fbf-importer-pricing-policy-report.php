<?php
/**
 * Class responsible for generating pricing policy report.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */

use PhpOffice\PhpSpreadsheet\IOFactory;

class Fbf_Importer_Pricing_Policy_Report {

    public function __construct($plugin_name)
    {
        global $wp;
        $this->plugin_name = $plugin_name;
    }

    public function get_report()
    {
        return [
            [1,2,3],
            [4,5,5]
        ];
    }
}
