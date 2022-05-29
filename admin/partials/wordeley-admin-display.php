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
    <h1>Wordeley Settings</h1>
    <p>This is the options page for the Wordeley plugin.</p>
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
    echo '<p>Insert your Mendeley Developer API credentials below. Not registered a Mendeley application yet? Go to the <a href="https://dev.mendeley.com/myapps.html" target="blank">Mendeley Developer Portal</a>.</p>';
}

function wordeley_plugin_application_id()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['application_id'] ?? null;
?>
    <input type="text" name="wordeley_plugin_settings[application_id]" value="<?php echo ((!empty($value)) ? $value : '') ?>" />
    <p class="description">
        The numerical ID of the application registered in the Mendeley Developer Portal.
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
        The application secret generated when creating a Mendeley application.
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
    <button class="button" action="wordeley-generate-access-token" <?= (!$enabled) ? 'disabled' : '' ?>>Generate</button>
    <p class="description">
        <?= (empty($value)) ? "The access token can be generated after entering your credentials." : "Your application's access token generated via the Mendeley API, valid for " . $token_expires_in . " and it will be refreshed automatically." ?>
    </p>
<?php
}

function wordeley_plugin_section_two()
{
    echo '<p>Fill in your catalogue options to start retrieving article data.</p>';
}

function wordeley_plugin_article_authors()
{
    $options = get_option('wordeley_plugin_settings');
    $value = $options['article_authors'] ?? "";
?>
    <textarea name="wordeley_plugin_settings[article_authors]" cols=25"><?= $value ?></textarea>
    <p class="description">
        Use a comma (,) to separate multiple author entries.
    </p>
<?php
}
