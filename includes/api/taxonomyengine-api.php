<?php
// require_once("../automl/taxonomyengine-automl.php");

class TaxonomyEngineAPI {
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('rest_api_init', [$this, 'register_api_routes' ]);
        $this->taxonomyengine_db = new TaxonomyEngineDB($this->taxonomyengine_globals);
    }
    
    function register_api_routes() { // TODO: Clean this up
        // Taxonomies
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
        // Reports
        register_rest_route( 'taxonomyengine/v1', '/reports/review_end_histogram', [
            'methods' => 'GET',
            'callback' => [$this, 'get_review_end_histogram'],
        ]);
        // Reviewers
        register_rest_route( 'taxonomyengine/v1', '/reviewers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_reviewers'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
        register_rest_route( 'taxonomyengine/v1', '/reviewers/users', [
            'methods' => 'POST',
            'callback' => [$this, 'search_users'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
        register_rest_route( 'taxonomyengine/v1', '/reviewers/add_role/(?P<user_id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'add_role'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
        register_rest_route( 'taxonomyengine/v1', '/reviewers/remove_role/(?P<user_id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'remove_role'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
        register_rest_route( 'taxonomyengine/v1', '/reviewers/update_user_weight/(?P<user_id>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_user_weight'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
        //AutoML
        $automl = new TaxonomyEngineAutoML($this->taxonomyengine_globals);
        register_rest_route( 'taxonomyengine/v1', '/automl/test_google_credentials', [
            'methods' => 'POST',
            'callback' => [$automl, 'test_google_credentials'],
            'permission_callback' => [$this, 'check_admin_access']
        ]);
    }

    function check_post_access(WP_REST_Request $request) { // TODO: This needs to be changed for crowdsourced submissions, or have another endpoint for that
        return current_user_can('edit_posts');
    }

    function check_admin_access(WP_REST_Request $request) {
        return current_user_can('manage_options');
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

    public function get_review(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        return $this->taxonomyengine_db->get_or_create_review(get_current_user_id(), $post_id);
    }   

    public function post_post_taxonomy(WP_REST_Request $request) {
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

    public function post_done(WP_REST_Request $request) {
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

    public function post_score(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $result = $this->taxonomyengine_db->get_matched_tag_score($post_id);
        return $result;
    }

    public function get_review_end_histogram(WP_REST_Request $request) {
        $review_end_histogram = $this->taxonomyengine_db->review_end_histogram();
        return $review_end_histogram;
    }

    private function _map_user($user) {
        $result = new stdClass();
        $result->id = $user->ID;
        $result->user_login = $user->user_login;
        $result->display_name = $user->display_name;
        $result->user_email = $user->user_email;
        $result->taxonomyengine_reviewer_weight = $user->taxonomyengine_reviewer_weight;
        $result->avatar = get_avatar_url($user->ID);
        $result->roles = $user->roles;
        return $result;
    }

    public function get_reviewers(WP_REST_Request $request) {
        try {
            $page = $_GET['page'];
            if (!$page) {
                $page = 1;
            }
            $user_query = new WP_User_Query([
                'paged' => $page,
                'role' => TAXONOMYENGINE_REVIEWER_ROLE,
                'number' => 50,
                'fields' => 'all_with_meta',
            ]);
            return [
                "page" => $page,
                "count" => $user_query->get_total(),
                "data" => array_values(array_map([$this, "_map_user"], $user_query->get_results())),
            ];
        } catch (Exception $e) {
            return new WP_REST_Response([ "success" => false, "error" => $e->getMessage() ], 400);
        }
    }

    private function _search($data, $page=1, $size=50) {
        $q = [
            'number' => $size,
            'fields' => 'all_with_meta',
        ];
        if ($data["search"]) {
            $q["search"] = $data["search"] . "*";
        }
        if ($data["id"]) {
            $q["search"] = $data["id"];
            $q["search_columns"] = ["ID"];
        }
        if ($data["role"]) {
            $q["role"] = $data["role"];
        }
        $user_query = new WP_User_Query($q);
        return [
            "page" => $page,
            "size" => $size,
            "count" => $user_query->get_total(),
            "data" => array_values(array_map([ $this, "_map_user"], $user_query->get_results())),
        ];
    }

    public function search_users(WP_REST_Request $request) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $page = $_GET['page'];
            return $this->_search($data, $page);
        } catch (Exception $e) {
            return new WP_REST_Response([ "success" => false, "error" => $e->getMessage() ], 400);
        }
    }

    public function add_role(WP_REST_Request $request) {
        try {
            $user_id = $request->get_param('user_id');
            $user = new WP_User($user_id);
            $user->add_role(TAXONOMYENGINE_REVIEWER_ROLE);
            update_user_meta( $user_id, "taxonomyengine_reviewer_weight", get_option("taxonomyengine_default_starting_weight", TAXONOMYENGINE_DEFAULT_STARTING_WEIGHT) );
            $users = $this->_search(["id" => $user_id]);
            return new WP_REST_Response($users, 200);
        } catch (Exception $e) {
            return new WP_REST_Response([ "success" => false, "error" => $e->getMessage() ], 400);
        }
    }

    public function remove_role(WP_REST_Request $request) {
        try {
            $user_id = $request->get_param('user_id');
            $user = new WP_User($user_id);
            $user->remove_role(TAXONOMYENGINE_REVIEWER_ROLE);
            $users = $this->_search(["id" => $user_id]);
            return new WP_REST_Response($users, 200);
        } catch (Exception $e) {
            return new WP_REST_Response([ "success" => false, "error" => $e->getMessage() ], 400);
        }
    }

    public function update_user_weight(WP_REST_Request $request) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $user_id = $request->get_param('user_id');
            $weight = $data["weight"];
            if (empty($weight) || !is_numeric($weight) || $weight < 0 || $weight > 1) {
                throw("Weight must be a number between 0 and 1");
            }
            update_user_meta( $user_id, "taxonomyengine_reviewer_weight", $weight );
            return new WP_REST_Response([ "success" => true ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([ "success" => false, "error" => $e->getMessage() ], 400);
        }
    }
}