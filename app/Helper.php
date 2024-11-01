<?php
namespace NappsSmartCollections;

/**
 * Helper function
 */
class Helper {
    
    /**
     * Render Template
     *
     * @param  string $name template name without .php
     * @param  string $folder folder where template is located
     * @param  array $data
     * @return void
     */
    public static function render_template( $name = '', $folder = '', $data = array()) {
		/**
		 * Default template
		 */
		if ( $folder ) {
			$folder = $folder . '/';
		}
		$template_file = NAPPS_SMARTCOLLECTIONS_PATH . 'templates/' . $folder . $name . '.php';

		if ( ! file_exists( $template_file ) ) {
			return;
		}

		extract( $data ); // @codingStandardsIgnoreLine

		include $template_file;
	}

	
	/**
	 * Sanitize array
	 *
	 * @param  array $arr
	 * @return array
	 */
	public static function sanitize_array( $arr ) {
		foreach ( (array) $arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$arr[ $k ] = Helper::sanitize_array( $v );
			} else {
				$arr[ $k ] = Helper::sanitize_text_field( $v );
			}
		}

		return $arr;
	}
	
	/**
	 * Sanitize text field
	 *
	 * @param  mixed $text
	 * @return void
	 */
	public static function sanitize_text_field($text) {
		return sanitize_text_field( $text );
	}
}
