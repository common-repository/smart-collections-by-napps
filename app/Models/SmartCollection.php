<?php

namespace NappsSmartCollections\Models;

use WC_Product;
use WP_Query;
use NappsSmartCollections\SmartCollectionTaxonomy;
use NappsSmartCollections\Models\SmartCollectionCondition;
use NappsSmartCollections\Repository\SmartCollectionRepository;

class SmartCollection {
        
    /**
     * id
     *
     * @var int
     */
    public $id;

    /**
     * Must meet
     *
     * @var string
     */
    public $mustMeet;
    
    /**
     * conditions
     *
     * @var SmartCollectionCondition[]
     */
    public $conditions;
    
    /**
     *
     * @param  int $term_id
     * @return void
     */
    public function __construct($term_id = 0)
    {
        $this->conditions = [];
        $this->id = 0;
        $this->mustMeet = '';

        if($term_id != 0) {
            $this->id = intval($term_id);
            $conditions = get_term_meta($term_id, 'napps-sc-conditions', true);
            $conditions = is_array($conditions) ? (array) $conditions : [];

            foreach($conditions as $condition) {
                $this->conditions[] = new SmartCollectionCondition($condition);
            }

            $this->mustMeet = get_term_meta( $term_id, 'napps-sc-must-meet', true ) ?: '';
        }
    }
    
    /**
     * Get products ids for this smart collection
     *
     * @return array
     */
    public function getProducts() {
        $repository = new SmartCollectionRepository();
        return $repository->getProductsWithTaxonomy(SmartCollectionTaxonomy::TaxonomyName, $this->id);
    }
    

    /**
     * Query products based on conditions and must meet property
     *
     * @return array
     */
    public function queryProducts() {
        if(count($this->conditions) <= 0) {
            return [];
        }

        global $wpdb;
        $whereCompare = $this->mustMeet === 'all_conditions' ? 'AND' : 'OR';
        $whereClause = [];
        $bindingArray = [];

        foreach($this->conditions as $condition) {

            if(count($whereClause) > 0) {
                $whereClause[] = $whereCompare;
            }

            if($condition->target === 'product_attribute' && $condition->attribute) {

                $compare = $condition->compare === 'is_equal' ? 'IN' : 'NOT IN';
                $whereClause[] = "
                    posts.ID IN (
                        SELECT object_id
                        FROM {$wpdb->term_relationships} AS tr
                        WHERE tr.term_taxonomy_id {$compare} (%d)
                    )
                ";
                $bindingArray[] = $condition->attribute;

            } else if($condition->target === 'has_discount') {

                $whereClause[] = "
                    posts.ID IN (
                        SELECT IF(posts.post_parent>0, posts.post_parent, posts.ID) as ID
                        FROM {$wpdb->postmeta} AS postmeta
                        INNER JOIN {$wpdb->posts} AS posts ON posts.ID = postmeta.post_id
                        WHERE (
                            (postmeta.meta_key = '_sale_price' AND postmeta.meta_value > 0) OR 
                            (postmeta.meta_key = '_min_variation_sale_price' AND postmeta.meta_value > 0)
                        )
                        AND posts.post_type IN ('product', 'product_variation') 
                        AND posts.ID NOT IN (
                            SELECT DISTINCT post_parent from {$wpdb->posts} 
                            WHERE post_type = 'product_variation' 
                            AND post_status != 'trash' 
                            AND post_parent != 0
                        )
                    )
                ";

            } else if($condition->target === 'created_at') {

                if($condition->compare === 'in_last') {
                    $whereClause[] = "DATE(posts.post_date) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE()";
                    $bindingArray[] = $condition->discountAmount;
                    continue;
                }

                if($condition->date) {
                    $compare = $condition->compare === 'is_after' ? ">" : "<";
                    $whereClause[] = "DATE(posts.post_date) {$compare} %s";
                    $bindingArray[] = $condition->date;
                }
                

            } else {
                continue;
            }
        }


        $sql = "
            SELECT posts.ID as id
            FROM {$wpdb->posts} AS posts
            WHERE posts.post_type IN ( 'product', 'product_variation' )
            AND posts.post_status != 'trash'
        ";

        if(count($whereClause) > 0) {
            $whereSql = implode(" ", $whereClause);
            $sql .= "AND ({$whereSql})";
        }

        if(count($bindingArray) > 0) {
            $result = $wpdb->get_results(
                $wpdb->prepare( 
                    $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $bindingArray
                ),
                "ARRAY_A"
            );
        } else {
            $result = $wpdb->get_results(
                $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "ARRAY_A"
            );
        }
        

        if(!is_array($result)) {
            return [];
        }

        $products = wp_list_pluck($result, "id");
        return $products;
    }
    
