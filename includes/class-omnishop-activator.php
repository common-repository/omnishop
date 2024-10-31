<?php

/**
 * Fired during plugin activation
 *
 * @link       https://omnishopapp.com
 * @since      1.0.0
 *
 * @package    Omnishop
 * @subpackage Omnishop/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Omnishop
 * @subpackage Omnishop/includes
 * @author     Dusan <dusan@omnishopapp.com>
 */
class Omnishop_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$has_minimum_woocommerce_version = class_exists('Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema');
		if (!$has_minimum_woocommerce_version) {
			die('Plugin NOT activated: Please install the newest version of WooCommerce');
		}

	}

}
