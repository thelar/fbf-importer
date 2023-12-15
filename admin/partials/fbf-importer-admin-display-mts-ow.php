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
    <p>Status: <span id="mts-ow-status"></span></p>

    <div>
        <button class="button button-primary" type="button" role="button" id="mts-ow-start" disabled>
            Start
        </button>
    </div>
</div>
