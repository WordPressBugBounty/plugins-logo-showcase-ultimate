<?php
/*
Plugin Name: Logo Showcase Ultimate
Plugin URI: https://wpwax.com/product/logo-showcase-ultimate-pro/
Description: This plugin allows you to easily create Logo Showcase to display logos of your clients, partners, sponsors and affiliates etc in a beautiful carousel, slider and grid.
Version:     1.4.2
Author:      wpWax
Author URI:  https://wpwax.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: logo-showcase-ultimate
Domain Path: /languages/
*/
if ( ! defined('ABSPATH') ) die( 'Direct access is not allow' );

if ( ! class_exists( 'Lcg_Main_Class' ) ) {
    class Lcg_Main_Class {
        /**
         *
         * @since 1.0.0
         */
        private static $instance;

        /**
         * all metabox
         * @since 2.0.0
         */
        public $metabox;

        /**
         * custom post
         * @since 2.0.0
         */
        public $custom_post;

        /**
         * featured image custmizer class
         * @since 1.4.2
         */
        public $featured_img_customizer;

        /**
         * all shortcode
         * @since 2.0.0
         */
        public $shortcode;

        /**
         * all shortcode
         * @since 2.0.0
         */
        public $migration;

        public static function instance()
        {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Lcg_Main_Class ) ) {
                self::$instance = new Lcg_Main_Class;
                //if woocmmerce plugin not activate
                self::$instance->define_lcg_adl_constants();
                add_action( 'plugin_loaded', array( self::$instance, 'lcg_load_textdomain' ) );
                add_action( 'admin_enqueue_scripts', array( self::$instance, 'lcg_admin_enqueue_scripts' ) );
                add_action( 'template_redirect', array( self::$instance, 'lcg_enqueue_style_front' ) );
                add_action( 'admin_menu', array( self::$instance, 'lcg_hook_usage_and_support_submenu' ) );
                add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( self::$instance, 'display_pro_version_logo_link' ) );

                // enqueue for elementor 
                add_action( 'elementor/preview/enqueue_styles', [ self::$instance, 'elementor_enqueue_preview_style' ] );
                add_action( 'elementor/preview/enqueue_scripts', [ self::$instance, 'elementor_preview_enqueue_script' ] );

                add_action( 'enqueue_block_editor_assets', [ self::$instance, 'enqueue_block_editor_assets' ] );

                if( empty( get_option('lcg_dismiss_discount_notice') ) ) {
                    add_action( 'admin_notices', array( self::$instance, 'admin_notices') );
                }

                if( empty( get_option('lcg_migrate_serialize_to_json') ) ) {
                    add_action( 'admin_init', array( self::$instance, 'migrate_serialize_data') );
                }

                self::$instance->lcg_include_required_files();
                self::$instance->custom_post                = new Lcg_Custom_Post();
                self::$instance->featured_img_customizer    = new Lcg_Featured_Img_Customizer(array(
                    'post_type'     => 'lcg_mainpost',
                    'metabox_title' => esc_html__( 'Logo', 'logo-showcase-ultimate' ),
                    'set_text'      => esc_html__( 'Set logo', 'logo-showcase-ultimate' ),
                    'remove_text'   => esc_html__( 'Remove logo', 'logo-showcase-ultimate' ),
                ));
                self::$instance->metabox                    = new Lcg_Metabox();
                self::$instance->shortcode                  = new Lcg_shortcode();
            }

            return self::$instance;
        }

        public function migrate_serialize_data() {
            
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $args = array(
                'post_type'      => 'lcg_shortcode',
                'post_status'    => 'any',
                'posts_per_page' => -1,
            );

            $query = new WP_Query( $args );
            
            // Check if there are posts in the query
            if ( $query->have_posts() ) {
                // Loop through each post
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $wcpscu_data = get_post_meta( get_the_ID(), 'lcg_scode', true );
                    
                    if ( ! empty( $wcpscu_data ) && ! $this->is_json_encoded( $wcpscu_data ) ) {
                        $unserialized_data = unserialize( base64_decode( $wcpscu_data ) );
                        
                        $json_decode_data = self::json_encoded( $unserialized_data );
                        update_post_meta( get_the_ID(), 'lcg_scode', $json_decode_data );
                    }
                }

                // Restore the global post data
                wp_reset_postdata();
            }
            update_option( 'lcg_migrate_serialize_to_json', true );

        }

        function is_json_encoded( $data ) {
            json_decode( $data );
            return (json_last_error() == JSON_ERROR_NONE);
        }


        /**
         *define constants
         */
        public function define_lcg_adl_constants() {
            if ( ! defined('LCG_PLUGIN_URI') ) define( 'LCG_PLUGIN_URI', plugin_dir_url(__FILE__) );
            if ( ! defined('LCG_PLUGIN_DIR') ) define( 'LCG_PLUGIN_DIR', plugin_dir_path(__FILE__) );
            if ( ! defined('LCG_TEXTDOMAIN') ) define( 'LCG_TEXTDOMAIN', 'logo-showcase-ultimate' );
        }

        public function admin_notices() {
            global $pagenow, $typenow;
            if ( 'index.php' == $pagenow || 'plugins.php' == $pagenow || 'lcg_mainpost' == $typenow || 'lcg_shortcode' == $typenow ) {
                require_once LCG_PLUGIN_DIR . 'template/notice.php';
            }
        }

        //include all file for plugin
        public function lcg_include_required_files() {
            require_once LCG_PLUGIN_DIR . 'classes/lcg-adl-custom-post.php';
            require_once LCG_PLUGIN_DIR . 'classes/lcg-metabox-overrider.php';
            require_once LCG_PLUGIN_DIR . 'classes/lcg-adl-metabox.php';
            require_once LCG_PLUGIN_DIR . 'classes/lcg-resize.php';
            require_once LCG_PLUGIN_DIR . 'classes/lcg-shortcode.php';
            require_once LCG_PLUGIN_DIR . 'classes/elementor/init.php';
            require_once LCG_PLUGIN_DIR . 'classes/gutenberg/init.php';
        }

        //enqueues all the styles and scripts
        public function lcg_enqueue_style_front() {

            wp_register_style( 'lcg-style', LCG_PLUGIN_URI . '/assets/css/style.css' );
            wp_register_style( 'lcg-swiper-min-css', LCG_PLUGIN_URI . '/assets/css/vendor/swiper-bundle.min.css' );
            wp_register_style( 'lcg-tooltip', LCG_PLUGIN_URI . '/assets/css/vendor/tooltip.css' );

            wp_register_script( 'lcg-popper-js', LCG_PLUGIN_URI . '/assets/js/vendor/popper.min.js', array('jquery') );
            wp_register_script( 'lcg-tooltip-js', LCG_PLUGIN_URI . '/assets/js/vendor/tooltip.js', array('jquery', 'lcg-popper-js') );
            wp_register_script( 'lcg-swiper-min-js', LCG_PLUGIN_URI . '/assets/js/vendor/swiper-bundle.min.js', array('jquery', 'lcg-tooltip-js') );
            wp_register_script( 'main-js', LCG_PLUGIN_URI . '/assets/js/main.js', array('jquery', 'lcg-swiper-min-js') );
        }

        public function elementor_enqueue_preview_style() {
            wp_enqueue_style('lcg-style', LCG_PLUGIN_URI . '/assets/css/style.css');
            wp_enqueue_style('lcg-swiper-min-css', LCG_PLUGIN_URI . '/assets/css/vendor/swiper-bundle.min.css');
            wp_enqueue_style('lcg-tooltip', LCG_PLUGIN_URI . '/assets/css/vendor/tooltip.css');
        }

        public function elementor_preview_enqueue_script() {
            wp_enqueue_script('lcg-popper-js', LCG_PLUGIN_URI . '/assets/js/vendor/popper.min.js', array('jquery'));
            wp_enqueue_script('lcg-tooltip-js', LCG_PLUGIN_URI . '/assets/js/vendor/tooltip.js', array('jquery', 'lcg-popper-js'));
            wp_enqueue_script('lcg-swiper-min-js', LCG_PLUGIN_URI . '/assets/js/vendor/swiper-bundle.min.js', array('jquery', 'lcg-tooltip-js'));
            wp_enqueue_script('main-js', LCG_PLUGIN_URI . '/assets/js/main.js', array('jquery', 'lcg-swiper-min-js'));
        }

        public function enqueue_block_editor_assets() {
            wp_enqueue_style('lcg-style', LCG_PLUGIN_URI . '/assets/css/style.css');
            wp_enqueue_style('lcg-swiper-min-css', LCG_PLUGIN_URI . '/assets/css/vendor/swiper-bundle.min.css');
            wp_enqueue_style('lcg-tooltip', LCG_PLUGIN_URI . '/assets/css/vendor/tooltip.css');
            
            wp_enqueue_script('lcg-popper-js', LCG_PLUGIN_URI . '/assets/js/vendor/popper.min.js', array('jquery'));
            wp_enqueue_script('lcg-tooltip-js', LCG_PLUGIN_URI . '/assets/js/vendor/tooltip.js', array('jquery', 'lcg-popper-js'));
            wp_enqueue_script('lcg-swiper-min-js', LCG_PLUGIN_URI . '/assets/js/vendor/swiper-bundle.min.js', array('jquery', 'lcg-tooltip-js'));
            wp_enqueue_script('main-js', LCG_PLUGIN_URI . '/assets/js/main.js', array('jquery', 'lcg-swiper-min-js'));
        }

        public function lcg_load_textdomain() {

            load_plugin_textdomain( LCG_TEXTDOMAIN, false, plugin_basename(dirname(__FILE__)) . '/languages/' );

        }

        //method for enqueue admins's style and script
        public function lcg_admin_enqueue_scripts() {
            global $typenow, $pagenow;

            if( 'lcg_mainpost' === $typenow || 'lcg_shortcode' === $typenow ) {
                wp_enqueue_style( 'cmb2-min', PLUGINS_URL('admin/css/cmb2.min.css', __FILE__) );
                wp_enqueue_style( 'admin-style', PLUGINS_URL('admin/css/lcsp-admin-styles.css', __FILE__) );
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_script( 'admin-script', PLUGINS_URL('admin/js/lcsp-admin-script.js', __FILE__), array('jquery', 'wp-color-picker') );
            }

            if ( 'index.php' == $pagenow || 'plugins.php' == $pagenow || 'lcg_mainpost' == $typenow || 'lcg_shortcode' == $typenow ) {
                wp_enqueue_style( 'lcg-notice', LCG_PLUGIN_URI . 'admin/css/notice.css' );
            }

        }

        //method for pro plugin's link
        public function display_pro_version_logo_link( $links ) {
            $links[] = '<a href="' . esc_url('https://wpwax.com/product/logo-showcase-ultimate-pro/') . '" target="_blank">' . esc_html__('Pro Version', 'logo-showcase-ultimate') . '</a>';
            return $links;
        }

        //add page for usage & support
        public function lcg_hook_usage_and_support_submenu() {
            add_submenu_page( 'edit.php?post_type=lcg_mainpost', __( 'Usage & Support', 'logo-showcase-ultimate' ), __( 'Usage & Support', 'logo-showcase-ultimate' ), 'manage_options', 'lcg_usage_support', array( $this, 'lcg_display_usage_and_support' ) );
        }

        public function lcg_display_usage_and_support() {
            require_once LCG_PLUGIN_DIR . 'classes/lcg-usages-support.php';
        }

        /**
         * Initialize appsero tracking.
         *
         * @see https://github.com/Appsero/client
         *
         * @return void
         */
        public function init_appsero() {
            if ( ! class_exists( '\Appsero\Client' ) ) {
                require_once LCG_PLUGIN_DIR . 'classes/appsero/src/Client.php';
            }

            $client = new \Appsero\Client( 'e5b9d7f8-87e0-48db-823e-f2b38a259095', 'Logo Showcase Ultimate', __FILE__ );

            // Active insights
            $client->insights()->init();
        }

        /**
         * Encodes a PHP value into its JSON representation.
         * @param $data
         * @return string
         */
        public static function json_encoded( $data ) {
            return json_encode( $data );
        }

        /**
         * Decodes a JSON-encoded string into a PHP associative array.
         * @param string $data The JSON-encoded string to be decoded.
         * @return array Returns the decoded PHP associative array on success, or an empty array on failure.
         */
        public static function json_decoded( $data ) {
        
            $decoded_data = json_decode( $data, true );

            
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
                return $decoded_data;
            } else {
                return array();
            }
        }
    } //end class


} //end if

function lcg() {
    return Lcg_Main_Class::instance();
}

if( ! class_exists('Lcg_Main_Class_Pro') ) {
    lcg();
}


function lcg_image_cropping( $attachmentId, $width, $height, $crop = true, $quality = 100 ) {
    $resizer = new Lcg_Image_resizer( $attachmentId );

    return $resizer->resize( $width, $height, $crop, $quality );
}