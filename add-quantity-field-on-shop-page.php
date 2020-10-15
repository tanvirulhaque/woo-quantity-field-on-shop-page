<?php
/*
 * Plugin Name: WooCommerce - Add Quantity Field on Shop Page
 * Plugin URI: https://wordpress.org/plugins/add-quantity-field-on-shop-page/
 * Description:
 * Author: Tanvirul Haque
 * Version: 1.0.0
 * Author URI: http://wpxpress.net
 * Text Domain: add-quantity-field-on-shop-page
 * Domain Path: /languages
 * WC requires at least: 3.2
 * WC tested up to: 4.6.0
 * License: GPLv2+
*/

// Don't call the file directly
defined( 'ABSPATH' ) or die( 'Keep Silent' );

if ( ! class_exists( 'Woo_Add_Quantity_Field_on_Shop_Page' ) ) {

    /**
     * Main Class
     * @since 1.0.0
     */
    class Woo_Add_Quantity_Field_on_Shop_Page {

        /**
         * Version
         *
         * @since 1.0.0
         * @var  string
         */
        public $version = '1.0.0';

        /**
         * The single instance of the class.
         */
        protected static $instance = null;

        /**
         * Constructor for the class
         *
         * Sets up all the appropriate hooks and actions
         *
         * @return void
         * @since 1.0.0
         */
        public function __construct() {
            // Initialize the action hooks
            $this->init_hooks();
        }

        /**
         * Initializes the class
         *
         * Checks for an existing instance
         * and if it does't find one, creates it.
         *
         * @return object Class instance
         * @since 1.0.0
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Init Hooks
         *
         * @return void
         * @since 1.0.0
         */
        private function init_hooks() {
            add_action( 'init', array( $this, 'localization_setup' ) );
            add_action( 'admin_notices', array( $this, 'php_requirement_notice' ) );
            add_action( 'admin_notices', array( $this, 'wc_requirement_notice' ) );
            add_action( 'admin_notices', array( $this, 'wc_version_requirement_notice' ) );

            add_action( 'woocommerce_after_shop_loop_item', array( $this, 'adding_quantity_field' ) );
            add_action( 'init', array( $this, 'add_to_cart_quantity_handler' ) );
        }

        /**
         * Initialize plugin for localization
         *
         * @return void
         * @since 1.0.0
         *
         */
        public function localization_setup() {
            load_plugin_textdomain( 'add-quantity-field-on-shop-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        /**
         * Adding quantity field
         *
         * @param $price
         * @param $product
         *
         * @return string
         * @since 1.0.0
         */
        public function adding_quantity_field() {
            $product        = wc_get_product( get_the_ID() );
            $is_wvsp_active = class_exists( 'Woo_Variation_Swatches_Pro' ) ? true : false;

            if ( $is_wvsp_active ) {
                $get_wvsp_options   = get_option( 'woo_variation_swatches' );
                $is_enable_swatches = $get_wvsp_options['show_on_archive'];
            }

            if ( ! $product->is_sold_individually() && $product->is_purchasable() && $product->is_in_stock() && $is_wvsp_active = true ) {
                woocommerce_quantity_input( array(
                    'min_value' => 1,
                    'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity()
                ) );
            }
        }

        public function add_to_cart_quantity_handler() {
            wc_enqueue_js( '
                jQuery( ".type-product" ).on( "click", ".quantity input", function() {
                    return false;
                });
                
                jQuery( ".type-product" ).on( "change input", ".quantity .qty", function() {
                    var add_to_cart_button = jQuery( this ).parents( ".product" ).find( ".add_to_cart_button" );
                    
                    // For AJAX add-to-cart actions
                    add_to_cart_button.attr( "data-quantity", jQuery( this ).val() );
                    
                    // For non-AJAX add-to-cart actions
                    add_to_cart_button.attr( "href", "?add-to-cart=" + add_to_cart_button.attr( "data-product_id" ) + "&quantity=" + jQuery( this ).val() );
                });
                
                // Trigger on Enter press
                jQuery(".woocommerce .products").on("keypress", ".quantity .qty", function(e) {
                    if ((e.which||e.keyCode) === 13) {
                        jQuery( this ).parents(".product").find(".add_to_cart_button").trigger("click");
                    }
                });
            ' );
        }


        /**
         * PHP Version
         *
         * @return bool|int
         */
        public function is_required_php_version() {
            return version_compare( PHP_VERSION, '5.6.0', '>=' );
        }

        /**
         * PHP Requirement Notice
         */
        public function php_requirement_notice() {
            if ( ! $this->is_required_php_version() ) {
                $class   = 'notice notice-error';
                $text    = esc_html__( 'Please check PHP version requirement.', 'add-quantity-field-on-shop-page' );
                $link    = esc_url( 'https://docs.woocommerce.com/document/server-requirements/' );
                $message = wp_kses( __( "It's required to use latest version of PHP to use <strong>WooCommerce - Disable Variable Product Price Range</strong>.", 'add-quantity-field-on-shop-page' ), array( 'strong' => array() ) );

                printf( '<div class="%1$s"><p>%2$s <a target="_blank" href="%3$s">%4$s</a></p></div>', $class, $message, $link, $text );
            }
        }

        /**
         * WooCommerce Requirement Notice
         */
        public function wc_requirement_notice() {
            if ( ! $this->is_wc_active() ) {
                $class = 'notice notice-error';
                $text  = esc_html__( 'WooCommerce', 'add-quantity-field-on-shop-page' );

                $link = esc_url( add_query_arg( array(
                    'tab'       => 'plugin-information',
                    'plugin'    => 'woocommerce',
                    'TB_iframe' => 'true',
                    'width'     => '640',
                    'height'    => '500',
                ), admin_url( 'plugin-install.php' ) ) );

                $message = wp_kses( __( "<strong>WooCommerce - Disable Variable Product Price Range</strong> is an add-on of ", 'add-quantity-field-on-shop-page' ), array( 'strong' => array() ) );

                printf( '<div class="%1$s"><p>%2$s <a class="thickbox open-plugin-details-modal" href="%3$s"><strong>%4$s</strong></a></p></div>', $class, $message, $link, $text );
            }
        }

        /**
         * WooCommerce Version
         */
        public function is_required_wc_version() {
            return version_compare( WC_VERSION, '3.2', '>' );
        }

        /**
         * WooCommerce Version Requirement Notice
         */
        public function wc_version_requirement_notice() {
            if ( $this->is_wc_active() && ! $this->is_required_wc_version() ) {
                $class   = 'notice notice-error';
                $message = sprintf( esc_html__( "Currently, you are using older version of WooCommerce. It's recommended to use latest version of WooCommerce to work with %s.", 'add-quantity-field-on-shop-page' ), esc_html__( 'WooCommerce - Disable Variable Product Price Range', 'add-quantity-field-on-shop-page' ) );
                printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
            }
        }

        /**
         * Check WooCommerce Activated
         */
        public function is_wc_active() {
            return class_exists( 'WooCommerce' );
        }
    }
}

/**
 * Initialize the plugin
 *
 * @return object
 */
function woo_add_quantity_field_on_shop_page() {
    return Woo_Add_Quantity_Field_on_Shop_Page::instance();
}

// Kick Off
woo_add_quantity_field_on_shop_page();
