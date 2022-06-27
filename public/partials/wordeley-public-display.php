<?php

use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/public/partials
 */

function article_filters_html(
    array $authors = null,
    int $oldest_year = null,
    array $author_article_total = null,
) {
    if (empty($authors) || empty($authors[0])) {
        return show_alert('No authors were configured.');
    } else {

        // Author values.
        $checkboxes = "";
        $total_articles = 0;
        $active_authors = $_GET['authors'] ?? $authors;
        foreach ($authors as $author) {
            $checked = in_array($author, $active_authors ?? []) ? 'checked' : '';
            $author_label = !empty($author_article_total) ? ($author . ' (' . $author_article_total[$author] . ')') : $author;
            $checkboxes .= <<<HTML
                <label>
                    <input type="checkbox" name="authors[]" value="{$author}" id="" {$checked} /> {$author_label}
                </label>
            HTML;
            $total_articles += $author_article_total[$author];
        };

        // Year values.
        $oldest_year = $oldest_year ?? 1975;
        $current_year = date("Y");
        $selected_starting_year = $_GET['starting-year'] ?? null;
        $selected_ending_year = $_GET['ending-year'] ?? null;

        // Page size selector options. 
        $page_size_options = [15, 25, 50];
        $page_size_options_html = "";
        for ($j = 0; $j <= count($page_size_options) - 1; $j++) {
            $selected = ($page_size_options[$j] == ($_GET['articles-per-page'] ?? 10)) ? 'selected' : '';
            $page_size_options_html .= <<<HTML
            <option value="{$page_size_options[$j]}" {$selected}>{$page_size_options[$j]}</option>
            HTML;
        }

        // Search value.
        $search_value = $_GET['article-search'] ?? '';

        // Labels.
        $search_label = __('Search', 'wordeley');
        $search_placeholder_label = __('Type a search term', 'wordeley');
        $authors_label = __('Authors', 'wordeley');
        $years_label = __('Years', 'wordeley');
        $years_from_label = __('From', 'wordeley');
        $years_to_label = __('To', 'wordeley');
        $view_label = __('View', 'wordeley');
        $articles_per_page_label = __('Articles per page', 'wordeley');
        $submit_label = __('Submit', 'wordeley');

        // Print form.
        return <<<HTML
            <form action="" method="get">
                <h4>{$search_label}</h4>
                <input type="text" name="article-search" value="{$search_value}" placeholder="{$search_placeholder_label}"/>
                <h4>{$authors_label}</h4>
                {$checkboxes}
                <h4>{$years_label}</h4>
                <div class="years-filter">
                    <label>
                        {$years_from_label}
                    <input type="number" name="starting-year" min="{$oldest_year}" max="{$current_year}" placeholder="{$oldest_year}" value="{$selected_starting_year}">
                    </label>
                    <label>
                        {$years_to_label}
                        <input type="number" name="ending-year" min="1970" max="{$current_year}" placeholder="{$current_year}" value="{$selected_ending_year}">
                    </label>
                </div>
                <h4>{$view_label}</h4>
                <label>
                {$articles_per_page_label}
                <select name="articles-per-page">
                    {$page_size_options_html}
                </select>
                </label>
                <button type="submit">{$submit_label}</button>
            </form>
        HTML;
    }
}

function article_list_html(array $articles = null)
{
    if (empty($articles['content'])) {
        return show_alert('No articles were found matching your criteria.', 'notice');
    } else {
        $list = "";
        foreach ($articles['content'] as $article) {
            $authors = "";
            foreach ($article['authors'] as $key => $author) {
                if (!empty($author['first_name']) && !empty($author['last_name'])) {
                    if (!empty($author['scopus_author_id'])) {
                        $authors .= <<<HTML
                        <a href="https://www.scopus.com/authid/detail.uri?authorId={$author['scopus_author_id']}">{$author['first_name']} {$author['last_name']}</a>
                    HTML;
                    } else {
                        $authors .= $author['first_name'] . " " . $author['last_name'];
                    }
                }
                if ($key != count($article['authors'])) {
                    $authors .= ", ";
                }
            }

            $source_label = __('Source', 'wordeley');

            $metadata_source = (!empty($article['source'])) ? <<<HTML
                <span class="source"><span class="label">{$source_label}:</span> {$article['source']}</span>
                HTML : '';
            $metadata_doi = (!empty($article['identifiers']['doi'])) ? <<<HTML
                <span class="doi"><span class="label">DOI:</span> {$article['identifiers']['doi']}</span>
                HTML : '';

            $view_more_label = __('View more information', 'wordeley');
            $authors_label = __('Authors', 'wordeley');
            $year_label = __('Year', 'wordeley');
            $list .= <<<HTML
                <li>
                    <h3>{$article['title']}</h3>
                    <div class="metadata">
                        <span class="authors"><span class="label">{$authors_label}:</span> {$authors}</span>
                        {$metadata_source}
                        {$metadata_doi}        
                        <span class="year"><span class="label">{$year_label}:</span> {$article['year']}</span>
                    </div>
                    <div class="information">
                        <a href="{$article['link']}" target="blank">{$view_more_label} &rarr;</a>
                    </div>
                </li>
            HTML;
        }

        return $list;
    }
}

/**
 * The HTML view of the article catalogue.
 * @since  1.0.0
 */
function catalogue_shortcode_html($authors = null, $articles = null, $author_statistics = null, int $total_articles = 0)
{
    $list = article_list_html($articles ?? null);
    $filters = article_filters_html(
        $authors ?? null,
        $articles['oldest_year'] ?? 1975,
        $author_statistics ?? null
    );

    // Append page selector.
    global $wp;
    $page_selector = "";
    $current_url = home_url($wp->request);
    for ($i = 1; $i < $articles['total_pages']; $i++) {
        $page_url = $current_url . '?article-page=' . $i;
        $html_class = ($_GET['article-page'] ?? '1' == $i) ? 'active' : '';
        $page_selector .= <<<HTML
            <li><a href="{$page_url}" class="{$html_class}">$i</a></li>
        HTML;
    }
    $page_selector = <<<HTML
        <ul class="wordeley-pagination">
            {$page_selector}
        </ul>
    HTML;

    $total_articles_label = __('total articles', 'wordeley');

    return <<<HTML
        <div class="wordeley-catalogue">
            <div class="wordeley-catalogue-filters">
                {$filters}
            </div>
            <div class="wordeley-catalogue-list">
                <div class="wordeley-total-article-label">
                    {$total_articles} {$total_articles_label}
                </div>
                <ul>
                    {$list}
                </ul>
                {$page_selector}
            </div>
        </div>  
    HTML;
}

/**
 *
 * Prints an auto-disappearing error or notice box.
 * The close button is handled @see policycloud-marketplace-public.js
 *
 * @param string $message The message to be shown.
 * @param bool $dismissable Whether the alert is dismissable or not.
 * @param string $type The type of message, a 'notice' or an 'error'.
 *
 * @since 1.0.0
 */
function show_alert(string $message, string $type = 'error')
{
    return '<div class="wordeley-notice wordeley-' . $type . '">' . $message . '</div>';
}
