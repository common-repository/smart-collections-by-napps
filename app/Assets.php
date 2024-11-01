<?php
namespace NappsSmartCollections;

/**
 * Scripts and Styles Class
 */
class Assets {

    function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register_admin' ], 5 );
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'register' ], 5 );
        }
    }

     /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register_admin() {
        $this->register_scripts( $this->get_scripts(true) );
        $this->register_styles( $this->get_styles(true) );
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register() {
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : NAPPS_SMARTCOLLECTIONS_VERSION;

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;

            wp_register_style( $handle, $style['src'], $deps, NAPPS_SMARTCOLLECTIONS_VERSION );
        }
    }

    /**
     * Get all registered scripts
     *
     * @param  boolean $admin
     * @return array<string, array>
     */
    public function get_scripts($admin = false) {

        $scripts = [];
        if($admin) {
            $scripts = [
                'napps-smartcollections-admin-main' => [
                    'src' =>  NAPPS_SMARTCOLLECTIONS_ASSETS . '/admin/js/main.js'
                ],
            ];
        }


        return $scripts;
    }

    /**
     * Get registered styles
     *
     * @param  boolean $admin
     * @return array<string, array>
     */
    public function get_styles($admin = false) {

        $styles = [];

        if($admin) {
            $styles = [
                'napps-smartcollections-admin' => [
                    'src' =>  NAPPS_SMARTCOLLECTIONS_ASSETS . '/admin/css/style.css'
                ],
            ];
        }
       

        return $styles;
    }

}
