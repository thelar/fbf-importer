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
    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
    <p style="border: 1px solid black; padding: 0.5em; margin: 0;">Status: <?=$this->display_status()?></p>
    <form action="options.php" method="post">
        <?php
        settings_fields( $this->plugin_name );
        do_settings_sections( $this->plugin_name );
        submit_button();
        ?>
    </form>

    <!--<form action="<?php /*echo admin_url('admin-post.php'); */?>" method="post" style="margin-bottom: 1em">
        <input type="hidden" name="action" value="fbf_importer_run_import">
        <input type="submit" value="Standard Cost & Supplier Stock Qty Import" class="button-primary">
    </form>-->

    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" style="margin-bottom: 1em">
        <input type="hidden" name="action" value="fbf_importer_process_stock">
        <input type="submit" value="Process Supplier Stock" class="button-primary">
    </form>

    <!--<button type="button" id="start-import" class="button-primary">
        Start Import
    </button>

    <button type="button" id="import-state" class="button-primary">
        Get State
    </button>

    <button type="button" id="stop-import" class="button-primary">
        Stop Import
    </button>-->

    <?php $this->display_log_table(); ?>
</div>
