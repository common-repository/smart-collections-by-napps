<?php
namespace NappsSmartCollections\Events;

use NappsSmartCollections\Models\SmartCollection;
use NappsSmartCollections\SmartCollectionTaxonomy;

class UpdateProductsList {

    /**
     * Handle update products list for smart collection
     *
     * @param  mixed> $term_id
     * @return void
     */
    public function handle($term_id) {
        $smartCollection = new SmartCollection($term_id);
        if(count($smartCollection->conditions) <= 0) {
            return;
        }

        $currentProducts = $smartCollection->getProducts();
        $products = $smartCollection->queryProducts();

        // All products from currentProducts that are not in current products list
        // It means that they no longer match the current conditions 
        $productsToRemoveFromCollection = array_diff($currentProducts, $products);
        foreach($productsToRemoveFromCollection as $productId) {
            $smartCollection->removeProduct($productId);
        }

        // Products from new products list that are not in current products list
        // It means that are new products
        $productsToAdd = array_diff($products, $currentProducts);
        foreach($productsToAdd as $productId) {
            $smartCollection->addProduct($productId);
        }

        // Trigger category updated for napps plugin
        $term = get_term_by( 'term_taxonomy_id', $term_id, SmartCollectionTaxonomy::TaxonomyName );
        if(!$term || is_wp_error($term)) {
            return;
        }

        $data = [];
        $data["id"] = $term_id;
        $data["name"] = $term->name;
        $data["slug"] = $term->slug;
        $data["identifier"] = uniqid();

        $productsChunks = array_chunk($products, 400);
        foreach($productsChunks as $key => $chunk) {

            $data["products"] = $chunk;
            $data["page"] = $key + 1;
    
            do_action('category_updated', $data);
        }

    }
    
}
