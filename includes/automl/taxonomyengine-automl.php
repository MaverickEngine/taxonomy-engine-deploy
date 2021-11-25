<?php
require_once(dirname(__DIR__) . '/../vendor/autoload.php');

class TaxonomyEngineAutoML {
    private $options = [
        "taxonomyengine_post_types",
        "taxonomyengine_article_strategy",
        "taxonomyengine_percentage_pass",
        "taxonomyengine_pass_score",
        "taxonomyengine_developer_mode",
        "taxonomyengine_google_credentials"
    ];

    public function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'settings_page' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_post_upload_google_credentials_file', [ $this, 'upload_google_credentials_file' ]);
        add_action('rest_api_init', [$this, 'register_api_routes' ]);
    }

    public function settings_page() {
        add_submenu_page(
            'taxonomyengine',
            'TaxonomyEngine AutoML Settings',
            'AutoML Settings',
            'manage_options',
            'taxonomyengine_automl_settings',
            [ $this, 'settings_page_html' ]
        );
    }

    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        require_once plugin_dir_path( dirname( __FILE__ ) ).'automl/views/automl-settings.php';
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'taxonomyengine-settings-group', $option );
        }
    }

    public function upload_google_credentials_file() {
        if ( ! empty( $_POST['_wp_http_referer'] ) ) {
            $form_url = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
        } else {
            $form_url =  'admin.php?page=taxonomyengine_automl_settings' ;
        }
        if ( isset( $_POST['_wp_nonce'] ) && wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['_wp_nonce'] ) ), 'upload_google_credentials_file' ) ) {
            $uploadedfile = $_FILES['taxonomyengine_google_credentials_file'];
            if (empty($uploadedfile['name'])) {
                update_option( "taxonomyengine_google_credentials", "" );
                wp_safe_redirect(esc_url_raw(add_query_arg( 'upload_google_credentials_file', 'deleted', $form_url )));
                exit;
            }
            $file = fopen($uploadedfile['tmp_name'], 'r');
            $contents = fread($file, filesize($uploadedfile['tmp_name']));
            fclose($file);
            // Encrypt $contents
            update_option( "taxonomyengine_google_credentials", $this->encrypt($contents) );
            unset($uploadedfile['tmp_name']);
            
            if ($contents === $this->decrypt(get_option( "taxonomyengine_google_credentials" ))) {
                wp_safe_redirect(esc_url_raw(add_query_arg( 'upload_google_credentials_file', 'success', $form_url )));
                exit();
            } else {
                wp_safe_redirect(esc_url_raw(add_query_arg( 'upload_google_credentials_file', 'error', $form_url )));
                exit();
            }
        } else {
            wp_safe_redirect(esc_url_raw(add_query_arg( 'upload_google_credentials_file', 'error', $form_url )));
            exit();
        }
    }

    private static function encrypt( $plaintext ) {
        $key = AUTH_SALT;
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
        return $ciphertext;
    }
    
    private static function decrypt( $ciphertext ) {
        $key = AUTH_SALT;
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        if (hash_equals($hmac, $calcmac))// timing attack safe comparison
        {
            return $original_plaintext;
        }
    }

    public static function get_google_credentials() {
        $credentials = get_option( "taxonomyengine_google_credentials" );
        if (empty($credentials)) {
            return false;
        } else {
            $data = self::decrypt(get_option( "taxonomyengine_google_credentials" ));
            return json_decode($data, true);
        }
    }

    function register_api_routes() { // TODO: Clean this up
        register_rest_route( "taxonomyengine/v1", "/automl/upload", [
            'methods' => 'GET',
            'callback' => [$this, 'automl_upload'],
            'permission_callback' => [$this, 'check_edit_post_access']
        ]);
    }

    public function automl_upload() {
        $credentials = self::get_google_credentials();
        if (!$credentials) {
            return [
                'success' => false,
                'message' => 'No credentials found'
            ];
        }
        
    }

    function check_post_access(WP_REST_Request $request) {
        return current_user_can('edit_posts');
    }
}