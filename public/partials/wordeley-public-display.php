<?php
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

/**
 * Generates the article filters HTML.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param array  $author_information The formatted array of author information.
 * @param string $query An active search term.
 * @param int    $oldest_year The oldest year of available articles.
 * @param int    $current_year The current year.
 * @param int    $start_year The first year on a selected year range.
 * @param int    $end_year The last year on a selected year range.
 * @param int    $page_size The number of articles in a page.
 * @return string The HTML article filters.
 */
function article_filters_html(
	array $author_information,
	string $query = null,
	int $oldest_year,
	int $current_year,
	int $start_year = null,
	int $end_year = null,
	int $page_size = null
) {
	if ( empty( $author_information ) || empty( $author_information[0] ) ) {
		return show_alert( 'No authors were configured.' );
	} else {

		// Author values.
		$checkboxes = '';
		foreach ( $author_information as $author ) {
			$checked                    = $author['selected'] ? 'checked' : '';
			$author_article_count_label = $author['selected'] ? ' (' . $author['article_count'] . ')' : '';
			$checkboxes                .= <<<HTML
                <label class="wordeley-article-catalogue-filters-author">
                    <input type="checkbox" name="authors[]" value="{$author['name']}" id="" {$checked} /> {$author['name']}{$author_article_count_label}
                </label>
            HTML;
		};

		// Page size selector options.
		$page_size_options       = array( 15, 25, 50 );
		$page_size_options_html  = '';
		$page_size_options_count = count( $page_size_options );
		for ( $j = 0; $j <= $page_size_options_count - 1; $j++ ) {
			$selected                = ( ( $page_size ) === $page_size_options[ $j ] ) ? 'selected' : '';
			$page_size_options_html .= <<<HTML
            <option value="{$page_size_options[$j]}" {$selected}>{$page_size_options[$j]}</option>
            HTML;
		}

		// Labels.
		$search_label             = __( 'Search', 'wordeley' );
		$search_placeholder_label = __( 'Type a search term', 'wordeley' );
		$authors_label            = __( 'Authors', 'wordeley' );
		$years_label              = __( 'Years', 'wordeley' );
		$years_from_label         = __( 'From', 'wordeley' );
		$years_to_label           = __( 'To', 'wordeley' );
		$view_label               = __( 'View', 'wordeley' );
		$articles_per_page_label  = __( 'Articles per page', 'wordeley' );
		$submit_label             = __( 'Submit', 'wordeley' );

		$start_year = empty( $start_year ) ? '' : $start_year;
		$end_year   = empty( $end_year ) ? '' : $end_year;

		// Print form.
		return <<<HTML
            <form action="" method="get">
                <h4>{$search_label}</h4>
                <input type="text" name="article-search" value="{$query}" placeholder="{$search_placeholder_label}"/>
                <h4>{$authors_label}</h4>
                {$checkboxes}
                <h4>{$years_label}</h4>
                <div class="wordeley-article-catalogue-filters-years">
                    <label>
                        {$years_from_label}
                    <input type="number" name="starting-year" min="{$oldest_year}" max="{$current_year}" placeholder="{$oldest_year}" value="{$start_year}">
                    </label>
                    <label>
                        {$years_to_label}
                        <input type="number" name="ending-year" min="{$oldest_year}" max="{$current_year}" placeholder="{$current_year}" value="{$end_year}">
                    </label>
                </div>
                <h4>{$view_label}</h4>
                <label class="wordeley-article-catalogue-page-size">
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

/**
 * Generates the article list HTML.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param array $articles The requested articles.
 * @return string The article list HTML.
 */
function article_list_html( array $articles = array() ) {
	if ( empty( $articles ) ) {
		return show_alert( 'No articles were found matching your criteria.', 'notice' );
	} else {
		$list = '';
		foreach ( $articles as $article ) {
			$authors = '';
			foreach ( $article['authors'] as $key => $author ) {
				if ( ! empty( $author['first_name'] ) && ! empty( $author['last_name'] ) ) {
					if ( ! empty( $author['scopus_author_id'] ) ) {
						$authors .= <<<HTML
                        <a href="https://www.scopus.com/authid/detail.uri?authorId={$author['scopus_author_id']}">{$author['first_name']} {$author['last_name']}</a>
                    HTML;
					} else {
						$authors .= $author['first_name'] . ' ' . $author['last_name'];
					}
				}
				if ( count( $article['authors'] ) !== $key ) {
					$authors .= ', ';
				}
			}

			$source_label = __( 'Source', 'wordeley' );

			$metadata_source = ( ! empty( $article['source'] ) ) ? <<<HTML
                <span class="source"><span class="label">{$source_label}:</span> {$article['source']}</span>
                HTML : '';
			$metadata_doi    = ( ! empty( $article['identifiers']['doi'] ) ) ? <<<HTML
                <span class="doi"><span class="label">DOI:</span> {$article['identifiers']['doi']}</span>
                HTML : '';

			$view_more_label = __( 'View more information', 'wordeley' );
			$authors_label   = __( 'Authors', 'wordeley' );
			$year_label      = __( 'Year', 'wordeley' );
			$list           .= <<<HTML
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
 * Generates the article catalogue HTML.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param array  $articles The requested articles.
 * @param array  $author_information The formatted array of author information.
 * @param string $query An active search term.
 * @param int    $oldest_year The oldest year of available articles.
 * @param int    $current_year The current year.
 * @param int    $start_year The first year on a selected year range.
 * @param int    $end_year The last year on a selected year range.
 * @param int    $page_size The number of articles in a page.
 * @param int    $page The page number.
 * @param int    $total_articles The number of total articles.
 * @param int    $total_pages The number of total pages.
 * @return string The HTML article filters.
 */
function catalogue_shortcode_html(
	array $articles = null,
	array $author_information = null,
	string $query = null,
	int $oldest_year,
	int $current_year,
	int $start_year = null,
	int $end_year = null,
	int $page_size,
	int $page,
	int $total_articles,
	int $total_pages
) {
	$list    = article_list_html( $articles ?? null );
	$filters = article_filters_html(
		$author_information,
		$query,
		$oldest_year,
		$current_year,
		$start_year,
		$end_year,
		$page_size,
	);

	// Append page selector.
	global $wp;
	$page_selector = '';
	$current_url   = home_url( $wp->request );
	for ( $i = 1; $i < $total_pages; $i++ ) {
		$page_url       = $current_url . '?article-page=' . $i;
		$html_class     = ( $page == $i ) ? 'active' : '';
		$page_selector .= <<<HTML
            <li><a href="{$page_url}" class="{$html_class}" wordeley-article-page="$i">$i</a></li>
        HTML;
	}
	$page_selector = <<<HTML
        <ul class="wordeley-article-catalogue-pagination">
            {$page_selector}
        </ul>
    HTML;

	$total_articles_label = __( 'total articles', 'wordeley' );

	return <<<HTML
        <div class="wordeley-article-catalogue">
            <div class="wordeley-article-catalogue-filters">
                {$filters}
            </div>
            <div class="wordeley-article-catalogue-list">
                <header>
                    {$total_articles} {$total_articles_label}
                </header>
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
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $message The message to be shown.
 * @param string $type The type of message, a 'notice' or an 'error'.
 */
function show_alert( string $message, string $type = 'error' ) {
	return '<div class="wordeley-alert" type="' . $type . '">' . $message . '</div>';
}
