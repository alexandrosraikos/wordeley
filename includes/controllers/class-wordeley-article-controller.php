<?php
/**
 * The file that defines the core plugin article controller class
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/includes/controller
 */

/**
 * The controller of article data.
 */
class Wordeley_Article_Controller {

	/**
	 * The default page size for article arrays.
	 *
	 * @since 1.0.0
	 * @var int The default page size.
	 */
	public static $default_page_size = 15;

	/**
	 * The default page number for article arrays.
	 *
	 * @since 1.0.0
	 * @var int The default page number.
	 */
	public static $default_page = 1;

	/**
	 * The default earliest publication year for articles.
	 *
	 * @since 1.0.0
	 * @var int The default earliest year.
	 */
	public static $earliest_year = 1970;


	/**
	 * The current year for use in article filtering.
	 *
	 * @since 1.0.0
	 * @var int The current year.
	 */
	public $current_year;


	/**
	 * The saved article array, mutable by filters and resettable.
	 *
	 * @since 1.0.0
	 * @var array The article array.
	 */
	public $articles;

	/**
	 * Construct the required list of articles.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Get all articles.
		$this->articles = self::get_articles();

		// Get year values.
		$this->current_year = intval( gmdate( 'Y' ) );
	}

	/**
	 * Reset the list of articles.
	 *
	 * Useful after the `filter_articles()` function has been used.
	 *
	 * @since 1.0.0
	 */
	public function reset_articles() {
		$this->articles = self::get_articles();
	}

	/**
	 * Get articles in paginated format.
	 *
	 * Splits the article array into pages and returns the selected one.
	 *
	 * @since 1.0.0
	 * @param int|null $page The specific natural positive page number.
	 * @param int|null $page_size The specific number of articles in the page.
	 * @return array The article array of the specific page.
	 */
	public function get_page(
		int $page = null,
		int $page_size = null
	): array {
		// Apply default values.
		$page      ??= self::$default_page;
		$page_size ??= self::$default_page_size;

		// Split and return.
		$starting_index = $page_size * ( $page - 1 );
		return array_slice( $this->articles, $starting_index, $page_size );
	}

	/**
	 * Get the total number of pages.
	 *
	 * Counts the total number of pages based on the available articles.
	 *
	 * @since 1.0.0
	 * @param int|null $page_size The number of articles in the page.
	 * @return int The page count.
	 */
	public function total_pages( int $page_size = null ): int {
		$page_size ??= self::$default_page_size;
		return ceil( count( $this->articles ) / $page_size );
	}

	/**
	 * Filter articles based on parameters.
	 *
	 * Filter the list of articles based on author name, term and year range.
	 * This function persists changes in the `$articles` variable, you must `reset_articles()`
	 * to restore them to their initial state.
	 *
	 * @since 1.0.0
	 * @param array  $authors A custom array of authors.
	 * @param string $query A query term for article titles.
	 * @param int    $start_year Articles are from this year onwards.
	 * @param int    $end_year Articles are from this year backwards.
	 */
	public function filter_articles(
		array $authors = null,
		string $query = '',
		int $start_year = 1970,
		int $end_year = null
	) {
		$authors  ??= Wordeley_Author_Controller::get_authors();
		$end_year ??= $this->current_year;
		if ( empty( $start_year ) ) {
			$start_year = self::$earliest_year;
		}
		if ( empty( $end_year ) ) {
			$end_year = $this->current_year;
		}

		// Filter relevant articles by author.
		$this->articles = self::filter_articles_by_year(
			self::filter_articles_by_term(
				self::filter_articles_by_author(
					$this->articles,
					$authors
				),
				$query
			),
			$start_year ?? self::$earliest_year,
			$end_year ?? $this->current_year
		);
	}


	/**
	 * Filter by a specific search term.
	 *
	 * @since 1.0.0
	 * @param array  $articles The array of articles to search in.
	 * @param string $query The query term to be searched.
	 * @return array The array of articles whose title matches the term.
	 */
	public static function filter_articles_by_term(
		array $articles,
		string $query = null
		) {
		if ( ! empty( $query ) ) {
			return array_filter(
				$articles,
				function ( $article ) use ( $query ) {
					$search_result = stripos( $article['title'], $query );
					return false !== $search_result;
				}
			);
		} else {
			return $articles;
		}
	}

	/**
	 * Filter by specific author names.
	 *
	 * @since 1.0.0
	 * @param array $articles An array of articles.
	 * @param array $authors An array of authors.
	 * @return array The filtered article list by author.
	 */
	public static function filter_articles_by_author(
		array $articles,
		array $authors
		): array {
		if ( Wordeley_Author_Controller::get_authors() === $authors ) {
			return $articles;
		}
		return array_filter(
			$articles,
			function ( $article ) use ( $authors ) {
				return count(
					array_intersect(
						$authors,
						array_map(
							function ( $author ) {
								return ( ( empty( $author['first_name'] ) ? '' : $author['first_name'] . ' ' ) ) . ( $author['last_name'] ?? '' );
							},
							$article['authors']
						)
					)
				) > 0;
			}
		);
	}

