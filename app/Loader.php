<?php

namespace NappsSmartCollections;

use NappsSmartCollections\Api;
use NappsSmartCollections\Admin;
use NappsSmartCollections\Assets;
use NappsSmartCollections\Events\Jobs;
use NappsSmartCollections\SmartCollectionTaxonomy;

/**
 * Loader class
 */
final class Loader {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.1';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = array();

    /**
     * Constructor for the Base_Plugin class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct() {

        $this->define_constants();

        register_activation_hook( NAPPS_SMARTCOLLECTIONS_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( NAPPS_SMARTCOLLECTIONS_FILE, array( $this, 'deactivate' ) );

        $this->init_plugin();
    }

    

    /**
     * Initializes the SmartCollections() class
     *
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Loader();
        }

        return $instance;
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'NAPPS_SMARTCOLLECTIONS_VERSION', $this->version );
        define( 'NAPPS_SMARTCOLLECTIONS_URL', plugins_url( '', NAPPS_SMARTCOLLECTIONS_FILE ) );
        define( 'NAPPS_SMARTCOLLECTIONS_ASSETS', NAPPS_SMARTCOLLECTIONS_URL . '/assets' );
        define( 'NAPPS_SMARTCOLLECTIONS_MINIMUM_WP_VERSION', '4.7.0' );
        define( 'NAPPS_SMARTCOLLECTIONS_MINIMUM_PHP_VERSION', '5.6' );
        define( 'NAPPS_SMARTCOLLECTIONS_MINIMUM_WC_VERSION', '3.5.0' );
    }

    /**
     * Load plugin
     *
     * @return void
     */
    public function init_plugin() {
        $this->container['smartcollection'] = new SmartCollectionTaxonomy();

        add_action( 'init', array( $this, 'after_wordpress_init' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {

        $this->checkRequirements();

        $installed = get_option( 'NAPPS_SMARTCOLLECTIONS_installed' );

        if ( ! $installed ) {
            update_option( 'NAPPS_SMARTCOLLECTIONS_installed', time() );
        }

        update_option( 'NAPPS_SMARTCOLLECTIONS_version', NAPPS_SMARTCOLLECTIONS_VERSION );

        if (! wp_next_scheduled ( Jobs::SCHEDULE_DAILY_UPDATE_CREATED_AT_X_DAYS )) {
            wp_schedule_event( strtotime( "today 5:00am" ), 'daily', Jobs::SCHEDULE_DAILY_UPDATE_CREATED_AT_X_DAYS );
        }

    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {

    }

    public function is_woocommerce_activated() {
        if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
    }

    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function after_wordpress_init() {

        if(!$this->is_woocommerce_activated()) {
            return;
        }

        if ( $this->is_request( 'admin' ) ) {
            $this->container['admin'] = new Admin();
        }
        
        $this->container['api'] = new Api();
        $this->container['assets'] = new Assets();
        $this->container['jobs'] = new Jobs();
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'smart-collections-by-napps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();

            case 'rest' :
                return defined( 'REST_REQUEST' );

            case 'cron' :
                return defined( 'DOING_CRON' );
        }
    }

    /**
     * Check requirements for activation
     * Check for minimum php version, woocommerce version and wordpress version
     * If requirements are not meat bail activation with an exit on script with a message for user
     *
     * @return void
     */
    private function checkRequirements() {
        if (!empty($_SERVER['SCRIPT_NAME']) && false !== strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php')) {

            $woocommerce = array_key_exists("woocommerce", $GLOBALS) ? $GLOBALS['woocommerce']->version : -1;

            if(!class_exists("WC_Product_Query") || !class_exists("WP_Term_Query")) {
                $this->bail_on_activation();
            }

            if (version_compare(phpversion(), NAPPS_SMARTCOLLECTIONS_MINIMUM_PHP_VERSION, '<' )) {
                $this->bail_on_activation();
            }

            //If current wordpress version or woocommerce version does not meet requirements
            if (version_compare($GLOBALS['wp_version'], NAPPS_SMARTCOLLECTIONS_MINIMUM_WP_VERSION, '<' )
                    || version_compare( $woocommerce, NAPPS_SMARTCOLLECTIONS_MINIMUM_WC_VERSION, '<' ) ) {
                $this->bail_on_activation();
            }
        }
    }

    /**
     * Bail on plugin activation when requirements are not meet
     *
     * @return void
     */
    private function bail_on_activation() {
        $message = 'Your current version of wordpress or woocommerce does not meet the necessary requirements';
        ?>
            <!doctype html>
            <html>
            <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <style>
            * {
                text-align: center;
                margin: 0;
                padding: 0;
                font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
            }
            p {
                margin-top: 1em;
                font-size: 18px;
            }
            </style>
            <body>
            <p><?php echo esc_html( $message ); ?></p>
            </body>
            </html>
        <?php

        deactivate_plugins( plugin_basename( __FILE__ ) );
        exit;
    }
}
