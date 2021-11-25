<?php

class TaxonomyEngineNavigation {
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('rest_api_init', [$this, 'register_api_routes' ]);
        $this->taxonomyengine_db = new TaxonomyEngineDB($this->taxonomyengine_globals);
    }

    function register_api_routes() {
        register_rest_route( 'taxonomyengine/v1', '/next_article', [
            'methods' => 'GET',
            'callback' => [$this, 'get_next_article'],
        ]);
        register_rest_route( 'taxonomyengine/v1', '/next_article/redirect', [
            'methods' => 'GET',
            'callback' => [$this, 'get_next_article_redirect'],
        ]);
    }

    function get_next_article() {
        $stragegy = get_option( "taxonomyengine_article_strategy", "random" );
        $reviews = $this->taxonomyengine_db->reviewed_posts(get_current_user_id());
        $exclude_ids = array_map(function($review) {
            return $review->post_id;
        }, $reviews);
        switch ($stragegy) {
            case "random":
                $post = $this->random_post($exclude_ids);
            case "newest":
                $post = $this->newest($exclude_ids);
            case "oldest":
                $post = $this->oldest($exclude_ids);
            default:
                $post = $this->random_post($exclude_ids);
        }
        return $post;
    }

    function get_next_article_redirect() {
        $next_article = $this->get_next_article();
        header("Location: " . get_permalink($next_article->ID));
        die();
    }

    function random_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => 'post',
            'numberposts' => 1,
            'orderby' => 'rand',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }

    function newest_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => 'post',
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }

    function oldest_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => 'post',
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'ASC',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }
}