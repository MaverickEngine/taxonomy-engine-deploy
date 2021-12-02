<?php
// TODO: This needs a cleanup before launch
// TODO: Bring setup in here
// TODO: Rethink table names
class TaxonomyEngineDB {
    public function __construct() {
        global $wpdb;
        $this->taxonomy_tablename = $wpdb->prefix . "taxonomyengine_reviews_taxonomy";
        $this->reviews_tablename = $wpdb->prefix . "taxonomyengine_reviews";
        $this->passed_posts_tablename = $wpdb->prefix . "taxonomyengine_passed_posts";
    }

    public function get_or_create_review($user_id, $post_id) {
        global $wpdb;
        $passed = $this->get_passed_post($post_id);
        if ($passed->id) {
            $review_id = $wpdb->get_var("SELECT id FROM $this->reviews_tablename WHERE user_id = $user_id AND post_id = $post_id");
            return (object) [
                'id' => $review_id,
                'post_id' => $post_id,
                'user_id' => $user_id,
                'passed' => $passed->date_complete,
                'user_taxonomy' => $this->get_user_taxonomy($review_id),
                'terms' => wp_get_post_terms($post_id, "taxonomyengine"),
            ];
        }
        $result = $wpdb->get_row("SELECT * FROM {$this->reviews_tablename} WHERE post_id = $post_id AND user_id = $user_id");
        if (!$result) {
            $user_score = get_user_meta( $user_id, 'taxonomyengine_reviewer_weight', true );
            $wpdb->insert($this->reviews_tablename, [
                'post_id' => $post_id,
                'user_id' => $user_id,
                'user_score' => $user_score,
                'review_start' => current_time('mysql'),
            ]);
            $review_id = $wpdb->insert_id;
            return (object) [
                'id' => $review_id,
                'post_id' => $post_id,
                'user_id' => $user_id,
                'review_start' => current_time('mysql'),
                'user_taxonomy' => []
            ];
        } else {
            $review_id = $result->id;
            return (object) [
                'id' => $review_id,
                'post_id' => $post_id,
                'user_id' => $user_id,
                'review_start' => $result->review_start,
                'review_end' => $result->review_end,
                'user_taxonomy' => $this->get_user_taxonomy($review_id),
                'terms' => wp_get_post_terms($post_id, "taxonomyengine"),
            ];
        }
    }

    public function end_review($review_id) {
        global $wpdb;
        $wpdb->update($this->reviews_tablename, [
            'review_end' => current_time('mysql'),
        ], [
            'id' => $review_id,
        ]);
        return $wpdb->get_row("SELECT * FROM {$this->reviews_tablename} WHERE id = $review_id");
    }

    public function get_user_taxonomy($review_id) {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM {$this->taxonomy_tablename} WHERE taxonomyengine_review_id = $review_id");
        return $result;
    }

    public function get_user_post_taxonomy($user_id, $post_id) {
        global $wpdb;
        $sql = "SELECT {$this->taxonomy_tablename}.* FROM {$this->taxonomy_tablename} 
        JOIN {$this->reviews_tablename} ON {$this->reviews_tablename}.id = {$this->taxonomy_tablename}.taxonomyengine_review_id 
        WHERE {$this->reviews_tablename}.user_id = $user_id AND {$this->reviews_tablename}.post_id = $post_id";
        $result = $wpdb->get_results($sql);
        return $result;
    }

    public function insert_taxonomy($review_id, $taxonomy_id, $user_score) {
        global $wpdb;
        $wpdb->insert($this->taxonomy_tablename, [
            'taxonomyengine_review_id' => $review_id,
            'taxonomy_id' => $taxonomy_id,
            'user_score' => $user_score,
        ]);
    }

    public function delete_taxonomy($review_id, $taxonomy_id) {
        global $wpdb;
        $wpdb->delete($this->taxonomy_tablename, [
            'taxonomyengine_review_id' => $review_id,
            'taxonomy_id' => $taxonomy_id,
        ]);
    }

    public function reviewed_posts($user_id) {
        global $wpdb;
        $sql = "SELECT {$this->reviews_tablename}.post_id, {$this->reviews_tablename}.review_end FROM {$this->reviews_tablename}
        WHERE {$this->reviews_tablename}.user_id = $user_id AND {$this->reviews_tablename}.review_end IS NOT NULL";
        return $wpdb->get_results($sql);
    }

    public function articles_reviewed_report() {
        global $wpdb;
        $sql = "SELECT {$this->reviews_tablename}.user_id, COUNT(*) AS count FROM {$this->reviews_tablename}
        GROUP BY {$this->reviews_tablename}.user_id";
        return $wpdb->get_results($sql);
    }

    public function pass_post($post_id, $score = 0) {
        global $wpdb;
        $wpdb->insert($this->passed_posts_tablename, [
            'post_id' => $post_id,
            'score' => $score
        ]);
        $result = $wpdb->get_row("SELECT * FROM {$this->passed_posts_tablename} WHERE post_id = $post_id");
        return $result;
    }

    public function get_passed_post($post_id) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->passed_posts_tablename} WHERE post_id = $post_id";
        return $wpdb->get_row($sql);
    }

    public function get_review_score($post_id) {
        global $wpdb;
        $sql = "SELECT SUM(user_score) AS score FROM {$this->reviews_tablename} WHERE post_id = $post_id AND (review_end IS NOT NULL OR review_end != '0000-00-00 00:00:00') ";
        return $wpdb->get_var($sql);
    }

    public function get_taxonomy_score($review_id, $taxonomy_id) {
        global $wpdb;
        $sql = "SELECT SUM(user_score) AS score FROM {$this->taxonomy_tablename} WHERE taxonomyengine_review_id = $review_id AND taxonomy_id = $taxonomy_id";
        return $wpdb->get_var($sql);
    }

    public function get_matched_tag_score($post_id) {
        global $wpdb;
        $sql = "SELECT {$this->taxonomy_tablename}.taxonomy_id, {$this->reviews_tablename}.user_id FROM {$this->taxonomy_tablename} 
        JOIN {$this->reviews_tablename} ON {$this->reviews_tablename}.id = {$this->taxonomy_tablename}.taxonomyengine_review_id 
        WHERE {$this->reviews_tablename}.post_id = $post_id";
        $result = $wpdb->get_results($sql);
        return $result;
    }

    public function review_end_histogram() {
        global $wpdb;
        $sql = "SELECT date({$this->reviews_tablename}.review_end) AS date, COUNT(1) AS count 
        FROM {$this->reviews_tablename}
        WHERE review_end IS NOT NULL AND review_end != '0000-00-00'
        GROUP BY 1";
        return $wpdb->get_results($sql);
    }
}