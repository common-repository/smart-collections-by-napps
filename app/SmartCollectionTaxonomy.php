<?php
namespace NappsSmartCollections;

/**
 * SmartCollection
 */
class SmartCollectionTaxonomy {

    const TaxonomyName = "napps-smart-collection";

    function __construct() {
        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );
    }

    public function woocommerce_init() {
        $labels = [
            'name'                       => esc_html__( 'Smart Collections', 'smart-collections-by-napps' ),
            'singular_name'              => esc_html__( 'Smart Collection', 'smart-collections-by-napps' ),
            'menu_name'                  => esc_html__( 'Smart Collections', 'smart-collections-by-napps' ),
            'all_items'                  => esc_html__( 'All Smart Collections', 'smart-collections-by-napps' ),
            'edit_item'                  => esc_html__( 'Edit Smart Collection', 'smart-collections-by-napps' ),
            'view_item'                  => esc_html__( 'View Smart Collection', 'smart-collections-by-napps' ),
            'update_item'                => esc_html__( 'Update Smart Collection', 'smart-collections-by-napps' ),
            'add_new_item'               => esc_html__( 'Add New Smart Collection', 'smart-collections-by-napps' ),
            'new_item_name'              => esc_html__( 'New Smart Collection Name', 'smart-collections-by-napps' ),
            'search_items'               => esc_html__( 'Search Smart Collections', 'smart-collections-by-napps' ),
            'popular_items'              => esc_html__( 'Popular Smart Collections', 'smart-collections-by-napps' ),
            'back_to_items'              => esc_html__( '&larr; Go to Smart Collections', 'smart-collections-by-napps' ),
            'separate_items_with_commas' => esc_html__( 'Separate collections with commas', 'smart-collections-by-napps' ),
            'add_or_remove_items'        => esc_html__( 'Add or remove Smart collections', 'smart-collections-by-napps' ),
            'choose_from_most_used'      => esc_html__( 'Choose from the most used smart collections', 'smart-collections-by-napps' ),
            'not_found'                  => esc_html__( 'No smart collections found', 'smart-collections-by-napps' )
        ];

        $args = [
            'hierarchical'       => true,
            'labels'             => $labels,
            'show_ui'            => true,
            'query_var'          => true,
            'public'             => true,
            'publicly_queryable' => true,
            'show_admin_column'  => false,
            'show_in_rest'       => true,
            'meta_box_cb'        => array( $this, 'show_smartcollections_product_metabox' ),
            'rewrite'            => [
                'slug'         => SmartCollectionTaxonomy::TaxonomyName,
                'hierarchical' => true,
                'with_front'   => true
            ]
        ];

        register_taxonomy( SmartCollectionTaxonomy::TaxonomyName, [ 'product' ], $args );
    }
    
    /**
     * Show list of smart collections for current product on a meta box
     * Sidebar product page
     *
     * @param  mixed $post
     * @param  mixed $box
     * @return void
     */
    public function show_smartcollections_product_metabox($post, $box) {
        if(!$post) {
            return;
        }

		$smartCollections = wp_get_object_terms( $post->ID, SmartCollectionTaxonomy::TaxonomyName );
        if(!$smartCollections || !is_array($smartCollections)) {
            return;
        }

        ?>
        <p><?php esc_html_e( 'Current smart collections for this product:', 'smart-collections-by-napps' ); ?></p>
        <ul>
            <?php foreach($smartCollections as $smartCollection) { ?>
                <li><?php echo esc_attr($smartCollection->name); ?></li>
            <?php } ?>
        </ul>
        <?php
    }
}
