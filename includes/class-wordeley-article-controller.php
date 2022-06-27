<?php

/**
 * The controller of article data.
 */
class Wordeley_Article_Controller
{
    public static $default_page_size = 15;
    public static $default_page = 1;
    public static $earliest_year = 1970;
    public $current_year;
    public $articles;

    /**
     * Construct the required list of articles.
     */
    public function __construct()
    {
        // Get all articles.
        $this->articles = self::get_articles();

        // Get year values.
        $this->current_year = intval(date('Y'));
    }

    /**
     * Useful after the filter function has been used.
     */
    public function reset_articles()
    {
        $this->articles = self::get_articles();
    }

    /**
     * Get articles in paginated format.
     */
    public function get_page(
        int $page = null,
        int $page_size = null
    ): array {
        $page ??= self::$default_page;
        $page_size ??= self::$default_page_size;

        $starting_index = $page_size * ($page - 1);
        return array_slice($this->articles, $starting_index, $page_size);
    }

    /**
     * Get the total number of pages.
     */
    public function total_pages(int $page_size = null): int
    {
        $page_size ??= self::$default_page_size;
        return ceil(count($this->articles) / $page_size);
    }

    /**
     * Filter the list of articles based on author name, term and year range.
     */
    public function filter_articles(
        $authors = null,
        $query = '',
        $start_year = 1970,
        $end_year = null
    ) {
        $authors ??= Wordeley_Author_Controller::get_authors();
        $end_year ??= $this->current_year;

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
     */
    public static function filter_articles_by_term(array $articles, string $query = null)
    {
        if (!empty($query)) {
            return array_filter(
                $articles,
                function ($article) use ($query) {
                    $search_result = stripos($article['title'], $query);
                    return $search_result !== false;
                }
            );
        } else {
            return $articles;
        }
    }

    /**
     * Filter by specific author names.
     */
    public static function filter_articles_by_author(array $articles, array $authors): array
    {
        if ($authors == Wordeley_Author_Controller::get_authors()) {
            return $articles;
        }
        return array_filter($articles, function ($article) use ($authors) {
            return count(
                array_intersect($authors, array_map(function ($author) {
                    return ((empty($author['first_name']) ? '' : $author['first_name'] . ' ')) . ($author['last_name'] ?? '');
                }, $article['authors']))
            ) > 0;
        });
    }

    /**
     * Filter by a specific year range.
     */
    public static function filter_articles_by_year(array $articles, int $start_year, int $end_year)
    {
        return array_filter($articles, function ($article) use ($start_year, $end_year) {
            return $article['year'] >= $start_year && $article['year'] <= $end_year;
        });
    }

    /**
     * Get the oldest year.
     */
    public static function get_year(array $articles, bool $descending): int
    {
        try {
            $years = array_map(function ($article) {
                return $article['year'];
            }, $articles);
            if ($descending) {
                rsort($years);
            } else {
                sort($years);
            }
            return $years[0];
        } catch (\Exception $e) {
            return self::$earliest_year;
        }
    }

    public static function count_articles_by_author(array $articles, string $author): int
    {
        return count(self::filter_articles_by_author($articles, [$author]));
    }

    /**
     * Check if 'articles.json' exists.
     */
    private static function cache_exists(): bool
    {
        return is_file(WORDELEY_FILE_STORE . "/articles.json");
    }

    /**
     * Get 'articles.json' contents.
     */
    private static function get_cache(): array
    {
        return json_decode(file_get_contents(WORDELEY_FILE_STORE . "/articles.json"), true);
    }

    /**
     * Update 'articles.json' contents.
     */
    private static function update_article_cache(array $articles): void
    {
        // Prepare filesystem access.
        global $wp_filesystem;
        WP_Filesystem();

        // Clear old cache and save new content.
        self::delete_article_cache();
        if (!file_exists(WORDELEY_FILE_STORE)) {
            mkdir(WORDELEY_FILE_STORE, 0777, true);
        }
        $wp_filesystem->put_contents(WORDELEY_FILE_STORE . '/articles.json', json_encode($articles), 0644);
    }

    /**
     * Delete 'articles.json' contents.
     */
    public static function delete_article_cache()
    {
        // Prepare filesystem access.
        global $wp_filesystem;

        if (self::cache_exists()) {
            unlink(WORDELEY_FILE_STORE . '/articles.json');
        }
    }

    /**
     * Retrieve articles from set authors and put them into 'articles.json'.
     */
    private static function update_cache(bool $return): array
    {
        $articles = [];
        $authors = Wordeley::parse_authors();

        foreach ($authors as $author) {
            $olders_exist = true;
            $start_year = intval(date('Y'));
            while ($olders_exist) {
                $response = Wordeley::api_request(
                    'GET',
                    '/search/catalog?author=' . urlencode($author) . '&limit=100&min_year=' . $start_year . '&max_year=' . $start_year
                );
                $response = Wordeley::api_request(
                    'GET',
                    '/search/catalog?author=' . urlencode($author) . '&limit=100&min_year=' . $start_year . '&max_year=' . $start_year
                );
                $olders_exist = !empty($response);
                $articles = array_merge($articles, $response);
                $start_year -= 1;
            }
        }

        self::update_article_cache($articles);

        if ($return) {
            return $articles;
        }
    }

    /**
     * Get all the articles by the saved authors.
     */
    private function get_articles(): array
    {
        return self::cache_exists() ? self::get_cache() : self::update_cache(true);
    }
}
