<?php

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
        if (empty($passed)) return;
        if ($passed->id) {
            $review_id = $wpdb->get_var($wpdb->prepare('SELECT id FROM %1$s WHERE user_id = %d AND post_id = %d', array($this->reviews_tablename, $user_id, $post_id)));
            return (object) [
                'id' => $review_id,
                'post_id' => $post_id,
                'user_id' => $user_id,
                'passed' => $passed->date_complete,
                'user_taxonomy' => $this->get_user_taxonomy($review_id),
                'terms' => wp_get_post_terms($post_id, "taxonomyengine"),
            ];
        }
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM %1$s WHERE post_id = %d AND user_id = %d', array($this->reviews_tablename, $post_id, $user_id)));
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
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM %1$s WHERE id = %d', array($this->reviews_tablename, $review_id)));
    }

    public function get_user_taxonomy($review_id) {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare('SELECT * FROM %1$s WHERE taxonomyengine_review_id = %d', array($this->taxonomy_tablename, $review_id)));
        return $result;
    }

    public function get_user_post_taxonomy($user_id, $post_id) {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare('SELECT %1$s.* FROM %1$s JOIN %2$s ON %2$s.id = %1$s.taxonomyengine_review_id WHERE %2$s.user_id = %3$d AND %2$s.post_id = %4$d', array($this->taxonomy_tablename, $this->reviews_tablename, $user_id, $post_id)));
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
        return $wpdb->get_results($wpdb->prepare('SELECT %1$s.post_id, %1$s.review_end 
        FROM %1$s
        WHERE %1$s.user_id = %2d 
        AND %1$s.review_end IS NOT NULL', 
        array($this->reviews_tablename, $user_id)));
    }

    public function articles_reviewed_report() {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare('SELECT %1$s.user_id, COUNT(*) AS count FROM %1$s
        GROUP BY %1$s.user_id', 
        $this->reviews_tablename));
    }

    public function pass_post($post_id, $score = 0) {
        global $wpdb;
        $wpdb->insert($this->passed_posts_tablename, [
            'post_id' => $post_id,
            'score' => $score
        ]);
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM %1$s WHERE post_id = %d', array($this->passed_posts_tablename, $post_id)));
        return $result;
    }

    public function get_passed_post($post_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM %1$s WHERE post_id = %d', array($this->passed_posts_tablename, $post_id)));
    }

    public function get_review_score($post_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SELECT SUM(user_score) AS score FROM %1$s WHERE post_id = %d AND (review_end IS NOT NULL OR review_end != "0000-00-00 00:00:00") ', array($this->reviews_tablename, $post_id)));
    }

    public function get_taxonomy_score($review_id, $taxonomy_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SELECT SUM(user_score) AS score FROM %1$s WHERE taxonomyengine_review_id = %d AND taxonomy_id = %d', array($this->taxonomy_tablename, $review_id, $taxonomy_id)));
    }

    public function get_matched_tag_score($post_id) {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare('SELECT %1$s.taxonomy_id, %2$s.user_id FROM %1$s
        JOIN %2$s ON %2$s.id = %1$s.taxonomyengine_review_id
        WHERE %2$s.post_id = %3$d', array($this->taxonomy_tablename, $this->reviews_tablename, $post_id)));
        return $result;
    }

    public function review_end_histogram() {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare('SELECT date(%1$s.review_end) AS date, COUNT(1) AS count
        FROM %1$s
        WHERE review_end IS NOT NULL AND review_end != "0000-00-00"', array($this->reviews_tablename)));
    }
}