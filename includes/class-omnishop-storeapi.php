<?php


use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;

use \Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Extend the StoreAPI endpoint
 *
 * Extends the data returned for products from the Store API /products path
 *
 * @since      1.0.0
 * @package    Omnishop
 * @subpackage Omnishop/includes
 * @author     Dusan <dusan@omnishopapp.com>
 */

class Omnishop_Storeapi {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'omnishop';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
	 *
	 * @since 3.1.0
	 */
	public static function init( ExtendSchema $extend_rest_api ) {
		self::$extend = $extend_rest_api;
		self::extend_store();
	}

	public static function get_extend_schema() {
		return StoreApi::container()->get( ExtendSchema::class );
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {

		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => ProductSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'Omnishop_Storeapi', 'extend_product_item_data' ),
				'schema_type'       => ARRAY_A,
			)
		);
	}

	/**
	 * Additional data fetched for product endpoint response
	 *
	 * @param array $cart_item Current cart item data.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_product_item_data( $product ) {
		$item_data = [];

		if (is_a( $product, '\WC_Product_Simple' ) or is_a($product, '\WC_Product_Variable')) {
			$extended_data = [
				'tax_class' => $product->get_tax_class(),
				'status' => $product->get_status(),
				'reviews_allowed' => $product->get_reviews_allowed(),
			];

			if (self::is_dokan_available()) {
				$seller = get_post_field( 'post_author', $product->get_id());
				
				$vendor = dokan()->vendor->get( $seller );

				$extended_data['dokan_storename'] =  $vendor->get_shop_name();
			}

			if (is_a($product, '\WC_Product_Variable')) {
				$extended_data['variations'] = $product->get_available_variations();
			}

			$item_data[] = $extended_data;
		}

		return $item_data;
	}

	private static function is_dokan_available() {
		return class_exists('WeDevs_Dokan', false);
	}
	
	/**
	 * We cover "WooCommerce Dynamic Pricing & Discounts" for now
	 */
	static function has_dynamic_pricing() {
		return class_exists('RP_WCDPD', false);
	}

	function calculate_dynamic_pricing($price, $product) {
		//only works in the REST API calls
		global $wp;
		$is_product = stripos($wp->request, '/product');
		if (defined('REST_REQUEST') && self::has_dynamic_pricing() && $is_product) {
			$dynamic_price = RP_WCDPD_Product_Pricing::apply_simple_product_pricing_rules_to_product_price($price, $product);
			
			if ($dynamic_price != $price) {
				return $dynamic_price;
			}
		}
		return $price;
	}
	
	function calculate_dynamic_pricing_is_on_sale($field, $product) {
		//only works in the REST API calls
		global $wp;
		$is_product = stripos($wp->request, '/product');
		if (!$field && defined('REST_REQUEST') && self::has_dynamic_pricing() && $is_product) {
    		$price = $product->get_price();
    		$regular_price = $product->get_regular_price();
           
            if (is_numeric($price) && is_numeric($regular_price)) {
                if ($price != $regular_price) {
                    return true;
                }
            } else if (is_a($product, '\WC_Product_Variable') && !$field) {
                $prices = $product->get_variation_prices();

                $is_regular_price = in_array($price, $prices['price']);
                return !$is_regular_price;
            }
		}
		return $field;
	}

	function clear_description_field($field, $product) {
		return self::remove_wordpress_shortcodes($field);
	}
	
	private function remove_wordpress_shortcodes($field) {
		$filtered_field = strip_shortcodes($field);
		return $filtered_field;
	}


}
