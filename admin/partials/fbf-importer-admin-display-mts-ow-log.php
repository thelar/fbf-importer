<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Importer
 * @subpackage Fbf_Importer/admin/partials
 */

?>

<div class="wrap">
    <h2>Details for MTS to OW log #<?=$_GET['log_id']?></h2>

    <?php $this->display_mts_ow_log(); ?>
</div>
