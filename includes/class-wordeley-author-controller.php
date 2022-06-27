<?php

class Wordeley_Author_Controller
{
    public $authors;

    public function __construct()
    {
        $this->authors = self::get_authors();
    }

    /**
     * Generate an information table for use in the filters view. The format is:
     * [name:string, selected:bool, article_count:int]
     */
    public function get_information_table(array $selected_authors, array $selected_articles): array
    {
        return array_map(function ($author) use ($selected_authors, $selected_articles) {
            return    [
                'name' => $author,
                'selected' => in_array($author, $selected_authors),
                'article_count' => Wordeley_Article_Controller::count_articles_by_author($selected_articles, $author)
            ];
        }, $this->authors);
    }

    /**
     * Get the saved table of authors.
     */
    public static function get_authors()
    {
        // Parse comma separated authors string.
        return array_map(
            function ($author) {
                return ltrim(rtrim($author));
            },
            explode(
                ',',
                get_option('wordeley_plugin_settings')['article_authors'] ?? ''
            )
        );
    }
}
