<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://omnishopapp.com
 * @since      1.0.0
 *
 * @package    Omnishop
 * @subpackage Omnishop/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Omnishop
 * @subpackage Omnishop/includes
 * @author     Dusan <dusan@omnishopapp.com>
 */
class Omnishop {

	//allowed values for Home Page sections
	public const ALLOWED_HOME_SECTION_VALUES = [
		'banner' 					=> 'Banner',
		'bannerSquare' 				=> 'Squared Banner',
		'sliderFeatured' 			=> 'Slider With Featured Products',
		'sliderPopular' 			=> 'Slider With Popular Products',
		'sliderNewest' 				=> 'Slider With Newest Products',
		'popular' 					=> 'Popular Products',
		'newest' 					=> 'Newest Products',
		'categoriesImageVertical' 	=> 'Categories In Multiple Rows',
		'categoriesTextFullWidth' 	=> 'Categories With Full Title Visible',
		'categoriesImageHorizontal' => 'Categories In Single Row'																																								
	];

	//allowed values for Home Page sections
	public const ALLOWED_BANNER_ACTIONS = [
		'category',
		'search',
		'tag',
		'product',
		'external_url'
	];

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Omnishop_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	protected $plugin_name;

	protected $version;

	public function __construct() {
		if ( defined( 'OMNISHOP_VERSION' ) ) {
			$this->version = OMNISHOP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'omnishop';


        $this->add_dependency_check();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->register_general_setup();

		$this->register_storeapi_extension();

		$this->register_omnishop_api_extension();

		
	}



	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Omnishop_Loader. Orchestrates the hooks of the plugin.
	 * - Omnishop_i18n. Defines internationalization functionality.
	 * - Omnishop_Admin. Defines all hooks for the admin area.
	 * - Omnishop_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-omnishop-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-omnishop-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-omnishop-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-omnishop-public.php';

		/**
		 * The class responsible for extending the WooCommerce StoreAPI
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-omnishop-storeapi.php';


		/**
		 * The class responsible for the omnishop API calls
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-omnishop-api.php';
		

		$this->loader = new Omnishop_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Omnishop_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Omnishop_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}


	private function register_general_setup() {

		$this->loader->add_filter('nonce_life', $this, 'modify_nonce_life_defaults', 99, 1);

	}

	//Set default nonce lifetime to 4 days
	public function modify_nonce_life_defaults($lifespan) {
		return DAY_IN_SECONDS * 4;
	}

	private function register_storeapi_extension() {
		//TODO: We didn't use the loaders's add_action because this was easier. Can be refactored, low value
		add_action( 'woocommerce_blocks_loaded', function() {
			//check if StoreAPI namespace exists (Older versions of WooCommerce Blocks prior to v7.2)
			if (class_exists('Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema')) {
				$extend = Omnishop_Storeapi::get_extend_schema();
				Omnishop_Storeapi::init( $extend );
			}
		});

		$storeAPI = new Omnishop_Storeapi();

		add_filter('woocommerce_product_get_price', [$storeAPI, 'calculate_dynamic_pricing'], 10, 2);
		add_filter('woocommerce_product_get_sale_price', [$storeAPI, 'calculate_dynamic_pricing'], 10, 2);
		add_filter('woocommerce_product_variation_get_price', [$storeAPI, 'calculate_dynamic_pricing'], 10, 2);
		add_filter('woocommerce_product_variation_get_sale_price', [$storeAPI, 'calculate_dynamic_pricing'], 10, 2);
		add_filter('woocommerce_product_is_on_sale', [$storeAPI, 'calculate_dynamic_pricing_is_on_sale'], 10, 2);
		
		add_filter('woocommerce_product_get_description', [$storeAPI, 'clear_description_field'], 10, 2);
		add_filter('woocommerce_product_get_short_description', [$storeAPI, 'clear_description_field'], 10, 2);
		
	}

	public function register_omnishop_api_extension() {

		add_action( 'rest_api_init', function () {
			
			$omnishopApi = new Omnishop_Api();

			//Version
			register_rest_route( 'omnishop/v1', 'apiversion', array(
					'methods' => 'GET',
					'callback' => [$this, 'get_version'],
					'permission_callback' => '__return_true',
				)
			);

			//Currency
			register_rest_route( 'omnishop/v1', 'currency', array(
					'methods' => 'GET',
					'callback' => [$omnishopApi, 'get_currency'],
					'permission_callback' => '__return_true',
				)
			);
			
			//Categories & Taxonomies
			register_rest_route( 'omnishop/v1', 'categories', array(
					'methods' => 'GET',
					'callback' => [$omnishopApi, 'get_product_categories'],
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route( 'omnishop/v1', '/categories/(?P<parent>\d+)', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'get_product_categories'],
				'permission_callback' => '__return_true',
				'args' => array(
					'parent' => array(
						'validate_callback' => function($param, $request, $key) {
							return is_numeric( $param );
						}
					),
				),
			));


			//Banners
			register_rest_route( 'omnishop/v1', 'banners', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'get_banners'],
				'permission_callback' => '__return_true',
			));
			
			
			/**
			 * Homepage settings
			 */
			register_rest_route( 'omnishop/v1', 'home_sections', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'get_home_page_sections'],
				'permission_callback' => '__return_true',
			));

			/**
			 * Handle User request.
			 */
			register_rest_route('omnishop/v1', 'users/login', array(
				'methods' => 'POST',
				'callback' => [$omnishopApi, 'login_user'],
				'permission_callback' => '__return_true',
			));
			register_rest_route('omnishop/v1', 'users/register', array(
				'methods' => 'POST',
				'callback' => [$omnishopApi, 'register_user'],
				'permission_callback' => '__return_true',
			));
			register_rest_route('omnishop/v1', 'users/lostpassword', array(
				'methods' => 'POST',
				'callback' => [$omnishopApi, 'lost_password'],
				'permission_callback' => '__return_true',
			));

			register_rest_route('omnishop/v1', 'users/delete', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'delete_user'],
				'permission_callback' => function () {
					if (get_current_user_id() == 0) {
						return new WP_Error( 'woocommerce_rest_customer_access', __( 'Only customers can access.', 'omnishop' ), array( 'status' => 400 ) );
					}
					return true;
				},
			));


			/**
			 * Orders
			 */
			register_rest_route('omnishop/v1', 'orders/review', array(
				'methods' => 'POST',
				'callback' => [$omnishopApi, 'order_review'],
				'permission_callback' => function () {
					if (get_current_user_id() == 0) {
						return new WP_Error( 'woocommerce_rest_customer_access', __( 'Only customers can access.', 'omnishop' ), array( 'status' => 400 ) );
					}
					return true;
				},
			));

			register_rest_route('omnishop/v1', 'orders/history', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'order_history'],
				'permission_callback' => function () {
					if (get_current_user_id() == 0) {
						return new WP_Error( 'woocommerce_rest_customer_access', __( 'Only customers can access.', 'omnishop' ), array( 'status' => 400 ) );
					}
					return true;
				},
				
			));
			register_rest_route('omnishop/v1', 'orders/history/(?P<id>\d+)', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'order_history_one'],
				'args' => array(
					'id' => array(
						'validate_callback' => function($param, $request, $key) {
							return is_numeric( $param );
						}
					),
				),
				'permission_callback' => function () {
					if (get_current_user_id() == 0) {
						return new WP_Error( 'woocommerce_rest_customer_access', __( 'Only customers can access.', 'omnishop' ), array( 'status' => 400 ) );
					}
					return true;
				},
			));

			/**
			 * Shipping requests.
			 */
			register_rest_route('omnishop/v1', 'shipping/allowed_countries', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'shipping_allowed_countries'],
				'permission_callback' => '__return_true',
			));

			/**
			 * Taxonomies and Terms
			 */
			register_rest_route('omnishop/v1', 'filter_taxonomies', array(
				'methods' => 'GET',
				'callback' => [$omnishopApi, 'filter_taxonomies'],
				'permission_callback' => '__return_true',
			));

			/**
			 * Coupons
			 */
			register_rest_route('omnishop/v1', 'available_coupons', array(
				'methods' => 'POST',
				'callback' => [$omnishopApi, 'available_coupons'],
				'permission_callback' => '__return_true',
			));

		});
		
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Omnishop_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_pages');

		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'add_orders_column' );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'add_orders_column_values', 20, 2 );

		$this->loader->add_action( 'woocommerce_coupon_options', $plugin_admin, 'add_coupon_allow_field');
		$this->loader->add_action( 'woocommerce_coupon_options_save', $plugin_admin, 'save_coupon_allow_field', 10, 2 );;

		$this->loader->add_action( 'init', $plugin_admin, 'register_taxonomy_banners' );
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Omnishop_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		
		$this->loader->add_action( 'woocommerce_checkout_before_customer_details', $plugin_public, 'checkout_page_check_if_mobile' );
		$this->loader->add_action( 'woocommerce_new_order', $plugin_public, 'new_order_set_mobile_status', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Omnishop_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


    
    public function add_dependency_check() {
        add_action(
            'plugins_loaded',
            function() {
                if ( ! class_exists( '\WooCommerce' ) ) { // or whatever your class might be
                    add_action(
                        'admin_notices',
                        function() {
                            ?>
                            <div class="error notice">
								<p><b>OMNISHOP</b> mobile shop plugin is activated, but it requires all the necessary dependencies in order to function.</p>
								<ol>
									<li>WooCommerce</li>
									<li>* <i>WooCommerce Blocks [if still using WooCommerce < 6.4]</i></li>
								</ol>
                            </div>
                            <?php
                        }
                    );
                }
            }
        );
    }


}
