<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email Priority Class
 *
 * Main class for saving email priority and getting
 * priority list
 *
 * @since 1.1.0
 * @package shuffled
 */
class SI_Email_Priority {

	/**
	 * AJAX endpoint
	 */
	const AJAX_PRIORITIES = 'shuffled_get_priorities';


	/**
	 * Default priorities
	 */
  const TEST_FIELDS = array(
    'cards_poker' => 'Poker Cards',
    'cards_tarot' => 'Tarot Cards'
  );


	/**
	 * Singleton instance
	 */
	private static $instance;


	/**
	 * Get things going, singleton style
	 *
	 * @since 1.1.0
	 */
	function __construct() {
		// add our admin stuff
		if ( is_admin() ) {
			$this->add_options_pages();
    }

    add_filter('acf/load_field/name=pqt_product', array($this, 'load_product_fields'));
	}

	/**
	 * Add pricing options pages
	 *
	 * @since 1.1.0
	 */
	private function add_options_pages() {
    
		if ( function_exists('acf_add_options_page') ) {

			// cards
			acf_add_options_page( array(
				'post_id'		=> 'misc_si_features',
				'page_title' 	=> 'Miscellaneous Features',
				'menu_title'	=> 'Misc Features',
				'menu_slug' 	=> 'misc-si-features',
				'icon_url'		=> 'dashicons-star-filled',
				'capability'	=> 'edit_posts',
				'redirect'		=> true,
				'position'		=> 51,
			));
			acf_add_options_sub_page( array(
				'post_id'		=> 'quote_priorities',
				'page_title' 	=> 'Edit Request a Quote Priorities',
				'menu_title'	=> 'Quote Priorities',
				'menu_slug'		=> 'quote-priorities',
				'parent_slug'	=> 'misc-si-features',
			));
			
		} // if ACF
	}

  public function load_product_fields( $field ) {
      $field['choices'] = self::TEST_FIELDS;
      
      // return the field
      return $field;
  }

	/**
	 * Create an instance of this class
	 *
	 * @since 1.1.0
	 */
	public static function instance() {
    
		if ( ! empty( self::$instance ) ) {
			return self::$instance;
		}

		return ( self::$instance = new self );
	}
}