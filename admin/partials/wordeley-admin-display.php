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

/**
 * Render the settings page.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function render_settings_page()
{   ?>
	<h1><?php echo esc_html(__('Wordeley Settings', 'wordeley')); ?></h1>
	<p><?php echo esc_html(__('This is the options page for the Wordeley plugin.', 'wordeley')); ?></p>
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

/**
 * Render the settings' section one description.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_section_one()
{
	echo '<p>' . __('Insert your Mendeley Developer API credentials below. Not registered a Mendeley application yet? Go to the <a href="https://dev.mendeley.com/myapps.html" target="blank">Mendeley Developer Portal</a>.', 'wordeley') . '</p>';
}

/**
 * Render the settings' Application ID field.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_application_id()
{
	$options = get_option('wordeley_plugin_settings');
	$value   = $options['application_id'] ?? null;
?>
	<input type="text" name="wordeley_plugin_settings[application_id]" value="<?php echo ((!empty($value)) ? esc_html($value) : ''); ?>" />
	<p class="description">
		<?php echo esc_html(__('The numerical ID of the application registered in the Mendeley Developer Portal.', 'wordeley')); ?>
	</p>
<?php
}

/**
 * Render the settings' Application Secret field.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_application_secret()
{
	$options = get_option('wordeley_plugin_settings');
	$value   = $options['application_secret'] ?? null;
?>
	<input type="text" name="wordeley_plugin_settings[application_secret]" value="<?php echo ((!empty($value)) ? esc_html($value) : ''); ?>" />
	<p class="description">
		<?php echo esc_html(__('The application secret generated when creating a Mendeley application.', 'wordeley')); ?>
	</p>
<?php
}

/**
 * Render the settings' section two description.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_section_two()
{
	echo '<p>' . esc_html(__('Fill in your catalogue options to start retrieving article data.', 'wordeley')) . '</p>';
}

/**
 * Render the settings' article authors catalogue filter.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_article_authors()
{
	$options = get_option('wordeley_plugin_settings');
	$value   = $options['article_authors'] ?? '';
?>
	<textarea name="wordeley_plugin_settings[article_authors]" cols="20" rows="10""><?php echo esc_html($value); ?></textarea>
	<p class=" description">
		<?php echo esc_html(__('Use a comma (,) to separate multiple author entries.', 'wordeley')); ?>
	</p>
	<?php
}

/**
 * Render the settings' section three description.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_section_three()
{
	echo '<p>' . esc_html(__('The cache will be automatically refreshed every 15 days.', 'wordeley')) . '</p>';
}

/**
 * Render the settings' cache refresh button.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_refresh_cache()
{
	if (file_exists(WORDELEY_FILE_STORE . 'articles.json')) {
		$last_modified = filemtime(WORDELEY_FILE_STORE . 'articles.json');
		$last_modified = get_date_from_gmt(gmdate('Y-m-d H:i:s', $last_modified), 'd-m-Y H:i:s');
	}
	?>
	<?php
	if (empty($last_modified)) {
	?>
		<button action="wordeley-refresh-cache" class="button"><?php echo esc_html(__('Build Cache', 'wordeley')); ?></button>
		<p><?php echo esc_html(__('Click to build the article cache manually.', 'wordeley')); ?></p>
		<?php
	} else {
		?>
		<button action="wordeley-refresh-cache" class="button"><?php echo esc_html(__('Refresh Cache', 'wordeley')); ?></button>
		<p><?php echo esc_html(sprintf(__('Click to refresh the article cache manually. Last modified at %s', 'wordeley'), $last_modified)); ?></p>
		<?php
	}
		?>
	<?php
}

/**
 * Render the settings' clear cache button
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_clear_cache()
{
	?>
	<button action="wordeley-clear-cache" class="button is-destructive"><?php echo esc_html(__('Clear Cache', 'wordeley')); ?></button>
	<p><?php echo esc_html(__('Click to clear the article cache manually.', 'wordeley')); ?></p>
	<?php
}


/**
 * Render the settings' automatic cache refresh checkbox.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function wordeley_plugin_refresh_cache_automatic()
{
	$options = get_option('wordeley_plugin_settings');
	$checked = ('on' === ($options['refresh_cache_automatic'] ?? '')) ? 'checked' : '';
	?>
	<input type="checkbox" name="wordeley_plugin_settings[refresh_cache_automatic]" <?php echo esc_html($checked); ?> />
	<p>
		<?php echo esc_html(__('The cache will be automatically refreshed every 15 days.', 'wordeley')); ?>
	</p>
	<?php
}
