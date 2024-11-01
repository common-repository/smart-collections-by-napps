<?php

namespace NappsSmartCollections\Repository;

use NappsSmartCollections\Models\SmartCollection;
use NappsSmartCollections\SmartCollectionTaxonomy;
use WC_Product_Query;

class SmartCollectionRepository
{
    public function __construct()
    {
    }

    /**
     * Get All Smart collections
     *
     * @return SmartCollection[]
     */
    public function getSmartCollectionsWithXDaysDefined() {
        $smartCollections = $this->getAllSmartCollections();

        $validSmartCollections = [];
        foreach($smartCollections as $smartCollection) {
            foreach($smartCollection->conditions as $condition) {
                if($condition->compare === 'in_last') {
                    array_push($validSmartCollections, $smartCollection);
                }
            }
        }

        return $validSmartCollections;
    }

    /**
     * Get All Smart collections
     *
     * @return SmartCollection[]
     */
    public function getAllSmartCollections() {
        $termIds = get_terms( [
            'taxonomy'   => SmartCollectionTaxonomy::TaxonomyName,
            'fields'     => 'ids'
        ] );

        if(!$termIds || is_wp_error($termIds)) {
            return [];
        }

        $smartCollections = array();
        foreach($termIds as $termId) {
            $smartCollections[] = new SmartCollection($termId);
        }

        return $smartCollections;
    }

    /**
     * Get products ids for this smart collection
     *
     * @return array
     */
    public function getProductsWithTaxonomy($taxonomyName, $value) {
        $args = array(
            'limit'                 => '-1',
            'tax_query'             => array(
                array(
                    'taxonomy'      => $taxonomyName,
                    'field'         => 'term_id',
                    'terms'         =>  $value,
                    'operator'      => 'IN'
                ),
            ),
            'return' => 'ids',
        );

        $query = new WC_Product_Query($args);
        $products = $query->get_products();
        return $products;
    }
}