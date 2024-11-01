<?php

namespace NappsSmartCollections;

use WC_Product;
use WP_Error;
use NappsSmartCollections\Helper;
use NappsSmartCollections\Events\Jobs;
use NappsSmartCollections\Events\UpdateProductsList;
use NappsSmartCollections\Models\SmartCollection;
use NappsSmartCollections\SmartCollectionTaxonomy;
use NappsSmartCollections\Repository\SmartCollectionRepository;

/**
 * Admin Pages Handler
 */
class Admin
{

    public function __construct()
    {
        // Add it to admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
        add_filter('plugin_action_links_' . plugin_basename(NAPPS_SMARTCOLLECTIONS_FILE), array($this, 'add_settings_link'));

        // Show smart collection template (edit and add form)
        add_action(SmartCollectionTaxonomy::TaxonomyName . '_add_form_fields', [$this, 'add_form_fields']);
        add_action(SmartCollectionTaxonomy::TaxonomyName . '_edit_form_fields', [$this, 'edit_form_fields']);

        // Save smart collection handler
        add_action('edit_' . SmartCollectionTaxonomy::TaxonomyName, [$this, 'save_form_fields']);
        add_action('create_' . SmartCollectionTaxonomy::TaxonomyName, [$this, 'save_form_fields']);
        add_action('delete_' . SmartCollectionTaxonomy::TaxonomyName, [$this, 'delete_smartcollection'], 10, 4);

        // New condition ajax request (on page edit and create button)
        add_action('wp_ajax_napps-sc-new-condition', [$this, 'new_condition_handler']);

        // When a smart collection is updated so we can trigger webhooks and queue job to update products list
        add_action('napps-sc-updated', [$this, 'on_smartcollection_updated']);

        add_action('woocommerce_update_product', [$this, 'on_product_update'], 5, 2);
        add_action('woocommerce_new_product', [$this, 'on_product_update'], 5, 2);
        add_action('pre_delete_term', [$this, 'on_attribute_delete'], 5, 2);
        add_filter('pre_insert_term', [$this, 'validate_new_smart_collection'], 10, 3);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Validate smart collection fields
     *
     * @param string|WP_Error $term     The term name to add, or a WP_Error object if there's an error.
     * @param string          $taxonomy Taxonomy slug.
     * @param array|string    $args
     * @return void
     */
    public function validate_new_smart_collection($term, $taxonomy, $args)
    {
        if ($taxonomy != SmartCollectionTaxonomy::TaxonomyName) {
            return $term;
        }

        if (!array_key_exists("must-meet", $args) || ($args["must-meet"] != "all_conditions" && $args["must-meet"] != "any_condition")) {
            return new WP_Error('empty_must_meet', __('Select an option products must meet (All conditions, Any condition)', 'smart-collections-by-napps'));
        }

        return $term;
    }

    /**
     *  When a attribute is deleted lets check products that had this attribute
     *  After that we can retrieve all smart collections associated with this products
     *  And dispatch a job to update their products list
     *  
     *  Right now we cant filter smart collections by their conditions
     * 
     * @param string[] $term_id
     * @param string   $taxonomy
     */
    public function on_attribute_delete($term_id, $taxonomy)
    {

        // Get products with this attribute deleted
        $repository = new SmartCollectionRepository();
        $products = $repository->getProductsWithTaxonomy($taxonomy, $term_id);

        // Get all smart collections fro products list
        $smartCollections = wp_get_object_terms($products, SmartCollectionTaxonomy::TaxonomyName);

        // Dispatch job in order to update products list
        foreach ($smartCollections as $smartCollection) {
            WC()->queue()->add(
                Jobs::UPDATE_PRODUCTS_ON_SMARTCOLLECTION,
                array(
                    'term_id' => $smartCollection->term_id,
                )
            );
        }
    }

    /**
     * On Product update hook
     *
     * @param  int $product_id
     * @param  WC_Product $product
     * @return void
     */
    public function on_product_update($product_id, $product)
    {
        if (!$product) {
            return;
        }

        // If is a variable product get parent id otherwise get product id
        $productId = $product->is_type('variable') ? $product->get_parent_id() : $product->get_id();
        if ($productId == 0) {
            $productId = $product->get_id();
        }

        $repository = new SmartCollectionRepository();
        $smartCollections = $repository->getAllSmartCollections();

        $smartCollectionIds = wp_get_post_terms($productId, SmartCollectionTaxonomy::TaxonomyName, ['fields' => 'ids']);
        if (is_wp_error($smartCollectionIds)) {
            $smartCollectionIds = [];
        }

        foreach ($smartCollections as $smartCollection) {
            $isInSmartCollection = in_array($smartCollection->id, $smartCollectionIds);
            $meetRequirements = $smartCollection->meetRequirements($product);

            // If product meet requirements and is not in smart collection at this moment add it
            // Otherwise if product does not meet requirements and was in smart collection remove it
            if ($meetRequirements && !$isInSmartCollection) {
                $smartCollection->addProduct($productId);
            } else if (!$meetRequirements && $isInSmartCollection) {
                $smartCollection->removeProduct($productId);
            }
        }
    }

    /**
     * On smart collection updated hook
     *
     * @param  int $term_id
     * @return void
     */
    public function on_smartcollection_updated($term_id)
    {
        // // Trigger job to update product list
        WC()->queue()->add(
            Jobs::UPDATE_PRODUCTS_ON_SMARTCOLLECTION,
            array(
                'term_id' => $term_id,
            )
        );
    }

    public function add_settings_link($actions)
    {
        $slug = SmartCollectionTaxonomy::TaxonomyName;
        $action = array(
            '<a href="' . admin_url("edit-tags.php?taxonomy={$slug}&post_type=product") . '">' . __('Settings', 'smart-collections-by-napps') . '</a>'
        );
        return array_merge($action, $actions);
    }

    protected function get_svg()
    {
        $svg = @'<svg width="150" height="150" viewBox="0 0 1080 1080" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M705.178 36C616.199 36.3484 530.989 71.9446 468.225 134.987C405.461 198.03 370.266 283.375 370.354 372.313H705.178V1044.94H1040V36H705.178Z" fill="#9BA1A8"/>
        <path d="M370.361 375.293H40V1044.94H370.361V375.293Z" fill="#9BA1A8"/>
        </svg>';
        $svg = "data:image/svg+xml;base64," . base64_encode($svg);
        return $svg;
    }

    public function redirect_to_smart_collections()
    {
        $slug = SmartCollectionTaxonomy::TaxonomyName;
        wp_redirect("edit-tags.php?taxonomy={$slug}&post_type=product");
    }

    /**
     * Register our menu page
     *
     * @return void
     */
    public function admin_menu()
    {
        $capability = 'manage_woocommerce';
        $slug = SmartCollectionTaxonomy::TaxonomyName;

        if ( !is_plugin_active("napps/napps.php") ) {
            add_menu_page('NAPPS', __('Smart Collections', 'smart-collections-by-napps'), $capability, $slug, array($this, 'redirect_to_smart_collections'), $this->get_svg(), 59);
            return;
        }

        if (current_user_can($capability)) {
            add_submenu_page(
                sanitize_key('napps-home'),
                __('Smart Collections', 'smart-collections-by-napps'),
                __('Smart Collections', 'smart-collections-by-napps'),
                $capability,
                "edit-tags.php?taxonomy={$slug}&post_type=product",
            );
        }
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style('napps-smartcollections-admin');
        wp_enqueue_script('napps-smartcollections-admin-main');
    }

    /**
     * New condition ajax request handler
     *
     * @return void
     */
    public function new_condition_handler()
    {
        Helper::render_template("condition", "admin");

        // If we dont wp_die, a 0 is returned on ajax response
        wp_die();
    }

    /**
     * Render new smart collection page
     *
     * @return void
     */
    public function add_form_fields()
    {
        Helper::render_template("form_fields", "admin", array("conditions" => [], "mustMeet" => ""));
    }

    /**
     * Render edit smart collection page
     *
     * @return void
     */
    public function edit_form_fields($term)
    {
        $conditions = get_term_meta($term->term_id, 'napps-sc-conditions', true) ? (array) get_term_meta($term->term_id, 'napps-sc-conditions', true) : [];
        $mustMeet = get_term_meta( $term->term_id, 'napps-sc-must-meet', true ) ?: '';
        Helper::render_template("form_fields", "admin", array("conditions" => $conditions, "mustMeet" => $mustMeet));
    }

    /**
     * Smart collection deleted
     *
     * @param  int $term_id
     * @param  int $tt_id
     * @param  mixed $term
     * @param  array $object_ids
     * @return void
     */
    public function delete_smartcollection($term_id, $tt_id, $term, $object_ids)
    {
        do_action('category_deleted', $term_id);
    }

    /**
     * Request handler to save / edit form fields
     *
     * @param  int $term_id
     * @return void
     */
    public function save_form_fields($term_id)
    {
        if (!isset($_POST['napps-smart-collection-nonce']) || !wp_verify_nonce($_POST['napps-smart-collection-nonce'], SmartCollectionTaxonomy::TaxonomyName)) {
            print 'Sorry, your nonce did not verify.';
            exit;
        }

        if (isset($_POST['napps-sc-conditions']) && is_array($_POST['napps-sc-conditions'])) {
            $conditions = array();
            foreach($_POST['napps-sc-conditions'] as $condition) {
                $conditions[] = array(
                    "target" => sanitize_text_field($condition['target']),
                    "compare" => sanitize_text_field($condition['compare']),
                    "date" => sanitize_text_field($condition['date']),
                    "attribute" => sanitize_text_field($condition['attribute']),
                    "discount_amount" => sanitize_text_field($condition['discount_amount']),
                );
            }
            update_term_meta($term_id, 'napps-sc-conditions', $conditions);
        }

        if (isset($_POST['must-meet'])) {
            update_term_meta($term_id, 'napps-sc-must-meet', sanitize_text_field($_POST['must-meet']));
        }

        do_action('napps-sc-updated', $term_id);
    }
}