    /**
     * Remove product from smart collection
     *
     * @param  int $productId
     * @return void
     */
    public function removeProduct($productId) {

        if($this->id == 0) {
            return false;
        }

        $status = wp_remove_object_terms( $productId, [ $this->id ], SmartCollectionTaxonomy::TaxonomyName ) == true;
        
        return $status;
    }

    /**
     * Add product to smart collection
     *
     * @param  int $productId
     * @return void
     */
    public function addProduct($productId) {

        if($this->id == 0) {
            return;
        }

        $terms   = wp_get_post_terms( $productId, SmartCollectionTaxonomy::TaxonomyName, [ 'fields' => 'ids' ] );
        if(is_wp_error($terms)) {
            $terms = [];
        }
        
        $terms[] = $this->id;
        $result = wp_set_post_terms( $productId, $terms, SmartCollectionTaxonomy::TaxonomyName );
        $status = $result != false && !is_wp_error($result);

        return $status;
    }
    
    /**
     * Product Meet requirments
     *
     * @param  WC_Product|null $product
     * @return bool
     */
    public function meetRequirements($product) {
        if(!$product || $this->id == 0) {
            return false;
        }

        if($product->get_status() === 'trash') {
            return false;
        }

        $conditionsMatch = array();
        foreach($this->conditions as $key => $condition) {

            // Check if product attribute exists in product
            if ($condition->target === 'product_attribute' && $condition->attribute) {
                $attribute = get_term($condition->attribute);
                if(!$attribute || is_wp_error($attribute)) {
                    $conditionsMatch[$key] = false;
                    continue;
                }

                if(!$product->get_attribute($attribute->taxonomy)) {
                    $conditionsMatch[$key] = false;
                    continue;
                }

                $conditionsMatch[$key] = true;

            } else if($condition->target === 'has_discount') {

                $conditionsMatch[$key] = $product->is_on_sale();

            } else if($condition->target === 'created_at') {

                // If created date from product is invalid
                // Get product created datetime
                $datetime_created  = $product->get_date_created(); 
                if(!$datetime_created) {
                    $conditionsMatch[$key] = false;
                    continue;
                }

                if($condition->compare === 'in_last') {

                    $timestampCreated = $datetime_created->getTimestamp(); // product created timestamp
                    $date = new \DateTime();
                    $date->modify("-{$condition->discountAmount} day");
                    
                    $conditionsMatch[$key] = $timestampCreated - $date->getTimestamp() > 0;
                    continue;
                }

                if($condition->date) {

                    $timestampCreated = $datetime_created->getTimestamp(); // product created timestamp

                    $datetime = wc_string_to_datetime($condition->date);
                    $timestamp_now = $datetime->getTimestamp();

                    if($condition->compare === 'is_after') {
                        $conditionsMatch[$key] = $timestampCreated - $timestamp_now > 0;
                    } else {
                        $conditionsMatch[$key] = $timestampCreated - $timestamp_now < 0;
                    }

                } else {
                    $conditionsMatch[$key] = false;
                }

            } else {
                $conditionsMatch[$key] = false;
            }

            // If we have one condition true, and must meet is any, dont need to continue
            if($conditionsMatch[$key] && $this->mustMeet == 'any_condition') {
                return true;
            }
        }

        // If we dont meet any condition inside loop, we dont have any true condition
        if($this->mustMeet == 'any_condition') {
            return false;
        }

        $uniqueValues = array_unique($conditionsMatch);
        if(count($uniqueValues) == 1 && in_array(true, $uniqueValues, true)) {
            return true;
        }

        return false;
    }
}
