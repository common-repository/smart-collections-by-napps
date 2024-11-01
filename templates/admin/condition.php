<?php

/**
 * The template for displaying the conditions table footer
 *
 * @version 1.0.0
 */
defined('ABSPATH') || die('No script kiddies please!');

if (empty($key)) {
    $key = uniqid();
}

$target   = isset($condition['target']) ? $condition['target'] : 'product_attribute';
$compare = isset($condition['compare']) ? $condition['compare'] : 'is_equal';
$date = isset($condition['date']) ? $condition['date'] : gmdate('Y-m-d');

$attribute  = isset($condition['attribute']) ? $condition['attribute'] : '';
$discountAmount = isset($condition['discount_amount']) ? $condition['discount_amount'] : '';
?>

<div class="condition">
    <div class="condition-settings">
        <div class="condition-target">
            <select class="condition-target-select" id="napps-sc-condition-target" name="napps-sc-conditions[<?php echo esc_attr($key); ?>][target]">
                <option value="product_attribute" <?php selected($target, 'product_attribute'); ?>><?php esc_html_e('Product Attribute', 'smart-collections-by-napps'); ?></option>
                <option value="has_discount" <?php selected($target, 'has_discount'); ?>><?php esc_html_e('Has Discount', 'smart-collections-by-napps'); ?></option>
                <option value="created_at" <?php selected($target, 'created_at'); ?>><?php esc_html_e('Created at', 'smart-collections-by-napps'); ?></option>
            </select>
            <select class="condition-target-select" id="napps-sc-condition-compare" name="napps-sc-conditions[<?php echo esc_attr($key); ?>][compare]">
                <option id="napps-sc-condition-compare_equal" value="is_equal" <?php selected($compare, 'is_equal'); ?>><?php esc_html_e('Is equal', 'smart-collections-by-napps'); ?></option>
                <option id="napps-sc-condition-compare_not_equal" value="is_not_equal" <?php selected($compare, 'is_not_equal'); ?>><?php esc_html_e('Is not equal', 'smart-collections-by-napps'); ?></option>
                <option id="napps-sc-condition-compare_after" value="is_after" <?php selected($compare, 'is_after'); ?>><?php esc_html_e('Is after', 'smart-collections-by-napps'); ?></option>
                <option id="napps-sc-condition-compare_before" value="is_before" <?php selected($compare, 'is_before'); ?>><?php esc_html_e('Is before', 'smart-collections-by-napps'); ?></option>
                <option id="napps-sc-condition-compare_in_last" value="in_last" <?php selected($compare, 'in_last'); ?>><?php esc_html_e('In last X days', 'smart-collections-by-napps'); ?></option>
            </select>
           
        </div>

        <input 
            style="display: none;" 
            class="condition-target-select" 
            value="<?php echo esc_html($date); ?>" 
            id="napps-sc-condition-date" 
            name="napps-sc-conditions[<?php echo esc_attr($key); ?>][date]" 
            type="date"
        >

        <input 
            style="display: none;" 
            disabled
            id="napps-sc-condition-discount-amount" 
            name="napps-sc-conditions[<?php echo esc_attr($key); ?>][discount_amount]" 
            value="<?php echo esc_html($discountAmount) ?>" 
            type="number" 
        />

        <div id="napps-sc-condition-attribute" class="attribute-target">
            <select class="attribute-target-select" name="napps-sc-conditions[<?php echo esc_attr($key); ?>][attribute]">
                <option value="">
                   <?php esc_html_e( 'Select one option', 'smart-collections-by-napps' ) ?>
                </option>
                <?php

                $taxonomies = [];
                $taxonomiesLabel = [];

                // Loop through WooCommerce registered product attributes
                foreach( wc_get_attribute_taxonomies() as $values ) {
                    $taxonomieKey = 'pa_' . $values->attribute_name;
                    if(!array_key_exists($taxonomieKey, $taxonomies)) {
                        $taxonomies[$taxonomieKey] = [];
                    }

                    // Get the array of term names for each product attribute
                    $term_names = get_terms( array('taxonomy' => $taxonomieKey ) );

                    $taxonomies[$taxonomieKey] = $term_names;

                    $taxonomiesLabel[$taxonomieKey] = $values->attribute_label;
                }

                foreach ($taxonomies as $taxonomieKey => $values) {
                    $taxonomieLabel = array_key_exists($taxonomieKey, $taxonomiesLabel) ? $taxonomiesLabel[$taxonomieKey] : null;
                    if(!$taxonomieLabel) {
                        continue;
                    }

                    foreach($values as $value) {
                        echo '<option value="' . esc_attr($value->term_id) . '" ' . ($attribute == $value->term_id ? 'selected' : '') . '>' . 
                                esc_html($taxonomieLabel . ": " . $value->name)
                            . '</option>';
                    }
                }
                ?>
            </select>
        </div>

    </div>
    <div class="remove-condition">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
            <path d="M17 1L1 17M1 1L17 17" stroke="#D82C0D" stroke-width="2" stroke-linecap="round" />
        </svg>
    </div>
</div>