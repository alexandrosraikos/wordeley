<?php
/**
 * The file that defines the core plugin author controller class
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/includes/controller
 */

/**
 * The core plugin author controller class.
 *
 * This is used for handling author related functionality, such as parsing
 * and formatting helper functions.
 *
 * @since      1.0.0
 * @package    Wordeley
 * @subpackage Wordeley/includes/controllers
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Author_Controller {

	/**
	 * The names of selected authors.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array $authors The parsed array of author names.
	 */
	public $authors;

	/**
	 * Retrieve authors from the database.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->authors = self::get_authors();
	}

	/**
	 * Generate an information table for use in the filters view.
	 *
	 * @since 1.0.0
	 * @param array $selected_authors A custom array of author names.
	 * @param array $selected_articles A custom array of articles.
	 * @return array The author information table in [name:string, selected:bool, article_count:int] format.
	 */
	public function get_information_table( array $selected_authors, array $selected_articles ): array {
		return array_map(
			function ( $author ) use ( $selected_authors, $selected_articles ) {
				return array(
					'name'          => $author,
					'selected'      => in_array( $author, $selected_authors, true ),
					'article_count' => Wordeley_Article_Controller::count_articles_by_author( $selected_articles, $author ),
				);
			},
			$this->authors
		);
	}

	/**
	 * Get the array of authors.
	 *
	 * @since 1.0.0
	 * @return array The array of authors.
	 */
	public static function get_authors(): array {
		$option  = get_option( 'wordeley_plugin_settings' );
		$authors = $option['article_authors'] ?? '';
		// Parse comma separated authors string.
		return array_map(
			function ( $author ) {
				return ltrim( rtrim( $author ) );
			},
			explode(
				',',
				get_option( 'wordeley_plugin_settings' )['article_authors'] ?? ''
			)
		);
	}
}
