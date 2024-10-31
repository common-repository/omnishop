<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://omnishopapp.com
 * @since      1.0.0
 *
 * @package    Omnishop
 * @subpackage Omnishop/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<h1>OmniShop Home Page sections setup</h1>

<div class="wrap">
    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php 
            settings_fields('omnishop_plugin');
            do_settings_sections('omnishop_plugin');
            submit_button();
        ?>
    </form>
</div>