	/**
	 * Filter by a specific year range.
	 *
	 * @since 1.0.0
	 * @param array $articles An array of articles.
	 * @param int   $start_year The first year of the article publication year range.
	 * @param int   $end_year The last year of the article publication year range.
	 * @return array The filtered article list by year range.
	 */
	public static function filter_articles_by_year(
		array $articles,
		int $start_year,
		int $end_year
		) {
		return array_filter(
			$articles,
			function ( $article ) use ( $start_year, $end_year ) {
				return $article['year'] >= $start_year && $article['year'] <= $end_year;
			}
		);
	}

	/**
	 * Get the oldest or most recent year of a list of articles.
	 *
	 * @since 1.0.0
	 * @param array $articles The array of articles.
	 * @param bool  $newest Whether to return the most recent year.
	 * @return int The oldest or most recent year.
	 */
	public static function get_year(
		array $articles,
		bool $newest
		): int {
		try {
			$years = array_map(
				function ( $article ) {
					return $article['year'];
				},
				$articles
			);
			if ( $newest ) {
				rsort( $years );
			} else {
				sort( $years );
			}
			return $years[0];
		} catch ( \Exception $e ) {
			return self::$earliest_year;
		}
	}

	/**
	 * Get the article count by author.
	 *
	 * @since 1.0.0
	 * @param array  $articles An array of articles.
	 * @param string $author An author name.
	 * @return int The article count.
	 */
	public static function count_articles_by_author( array $articles, string $author ): int {
		return count( self::filter_articles_by_author( $articles, array( $author ) ) );
	}

	/**
	 * Check if 'articles.json' exists.
	 *
	 * @since 1.0.0
	 * @return bool Whether the cache exists.
	 */
	private static function cache_exists(): bool {
		return is_file( WORDELEY_FILE_STORE . '/articles.json' );
	}

	/**
	 * Retrieve articles from set authors and put them into 'articles.json'.
	 *
	 * @since 1.0.0
	 * @param bool $return True to return article cache.
	 * @return array|void The newly updated article cache.
	 */
	public static function update_article_cache( bool $return ) {
		$articles = self::retrieve_articles();

		// Prepare filesystem access.
		global $wp_filesystem;
		WP_Filesystem();

		// Clear old cache and save new content.
		self::delete_article_cache();
		if ( ! file_exists( WORDELEY_FILE_STORE ) ) {
			mkdir( WORDELEY_FILE_STORE, 0777, true );
		}
		$wp_filesystem->put_contents(
			WORDELEY_FILE_STORE . '/articles.json',
			wp_json_encode( $articles ),
			0644
		);

		if ( $return ) {
			return $articles;
		}
	}

	/**
	 * Delete 'articles.json' contents.
	 *
	 * @since 1.0.0
	 * @return bool True on success, False on failure.
	 */
	public static function delete_article_cache(): bool {
		if ( self::cache_exists() ) {
			return unlink( WORDELEY_FILE_STORE . '/articles.json' );
		}
		return true;
	}

	/**
	 * Get 'articles.json' contents.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array The array of articles stored in the plugin's cache.
	 */
	private static function get_cache(): array {
		return json_decode(
			file_get_contents( WORDELEY_FILE_STORE . '/articles.json' ),
			true
		);
	}

	/**
	 * Retrieve article data from the Mendeley API.
	 *
	 * Retrieves all authors registered in the Wordeley settings and
	 * requests article data for each author, for every year going back until there
	 * are no more articles published by them.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array The array of newly retrieved articles.
	 */
	private static function retrieve_articles() {
		$articles          = array();
		$author_controller = new Wordeley_Author_Controller();

		foreach ( $author_controller->authors as $author ) {
			$blank_year_limit = 2;
			$start_year       = intval( gmdate( 'Y' ) );
			while ( $blank_year_limit >= 0 ) {
				$response = Wordeley_Communication_Controller::api_request(
					'GET',
					'/search/catalog?author=' . rawurlencode( $author ) . '&limit=100&min_year=' . $start_year . '&max_year=' . $start_year
				);
				if ( empty( $response ) ) {
					--$blank_year_limit;
				} else {
					$articles = array_merge( $articles, $response );
				}
				--$start_year;
			}
		}

		return $articles;
	}

	/**
	 * Get all the articles by the saved authors.
	 *
	 * Cached article data are fetched by default, and retrieving and updating
	 * the cache serves as a fallback operation.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array The array of articles.
	 */
	private function get_articles(): array {
		return self::cache_exists() ? self::get_cache() : self::update_article_cache( true );
	}
}
