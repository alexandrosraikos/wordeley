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

function article_list_html(array $articles = null)
{
    if (empty($articles)) {
        return show_alert('No articles were found matching your criteria.', 'notice');
    } else {
        $list = "";
        foreach ($articles as $article) {
            $authors = "";
            foreach ($article['authors'] as $key => $author) {
                if (!empty($author['scopus_author_id'])) {
                    $authors .= <<<HTML
                        <a href="https://www.scopus.com/authid/detail.uri?authorId={$author['scopus_author_id']}">{$author['first_name']} {$author['last_name']}</a>
                    HTML;
                } else {
                    $authors .= $author['first_name'] . " " . $author['last_name'];
                }
                if ($key != count($article['authors'])) {
                    $authors .= ", ";
                }
            }
            $list .= <<<HTML
                <li>
                    <h3>{$article['title']}</h3>
                    <div class="metadata">
                        <span class="authors"><span class="label">Authors:</span> {$authors}</span>
                        <span class="source"><span class="label">Source:</span> {$article['source']}</span>
                        <span class="doi"><span class="label">DOI:</span> {$article['identifiers']['doi']}</span>
                        <span class="year"><span class="label">Year:</span> {$article['year']}</span>
                    </div>
                    <div class="information">
                        <a href="{$article['link']}">View more information &rarr;</a>
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
function catalogue_shortcode_html($authors = null, $articles = null)
{
    $list = article_list_html($articles ?? null);

    return <<<HTML
        <div class="wordeley-catalogue">
            <div class="wordeley-catalogue-filters">
                <h2>Filters</h2>
            </div>
            <div class="wordeley-catalogue-list">
                <ul>
                    {$list}
                </ul>
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
