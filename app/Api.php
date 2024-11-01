<?php
namespace NappsSmartCollections;

use WP_REST_Controller;
use NappsSmartCollections\SmartCollectionTaxonomy;
use WP_REST_Server;
use WC_REST_Terms_Controller;
/**
 * REST_API Handler
 */
class Api extends WC_REST_Terms_Controller {
	
	/**
	 * Base path
	 *
	 * @var string
	 */
	protected $base     = 'smartcollections';
    protected $taxonomy = SmartCollectionTaxonomy::TaxonomyName;

    public function __construct() {
        add_action('rest_api_init', array($this, 'on_rest_api_init'));
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

		if (is_admin() || wp_doing_cron()) {
			add_action('woocommerce_deliver_webhook_async', array($this, 'woocommerce_deliver_webhook_async'), 10, 2);
		}

    }

    /**
	 * Registers the endpoint
	 */
	public function register_endpoints() {
        $namespace = "wc/v3";
        register_rest_route(
            $namespace,
            $this->base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
            )
        );

        register_rest_route(
            $namespace,
            $this->base . '/(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the resource.', 'perfect-woocommerce-brands' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                        'context' => $this->get_context_param( array( 'default' => 'view' ) ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_item' ),
                    'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                    'args'                => array(
                        'force' => array(
                            'default'     => true,
                            'type'        => 'boolean',
                            'description' => __( 'Whether to bypass trash and force deletion.', 'perfect-woocommerce-brands' ),
                        ),
                    ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
	 * Prepare a single brand output for response.
	 *
	 * @param WP_Term         $item    Term object.
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {

        // Get category order.
		$menu_order = get_term_meta( $item->term_id, 'order', true );

        $data = array(
			'id'          => (int) $item->term_id,
			'name'        => $item->name,
			'slug'        => $item->slug,
			'parent'      => (int) $item->parent,
			'description' => $item->description,
			'display'     => 'default',
			'image'       => null,
			'menu_order'  => (int) $menu_order,
			'count'       => (int) $item->count,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 * @since 2.3.0
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

    /**
	*   Init rest api init on webhook delivery, product webhook use it
	*/
	public function woocommerce_deliver_webhook_async($webhook_id, $arg)
	{
		$this->on_rest_api_init();
	}

    /**
     * Apply filters before rest api
     *
     * @return void
     */
    public function on_rest_api_init() {
        add_filter('woocommerce_rest_prepare_product_object', array($this, 'woocommerce_rest_product_object'), 10, 3);
    }
    
    /**
     * Add out smart collection to product categories fields
     *
     * @param  mixed $response
     * @param  mixed $item
     * @param  mixed $request
     * @return void
     */
    public function woocommerce_rest_product_object($response, $item, $request)
	{
		$context = !empty($request['context']) ? $request['context'] : 'view';
		if ($context != 'view') {
			return $response;
		}

		$data = $response->get_data();
		$productId = $data['id'];
        if($data['parent_id'] != 0) {
            $productId = $data['parent_id'];
        }

        if(!$productId || $productId == 0) {
            return $response;
        }


        $smartCollections = wp_get_object_terms( $productId, SmartCollectionTaxonomy::TaxonomyName );
        if(!$smartCollections || !is_array($smartCollections)) {
            return $response;
        }

        $categories = $data['categories'];
        if(!is_array($categories)) {
            $categories = [];
        }

        foreach($smartCollections as $smartCollection) {
            $categories[] = array(
                "id" => $smartCollection->term_id,
                "name" => $smartCollection->name,
                "slug" => $smartCollection->slug
            );
        }

        $data['categories'] = $categories;
		$response->set_data($data);

		return $response;
	}

}
