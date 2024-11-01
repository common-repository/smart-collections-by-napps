<?php

/**
 * The template for displaying the edit-tags.php table footer
 *
 * @version 1.0.0
 */
defined('ABSPATH') || die('No script kiddies please!');
?>

<div class="form-field napps-sc-fields">
    <?php wp_nonce_field('napps-smart-collection', 'napps-smart-collection-nonce'); ?>
    <label for="napps-sc-conditions" class="conditions-title"><?php esc_html_e('Conditions', 'smart-collections-by-napps'); ?></label>
    <p class="conditions-desc">
        <?php esc_html_e('Existing and upcoming products that match the conditions will automatically be added to this collection.', 'smart-collections-by-napps'); ?>
    </p>
    <div class="must-meet">
        <span>        
            <?php esc_html_e('Products must meet:', 'smart-collections-by-napps'); ?>
        </span>
        <div class="must-meet-elements">
            <div class="must-meet-radio-element">
                <input required type="radio" <?php echo $mustMeet === 'all_conditions' ? 'checked' : '' ?> id="all_conditions" name="must-meet" value="all_conditions">
                <label for="all_conditions"><?php esc_html_e('All conditions', 'smart-collections-by-napps'); ?></label>
            </div>

            <div class="must-meet-radio-element">
                <input required type="radio" <?php echo $mustMeet === 'any_condition' ? 'checked' : '' ?> id="any_condition" name="must-meet" value="any_condition">
                <label for="any_condition"><?php esc_html_e('Any condition', 'smart-collections-by-napps'); ?></label>
            </div>
        </div>

    </div>
    <div class="conditions-outter">
        <div class="conditions-list">
            <?php if (!empty($conditions)) {
                foreach ($conditions as $key => $condition) {
                    NappsSmartCollections\Helper::render_template("condition", "admin", array("key" => $key, "condition" => $condition));
                }
            } else {
                NappsSmartCollections\Helper::render_template("condition", "admin", array("key" => null, "condition" => null));
            } ?>
        </div>
        <div class="conditions-new-button">
            <input type="button" class="button button-large" value="<?php esc_attr_e('+ Add Condition', 'smart-collections-by-napps'); ?>" />
        </div>
    </div>
</div>