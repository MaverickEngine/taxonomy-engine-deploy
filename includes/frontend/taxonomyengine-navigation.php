<?php

class TaxonomyEngineNavigation {
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        // add_action('rest_api_init', [$this, 'register_api_routes' ]);
        $this->post_types = get_option("taxonomyengine_post_types", ["posts"]);
        $this->taxonomyengine_db = new TaxonomyEngineDB($this->taxonomyengine_globals);
    }

    // function register_api_routes() {
    //     register_rest_route( 'taxonomyengine/v1', '/next_article', [
    //         'methods' => 'GET',
    //         'callback' => [$this, 'get_next_article'],
    //     ]);
    //     register_rest_route( 'taxonomyengine/v1', '/next_article/redirect', [
    //         'methods' => 'GET',
    //         'callback' => [$this, 'get_next_article_redirect'],
    //     ]);
    //     register_rest_route( 'taxonomyengine/v1', '/next_article/test', [
    //         'methods' => 'GET',
    //         'callback' => [$this, 'test_next_article'],
    //     ]);
    // }

    public function get_next_article() {
        try {
            define('WP_DEBUG', true);
            define('WP_DEBUG_LOG', true);
            define('WP_DEBUG_DISPLAY', true);
            @ini_set('display_errors', 1);
            $strategy = strtolower(get_option( "taxonomyengine_article_strategy", "random" ));
            $reviews = $this->taxonomyengine_db->reviewed_posts(get_current_user_id());
            $exclude_ids = array_map(function($review) {
                return $review->post_id;
            }, $reviews);
            switch ($strategy) {
                case "random":
                    $post = $this->random_post($exclude_ids);
                    break;
                case "latest":
                    $post = $this->latest_post($exclude_ids);
                    break;
                case "oldest":
                    $post = $this->oldest_post($exclude_ids);
                    break;
                case "popular":
                    $post = $this->popular_post($exclude_ids);
                    break;
                default:
                    $post = $this->random_post($exclude_ids);
            }
            return $post;
        } catch (Exception $e) {
            return new WP_Error( 'error', $e->getMessage(), array( 'status' => 500 ) );
        }
    }

    function get_next_article_redirect() {
        $next_article = $this->get_next_article();
        print_r($next_article);
        print "<script>window.location.href = '" . get_permalink($next_article->ID) . "';</script>";
        die();
    }

    function random_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => $this->post_types,
            'numberposts' => 1,
            'orderby' => 'rand',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }

    function latest_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => $this->post_types,
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }

    function oldest_post($exclude_ids) {
        $posts = get_posts([
            'post_type' => $this->post_types,
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'ASC',
            'exclude' => $exclude_ids,
        ]);
        return $posts[0];
    }

    function popular_post($exclude_ids) {
        $client = new GuzzleHttp\Client([
            'base_uri' => get_option('taxonomyengine_revengine_wordpress_api_url'),
            'timeout'  => 10.0,
        ]);
        $date_last_month = date("Y-m-d", strtotime("-1 month"));
        $response = $client->post("/random", 
            [
                'form_params' => [
                    'size' => 1,
                    'jitter_factor' => get_option('taxonomyengine_jitter_factor', 10),
                    'ignore_post_ids' => $exclude_ids,
                    'published_start_date' => $date_last_month
                ],
            ]
        );
        // Get article by post_id
        $post_id = json_decode($response->getBody())->result[0]->key;
        $post = get_post($post_id);
        return $post;
    }

    function test_next_article() {
        $strategy = get_option( "taxonomyengine_article_strategy", "random" );
        $reviews = $this->taxonomyengine_db->reviewed_posts(get_current_user_id());
        $exclude_ids = array_map(function($review) {
            return $review->post_id;
        }, $reviews);
        return [
            "strategy" => $strategy,
            "post_types" => $this->post_types,
            "exclude_ids" => $exclude_ids,
        ];
    }
}