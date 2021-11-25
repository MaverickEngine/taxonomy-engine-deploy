<?php

class TaxonomyEngineAPI {
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('rest_api_init', [$this, 'register_api_routes' ]);
        $this->taxonomyengine_db = new TaxonomyEngineDB($this->taxonomyengine_globals);
    }
    
    function register_api_routes() { // TODO: Clean this up
        register_rest_route( 'taxonomyengine/v1', '/taxonomies/(?P<post_id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taxonomies'],
        ]);
        register_rest_route( "taxonomyengine/v1", "/taxonomies/(?P<post_id>[0-9]+)", [
            'methods' => 'POST',
            'callback' => [$this, 'post_post_taxonomy'],
            'permission_callback' => [$this, 'check_post_access']
        ]);
        register_rest_route( "taxonomyengine/v1", "/taxonomies/(?P<post_id>[0-9]+)/done", [
            'methods' => ['POST', 'GET'],
            'callback' => [$this, 'post_done'],
            'permission_callback' => [$this, 'check_post_access']
        ]);
        register_rest_route( "taxonomyengine/v1", "/review/(?P<post_id>[0-9]+)", [
            'methods' => ['POST', 'GET'],
            'callback' => [$this, 'get_review'],
            'permission_callback' => [$this, 'check_post_access']
        ]);
        register_rest_route( "taxonomyengine/v1", "/post/score/(?P<post_id>[0-9]+)", [
            'methods' => ['POST', 'GET'],
            'callback' => [$this, 'post_score'],
            'permission_callback' => [$this, 'check_post_access']
        ]);
    }

    function check_post_access(WP_REST_Request $request) { // TODO: This needs to be changed for crowdsourced submissions, or have another endpoint for that
        return current_user_can('edit_posts');
    }

    function get_taxonomies($request) {

        function map_term($term, $selected, $user_selected) {
            $result = new stdClass();
            $result->id = $term->term_id;
            $result->name = $term->name;
            $result->slug = $term->slug;
            $result->description = $term->description;
            if (in_array($term->term_id, $user_selected)) {
                $result->selected = true;
            }
            $result->children = get_taxonomy_children($term->term_id, $selected, $user_selected);
            return $result;
        }

        function get_taxonomy_children($parent_id, $selected, $user_selected) {
            $children = get_terms([
                'taxonomy' => "taxonomyengine",
                'hide_empty' => false,
                'parent' => $parent_id,
            ]);
            $children_array = [];
            foreach ($children as $child) {
                $children_array[] = map_term($child, $selected, $user_selected);
            }
            return $children_array;
        }
        
        $terms = get_terms("taxonomyengine", [
            'parent' => 0,
            'hide_empty' => false,
        ]);
        $post_id = $request->get_param('post_id');
        $selected = array_map(function($term) {
            return $term->term_id;
        }, get_the_terms($post_id, "taxonomyengine"));
        $taxonomyengine_review = $this->taxonomyengine_db->get_user_post_taxonomy(get_current_user_id(), $post_id);
        $user_selected = array_map(function($taxonomy_review) {
            return $taxonomy_review->taxonomy_id;
        }, $taxonomyengine_review);
        $taxonomy = [];
        foreach ($terms as $term) {
            $taxonomy[] = map_term($term, $selected, $user_selected);
        }
        return $taxonomy;
    }

    function get_review(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        return $this->taxonomyengine_db->get_or_create_review(get_current_user_id(), $post_id);
    }   

    function post_post_taxonomy(WP_REST_Request $request) {
        global $wpdb;
        $post_id = $request->get_param('post_id');
        $user_id = get_current_user_id();
        $user_score = get_user_meta( $user_id, 'taxonomyengine_reviewer_weight', true );
        // Check if we have already started recording this submission from this user
        $review = $this->taxonomyengine_db->get_or_create_review($user_id, $post_id);
        
        // Save to database table taxonomyengine_user_taxonomy
        $data = json_decode(file_get_contents('php://input'), true);
        $taxonomy = $data["taxonomy"];
        if ($taxonomy["selected"]) {
            $this->taxonomyengine_db->insert_taxonomy($review->id, $taxonomy["id"], $user_score);
        } else {
            $this->taxonomyengine_db->delete_taxonomy($review->id, $taxonomy["id"]);
        }
        return $taxonomy;
    }

    
    public function check_pass($post_id, $pass_score = 0.6) { // TODO - Change to take into account number of completed taxonomies
        // Have we already passed this post?
        $result = $this->taxonomyengine_db->get_passed_post($post_id);
        if ($result->post_id) {
            return $result;
        }
        // Calculate the sum of the scores for this post
        $score = $this->taxonomyengine_db->get_review_score($post_id);
        // If the score is greater than the pass score, mark it as passed
        $pass_score = get_option("taxonomyengine_pass_score", 0.7 );
        if ($score >= $pass_score) {
            $matched_tag_score = $this->taxonomyengine_db->get_matched_tag_score($post_id);
            $result = $this->taxonomyengine_db->pass_post($post_id, $score);
            
        }
        return $result;
    }
    
    private function check_taxonomy($review_id, $post_id, $taxonomy_id) {
        // Check if score is enough to pass
        $pass_score = get_option("taxonomyengine_pass_score", 0.7 );
        $score = $this->taxonomyengine_db->get_taxonomy_score($review_id, $taxonomy_id);
        if ($score >= $pass_score) {
            // Success! Assign our taxonomy to our post
            $result = wp_set_object_terms($post_id, [intval($taxonomy_id)], "taxonomyengine", true);
        }
    }

    function post_done(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $user_id = get_current_user_id();
        $review = $this->taxonomyengine_db->get_or_create_review($user_id, $post_id);
        foreach($review->user_taxonomy as $taxonomy) {
            $this->check_taxonomy($review->id, $post_id, $taxonomy->taxonomy_id);
        }
        $terms = wp_get_post_terms($post_id, "taxonomyengine");
        $result = $this->taxonomyengine_db->end_review($review->id);
        
        // $this->check_pass($post_id);
        return $result;
    }

    function post_score(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $result = $this->taxonomyengine_db->get_matched_tag_score($post_id);
        return $result;
    }
}