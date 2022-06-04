<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/admin/partials
 */

function render_settings_page()
{
?>
    <h1><?= __("Wordeley Settings", 'wordeley') ?></h1>
    <p><?= __("This is the options page for the Wordeley plugin.", 'wordeley') ?></p>
    <form action="options.php" method="post">
        <?php
        settings_fields('wordeley_plugin_settings');
        do_settings_sections('wordeley_plugin');
        ?>
        <br />
        <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save'); ?>" />
    </form>
<?php
}

function wordeley_plugin_section_one()
{
    echo __('<p>Insert your Mendeley Developer API credentials below. Not registered a Mendeley application yet? Go to the <a href="https://dev.mendeley.com/myapps.html" target="blank">Mendeley Developer Portal</a>.</p>', 'wordeley');
}

function wordeley_plugin_application_id()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['application_id'] ?? null;
?>
    <input type="text" name="wordeley_plugin_settings[application_id]" value="<?php echo ((!empty($value)) ? $value : '') ?>" />
    <p class="description">
        <?= __("The numerical ID of the application registered in the Mendeley Developer Portal.", 'wordeley') ?>
    </p>
<?php
}

function wordeley_plugin_application_secret()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['application_secret'] ?? null;
?>
    <input type="text" name="wordeley_plugin_settings[application_secret]" value="<?php echo ((!empty($value)) ? $value : '') ?>" />
    <p class="description">
        <?= __("The application secret generated when creating a Mendeley application.", 'wordeley') ?>
    </p>
<?php
}

function wordeley_plugin_api_access_token()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['api_access_token'] ?? null;
    $enabled = !empty($options['application_id'] ?? null) && !empty($options['application_secret'] ?? null);

    if (!empty($options['api_access_token_expires_at'])) {
        $token_expires_in = gmdate("H:i:s", ($options['api_access_token_expires_at'] - time()));
    }
?>
    <input type="text" readonly="readonly" name="wordeley_plugin_settings[api_access_token]" value="<?php echo ((!empty($value)) ? $value : '') ?>" />
    <button class="button" action="wordeley-generate-access-token" <?= (!$enabled) ? 'disabled' : '' ?>>
        <?= (empty($value)) ? 'Generate' : 'Refresh' ?>
    </button>
    <p class="description">
        <?= (empty($value)) ?
            __("The access token can be generated after entering your credentials.", 'wordeley') :
            sprintf(__("Your application's access token generated via the Mendeley API, valid for %s and it will be refreshed automatically.", 'wordeley'), $token_expires_in) ?>
    </p>
<?php
}

function wordeley_plugin_api_access_token_automatic()
{
    $options = get_option('wordeley_plugin_settings');
    $checked = ($options['api_access_token_automatic'] ?? '') == 'on' ? 'checked' : '';
?>
    <input type="checkbox" name="wordeley_plugin_settings[api_access_token_automatic]" <?= $checked ?> />
    <p>
        <?= __("The token will be refreshed automatically every hour.", 'wordeley') ?>
    </p>
<?php
}

function wordeley_plugin_section_two()
{
    echo '<p>' . __("Fill in your catalogue options to start retrieving article data.", 'wordeley') . '</p>';
}

function wordeley_plugin_article_authors()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['article_authors'] ?? "";
?>
    <textarea name="wordeley_plugin_settings[article_authors]" cols="20" rows="10""><?= $value ?></textarea>
    <p class=" description">
        <?= __("Use a comma (,) to separate multiple author entries.", 'wordeley') ?>
    </p>
<?php
}


function wordeley_plugin_section_three()
{
    echo '<p>' . __("The cache will be automatically refreshed every 30 days.", 'wordeley') . '</p>';
}

function wordeley_plugin_refresh_cache()
{
    if (file_exists(WORDELEY_FILE_STORE . 'articles.json')) {
        $last_modified = filemtime(WORDELEY_FILE_STORE . 'articles.json');
        $last_modified = get_date_from_gmt(date('Y-m-d H:i:s', $last_modified), 'd-m-Y H:i:s');
    }
?>
    <?php
    if (empty($last_modified)) {
    ?>
        <button action="wordeley-refresh-cache" class="button"><?= __("Build Cache", 'wordeley') ?></button>
        <p><?= __("Click to build the article cache manually.", 'wordeley') ?></p>
    <?php
    } else {
    ?>
        <button action="wordeley-refresh-cache" class="button"><?= __("Refresh Cache", 'wordeley') ?></button>
        <p><?= sprintf(__("Click to refresh the article cache manually. Last modified at %s", 'wordeley'), $last_modified) ?></p>
    <?php
    }
    ?>
<?php
}

function wordeley_plugin_delete_cache()
{
?>
    <button action="wordeley-clear-cache" class="button is-destructive"><?= __("Clear Cache", 'wordeley') ?></button>
    <p><?= __("Click to clear the article cache manually.", 'wordeley') ?></p>
<?php
}



function wordeley_plugin_refresh_cache_automatic()
{
    $options = get_option('wordeley_plugin_settings');
    $checked = ($options['refresh_cache_automatic'] ?? '') == 'on' ? 'checked' : '';
?>
    <input type="checkbox" name="wordeley_plugin_settings[refresh_cache_automatic]" <?= $checked ?> />
    <p>
        <?= __("The cache will be automatically refreshed every 30 days.", 'wordeley') ?>
    </p>
<?php
}
