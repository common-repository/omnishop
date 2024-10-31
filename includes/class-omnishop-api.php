<?php


///we might extend the WC_REST_Terms_Controller 

/**
 * Define the API endpoint functions
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Omnishop
 * @subpackage Omnishop/includes
 * @author     Dusan <dusan@omnishopapp.com>
 */

class Omnishop_Api {


	/**
	 * Multicurrency
	 */
	public function get_currency() {
		$current_currency = get_woocommerce_currency();
		//TODO: Add support for WCML multicurrency
		// $current_currency = apply_filters('wcml_price_currency', NULL );

		// $wc_currencies = get_woocommerce_currencies();

		return $current_currency;
	}


	/**
	 * Get a listing of product categories
	 *
	 * We are usig a legacy API implementation copy here for simplicity
	 * It was also easier to copy and remove the auth restrictions
	 *
	 * @param string|null $fields fields to limit response to
	 *
	 * @return array|WP_Error
	 */
	public function get_product_categories( $request = null ) {
		try {

			$parent = '';
			if (is_a($request, 'WP_REST_REQUEST') && is_numeric($request->get_param('parent'))) {
				$parent = $request->get_param('parent');
			}
			//Make into a config option if there is a need for it
			$hide_empty_caategories = true;

			$terms = get_terms( ['taxonomy' => 'product_cat', 'hide_empty' => $hide_empty_caategories, 'fields' => 'ids', 'parent' => $parent ] );
			
			$product_categories = [];
			foreach ( $terms as $term_id ) {
				$product_categories[] = current( $this->get_product_category( $term_id ) );
			}
			$fields = null;

			return apply_filters( 'woocommerce_api_product_categories_response', $product_categories, $terms, $fields, $this );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}
	/**
	 * Get a listing of banners (taxonomy "omnishop_banner")
	 *
	 * @return array|WP_Error
	 */
	public function get_banners( $request = null ) {
		try {

			$terms = get_terms( array('taxonomy' => 'omnishop_banner', 'hide_empty' => false) );
			
			$banners = array();
			foreach ( $terms as $term ) {
				
				if ( is_wp_error( $term ) || is_null( $term ) ) {
					throw new WC_API_Exception( 'woocommerce_api_invalid_product_category_id', __( 'A product category with the provided ID could not be found', 'woocommerce' ), 404 );
				}
				// Get banner action
				$image_full_width = get_term_meta( $term->term_id, 'omnishop_banner_image_full_width', true );
				$button_text = get_term_meta( $term->term_id, 'omnishop_banner_button_text', true );
				$bottom_text = get_term_meta( $term->term_id, 'omnishop_banner_bottom_text', true );
				$top_text = get_term_meta( $term->term_id, 'omnishop_banner_top_text', true );
				$action = get_term_meta( $term->term_id, 'omnishop_banner_action', true );
				$action_param = get_term_meta( $term->term_id, 'omnishop_banner_action_param', true );
				$action_title = get_term_meta( $term->term_id, 'omnishop_banner_action_title', true );
				$action_on_sale = get_term_meta( $term->term_id, 'omnishop_banner_action_on_sale', true );

				// Get banner image
				$image = '';
				if ( $image_id = get_term_meta( $term->term_id, 'omnishop_banner_img', true ) ) {
					$image = wp_get_attachment_url( $image_id );
					// wp_get_attachment_image_url
				}
				
				//response needs:
				//topText, mainText, bottom_text, button_text, image, imageFullWidth, action
				$banner = array(
					'id'           => $term->term_id,
					'position'     => $term->slug,
					'topText'      => $top_text,
					'mainText'     => $term->description,
					'buttonText'   => $button_text,
					'bottomText'   => $bottom_text,
					'action'       => $action,
					'actionParam'  => $action_param,
					'actionTitle'  => $action_title,
					'image'        => $image ? esc_url( $image ) : '',
					'imageFullWidth' => $image_full_width,
					'actionOnSale' => $action_on_sale
				);

				$banners[] = $banner;
			}

			return $banners;
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get the product category for the given ID
	 *
	 * @param string $id product category term ID
	 * @param string|null $fields fields to limit response to
	 *
	 * @return array|WP_Error
	 */
	private function get_product_category( $id, $fields = null ) {
		try {
			$id = absint( $id );

			// Validate ID
			if ( empty( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_category_id', __( 'Invalid product category ID', 'woocommerce' ), 400 );
			}

			$term = get_term( $id, 'product_cat' );

			if ( is_wp_error( $term ) || is_null( $term ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_category_id', __( 'A product category with the provided ID could not be found', 'woocommerce' ), 404 );
			}

			$term_id = intval( $term->term_id );

			// Get category display type
			$display_type = get_term_meta( $term_id, 'display_type', true );


			// Get category exclusion from mobile
			$exclude_from_mobile = get_term_meta( $term_id, 'product_cat_fields_exclude_mobile', true );

			// Get category image
			$image = '';
			if ( $image_id = get_term_meta( $term_id, 'thumbnail_id', true ) ) {
				$image = wp_get_attachment_url( $image_id );
			}

			$product_category = array(
				'id'                  => $term_id,
				'name'                => $term->name,
				'slug'                => $term->slug,
				'parent'              => $term->parent,
				'description'         => $term->description,
				'display'             => $display_type ? $display_type : 'default',
				'image'               => $image ? esc_url( $image ) : '',
				'count'               => intval( $term->count ),
				'exclude_from_mobile' => intval( $exclude_from_mobile ),
			);

			return array( 'product_category' => apply_filters( 'woocommerce_api_product_category_response', $product_category, $id, $fields, $term, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Homepage sections
	 */
	public function get_home_page_sections() {
		$sections_option = esc_attr( get_option('homepage_sections'));
		if ($sections_option && $sections = array_map('trim', explode(',', $sections_option))) {
			if (is_array($sections)) {
				$allowed_values = array_keys(Omnishop::ALLOWED_HOME_SECTION_VALUES);
				$sections_verified = array_intersect($sections, $allowed_values);
				return array_values($sections_verified);
			};
		}
		
		return new WP_Error( 'omnishop_home_sections_error', 'Sections not set in an appropriate format', array( 'status' => 400 ) );
	}


	/**
	 * Log in user via username or email in the request body and Password
	 * Session/cookie based login
	 *
	 * @param [type] $request [description]
	 *
	 * @return [type] [description]
	 */
	public function login_user($request = null) {

		$response = array();
		$parameters = $request->get_json_params();
		$user_login = sanitize_text_field($parameters['user_login']);
		$password = sanitize_text_field($parameters['password']);
		$error = new WP_Error();

		if (empty($password)) {
			$error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($user_login)) {
			$error->add(400, __("The field 'user_login' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}

		$creds = array(
			'user_login'    => $user_login,
			'user_password' => $password,
			'remember'      => true
		);
		
		$user = wp_signon( $creds, false );
		
		if ( is_wp_error( $user ) ) {
			$error->add(401, $user->get_error_message(), array('status' => 401));
			return $error;
		}else{
			
			/* Since we set a new cookie with wp_set_auth_cookie, that is available in $_COOKIE variable
			only in the next request. But we need that fresh value for the wp_create_nonce. 
			Thus we will add our hook being called when the new cookie is generated and update the $_COOKIE manually now.
			*/
			add_action( 'set_logged_in_cookie', [$this, 'my_update_cookie'] );

			// wp_clear_auth_cookie();
			wp_set_current_user ( $user->ID ); // Set the current user detail
			wp_set_auth_cookie  ( $user->ID ); // Set auth details in cookie
			
			$nonce = $this->_omnishop_wp_create_nonce( 'wp_rest' );
			header('X-WP-Nonce: '.$nonce);
			
			$response = $this->prepare_user_response($user);

			return $response;
		}

	}

	/**
	 * Implementation taken from WP core (file pluggable.php)
	 * Since wp_create_nonce is a pluggable funcion and LSCache overrides it when you use ESI blocks and we need the original
	 */
	private function _omnishop_wp_create_nonce( $action = -1 ) {
		$user = wp_get_current_user();
		$uid  = (int) $user->ID;
		if ( ! $uid ) {
			/** This filter is documented in wp-includes/pluggable.php */
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		$token = wp_get_session_token();
		$i     = wp_nonce_tick( $action );

		return substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
	}

	
	public function my_update_cookie( $logged_in_cookie ){
		$_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
	}
	
	private function prepare_user_response($user) {
		
		$response = [
			'id' => $user->ID, 
			'user_login' => $user->user_login, 
			'email' => $user->user_email,
			'roles' => array_values($user->roles), //clean up roles if any of the plugins made it into a dictionary
		];

		//We return the Customer object for all roles. User can be Admin,Vendor,Shop Owner...
		$customer = new WC_Customer( $user->ID );
		$response += [
			'first_name'   => $customer->get_first_name(),
			'last_name'    => $customer->get_last_name(),
			'display_name' => $customer->get_display_name(),

			// Customer billing information details (from account,
			'billing_first_name' => $customer->get_billing_first_name(),
			'billing_last_name'  => $customer->get_billing_last_name(),
			'billing_company'    => $customer->get_billing_company(),
			'billing_address_1'  => $customer->get_billing_address_1(),
			'billing_address_2'  => $customer->get_billing_address_2(),
			'billing_city'       => $customer->get_billing_city(),
			'billing_state'      => $customer->get_billing_state(),
			'billing_postcode'   => $customer->get_billing_postcode(),
			'billing_country'    => $customer->get_billing_country(),
			'billing_phone'      => $customer->get_billing_phone(),
			
			//Customer shipping information details (from account,
			'shipping_first_name' => $customer->get_shipping_first_name(),
			'shipping_last_name'  => $customer->get_shipping_last_name(),
			'shipping_company'    => $customer->get_shipping_company(),
			'shipping_address_1'  => $customer->get_shipping_address_1(),
			'shipping_address_2'  => $customer->get_shipping_address_2(),
			'shipping_city'       => $customer->get_shipping_city(),
			'shipping_state'      => $customer->get_shipping_state(),
			'shipping_postcode'   => $customer->get_shipping_postcode(),
			'shipping_country'    => $customer->get_shipping_country(),
			'shipping_phone'      => $customer->get_shipping_phone(),
		];
				
		return $response;
	}

	/**
	 * Get the user and password in the request body and Register a User
	 *
	 *
	 * @param [type] $request [description]
	 *
	 * @return [type] [description]
	 */
	public function register_user($request = null) {

		$response = array();
		$parameters = $request->get_json_params();
		$username = sanitize_text_field($parameters['username']);
		$email = sanitize_text_field($parameters['email']);
		$password = sanitize_text_field($parameters['password']);
		$error = new WP_Error();
		
		$role = 'customer';

		if (empty($username)) {
			$error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($email)) {
			$error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($password)) {
			$error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}

		$user_id = username_exists($username);
		if (!$user_id && email_exists($email) == false) {
			$user_id = wp_create_user($username, $password, $email);
			if (!is_wp_error($user_id)) {
				// Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
				$user = get_user_by('id', $user_id);
				$user->set_role($role);

				/* Since we set a new cookie with wp_set_auth_cookie, that is available in $_COOKIE variable
				only in the next request. But we need that fresh value for the wp_create_nonce. 
				Thus we will add our hook being called when the new cookie is generated and update the $_COOKIE manually now.
				*/
				add_action( 'set_logged_in_cookie', [$this, 'my_update_cookie'] );

				// wp_clear_auth_cookie();
				wp_set_current_user ( $user->ID ); // Set the current user detail
				wp_set_auth_cookie  ( $user->ID ); // Set auth details in cookie
				
				$nonce = $this->_omnishop_wp_create_nonce( 'wp_rest' );
				header('X-WP-Nonce: '.$nonce);

				// Ger User Data (Non-Sensitive, Pass to front end.)
				$response = $this->prepare_user_response($user);

				return $response;
				// $response['code'] = 200;
				// $response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
			} else {
				//this is a WP_Error class
				return $user_id;
			}
		} else if ($user_id) {
			$error->add(406, __("Username already exists, please enter another username", 'wp-rest-user'), array('status' => 400));
			return $error;
		} else {
			$error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
	}

	/**
	 * Get the username or email in the request body and Send a Forgot Password email
	 *
	 * @param [type] $request [description]
	 *
	 * @return [type] [description]
	 */
	public function lost_password($request = null) {

		$response = array();
		$parameters = $request->get_json_params();
		$user_login = sanitize_text_field($parameters['user_login']);
		$error = new WP_Error();
		$user_id = 0;

		if (empty($user_login)) {
			$error->add(400, __("The field 'user_login' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		} else {
			$user_id = username_exists($user_login);
			if ($user_id == false) {
				$user_id = email_exists($user_login);
				if ($user_id == false) {
					$error->add(401, __("User '" . $user_login . "' not found.", 'wp-rest-user'), array('status' => 400));
					return $error;
				}
			}
		}

		$user = new WP_User(intval($user_id));
		$reset_key = get_password_reset_key($user);
		$wc_emails = WC()->mailer()->get_emails();
		$wc_emails['WC_Email_Customer_Reset_Password']->trigger($user->user_login, $reset_key);


		$response['success'] = true;
		return $response;
	}


	/**
	 * Send a delete user request to the site admin via email
	 *
	 * @param [type] $request [description]
	 *
	 * @return [type] [description]
	 */
	public function delete_user($request = null) {
		$customer = wp_get_current_user();
		
		$to = get_option( 'admin_email' );
		$subject = 'User deletion requested';

		$user_name = $customer->get('user_nicename');
		$user_email = $customer->get('user_email');
		$body = "The user $user_name with email $user_email requested to be deleted";

		wp_mail( $to, $subject, $body);
		
		$response = ['success' => true];
		return $response;
	}


	

	

	/**
	 * ORDERS API
	 */

	/**
	 * Taken from WC_REST_Product_Reviews_Controller
	 */
	public function order_review($request = null) {
		$response = array();
		$parameters = $request->get_json_params();
		$product_id = sanitize_text_field($parameters['product_id']);
		$rating = sanitize_text_field($parameters['rating']);
		$review = sanitize_text_field($parameters['review']);
		$error = new WP_Error();
		

		if (empty($product_id) || !is_numeric($product_id)) {
			$error->add(400, __("Product ID is required.", 'omnishop'), array('status' => 400));
			return $error;
		}
		if (empty($rating) || !is_numeric($rating) || $rating < 0 || $rating > 5) {
			$error->add(401, __("Valid rating is required.", 'omnishop'), array('status' => 400));
			return $error;
		}
		if (empty($review)) {
			$error->add(404, __("Review field 'review' is required.", 'omnishop'), array('status' => 400));
			return $error;
		}

		$customer = wp_get_current_user();

		if ( 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$prepared_review['comment_type'] = 'review';
		$prepared_review['comment_content'] = $review;
		$prepared_review['comment_post_ID'] = $product_id;
	
		$prepared_review['comment_author'] = $customer->get('user_nicename');
		$prepared_review['comment_author_email'] = $customer->get('user_email');


		// Setting remaining values before wp_insert_comment so we can use wp_allow_comment().
		if ( ! isset( $prepared_review['comment_date_gmt'] ) ) {
			$prepared_review['comment_date_gmt'] = current_time( 'mysql', true );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) && rest_is_ip_address( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) { // WPCS: input var ok, sanitization ok.
			$prepared_review['comment_author_IP'] = wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // WPCS: input var ok.
		} else {
			$prepared_review['comment_author_IP'] = '127.0.0.1';
		}

		if ( ! empty( $request['author_user_agent'] ) ) {
			$prepared_review['comment_agent'] = $request['author_user_agent'];
		} elseif ( $request->get_header( 'user_agent' ) ) {
			$prepared_review['comment_agent'] = $request->get_header( 'user_agent' );
		} else {
			$prepared_review['comment_agent'] = '';
		}

		$check_comment_lengths = wp_check_comment_data_max_lengths( $prepared_review );
		if ( is_wp_error( $check_comment_lengths ) ) {
			$error_code = str_replace( array( 'comment_author', 'comment_content' ), array( 'reviewer', 'review_content' ), $check_comment_lengths->get_error_code() );
			return new WP_Error( 'woocommerce_rest_' . $error_code, __( 'Product review field exceeds maximum length allowed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$prepared_review['comment_parent']     = 0;
		$prepared_review['comment_author_url'] = '';
		$prepared_review['comment_approved']   = wp_allow_comment( $prepared_review, true );

		if ( is_wp_error( $prepared_review['comment_approved'] ) ) {
			$error_code    = $prepared_review['comment_approved']->get_error_code();
			$error_message = $prepared_review['comment_approved']->get_error_message();

			if ( 'comment_duplicate' === $error_code ) {
				return new WP_Error( 'woocommerce_rest_' . $error_code, $error_message, array( 'status' => 409 ) );
			}

			if ( 'comment_flood' === $error_code ) {
				return new WP_Error( 'woocommerce_rest_' . $error_code, $error_message, array( 'status' => 400 ) );
			}

			return $prepared_review['comment_approved'];
		}

		/**
		 * Filters a review before it is inserted via the REST API.
		 *
		 * Allows modification of the review right before it is inserted via wp_insert_comment().
		 * Returning a WP_Error value from the filter will shortcircuit insertion and allow
		 * skipping further processing.
		 *
		 * @since 3.5.0
		 * @param array|WP_Error  $prepared_review The prepared review data for wp_insert_comment().
		 * @param WP_REST_Request $request          Request used to insert the review.
		 */
		$prepared_review = apply_filters( 'woocommerce_rest_pre_insert_product_review', $prepared_review, $request );
		if ( is_wp_error( $prepared_review ) ) {
			return $prepared_review;
		}

		$review_id = wp_insert_comment( wp_filter_comment( wp_slash( (array) $prepared_review ) ) );

		if ( ! $review_id ) {
			return new WP_Error( 'woocommerce_rest_review_failed_create', __( 'Creating product review failed.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		update_comment_meta( $review_id, 'rating', $rating );

		$comment = get_comment( $review_id );

		$response['success'] = true;
		$response['verified'] = wc_review_is_from_verified_owner( $comment->comment_ID );
		$response['status'] = $this->prepare_status_response( (string) $comment->comment_approved );
		return $response;

	}

	/**
	 * Checks comment_approved to set comment status for single comment output.
	 *
	 * @since 3.5.0
	 * @param string|int $comment_approved comment status.
	 * @return string Comment status.
	 */
	protected function prepare_status_response( $comment_approved ) {
		switch ( $comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;
			case 'approve':
			case '1':
				$status = 'approved';
				break;
			case 'spam':
			case 'trash':
			default:
				$status = $comment_approved;
				break;
		}

		return $status;
	}

	public function order_history($request) {
		try {

			$page = absint($request->get_param('page') ?? 1);
			$perpage = absint($request->get_param('perpage') ?? 10);
			$filter['offset']  = ($page-1)*$perpage;
			$filter['limit']  = $perpage;

			$orders = $this->get_orders($filter);

			return $orders;
			
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	public function order_history_one($request) {
		try {
			$id = '';
			if (is_a($request, 'WP_REST_REQUEST') && is_numeric($request->get_param('id'))) {
				$id = $request->get_param('id');
			}
			$order = $this->get_order($id);

			return $order;
			
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get all orders
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param array $filter
	 * @param string $status
	 * @param int $page
	 * @return array
	 */
	public function get_orders($filter = array()) {

		$query = $this->query_orders( $filter );
		
		$orders = array();
		foreach ( $query->posts as $order_id ) {
			$orders[] = current( $this->get_order( $order_id, null, $filter ) );
		}

		// $this->server->add_pagination_headers( $query );

		return $orders;
	}

	//Util function to format money values the same way StoreAPI does
	public static function format( $value, array $options = [] ) {
		$options = wp_parse_args(
			$options,
			[
				'decimals'      => wc_get_price_decimals(),
				'rounding_mode' => PHP_ROUND_HALF_UP,
			]
		);

		return (string) intval(
			round(
				( (float) wc_format_decimal( $value ) ) * ( 10 ** absint( $options['decimals'] ) ),
				0,
				absint( $options['rounding_mode'] )
			)
		);
	}

	/**
	 * Return the currency formating options.
	 * We don't support multicurrency options yet, they are module dependant and support will be added later
	 */
	public function get_format_currency_options( $currency ) {

		$position = get_option( 'woocommerce_currency_pos' );
		$symbol   = html_entity_decode( get_woocommerce_currency_symbol() );
		$prefix   = '';
		$suffix   = '';

		switch ( $position ) {
			case 'left_space':
				$prefix = $symbol . ' ';
				break;
			case 'left':
				$prefix = $symbol;
				break;
			case 'right_space':
				$suffix = ' ' . $symbol;
				break;
			case 'right':
				$suffix = $symbol;
				break;
		}

		return [
			'currency_code'               => $currency,
			'currency_symbol'             => $symbol,
			'currency_minor_unit'         => wc_get_price_decimals(),
			'currency_decimal_separator'  => wc_get_price_decimal_separator(),
			'currency_thousand_separator' => wc_get_price_thousand_separator(),
			'currency_prefix'             => $prefix,
			'currency_suffix'             => $suffix,
		];
	}



	/**
	 * Get the order for the given ID.
	 *
	 * @since 2.1
	 * @param int $id The order ID.
	 * @param array $fields Request fields.
	 * @param array $filter Request filters.
	 * @return array|WP_Error
	 */
	public function get_order( $id, $fields = null, $filter = array() ) {
		//TODO: Consider returning just a subset of fields

		// Get the decimal precession.
		$dp     = ( isset( $filter['dp'] ) ? intval( $filter['dp'] ) : wc_get_price_decimals() );
		$money_format_options = ['decimals' => $dp];

		$order  = wc_get_order( $id );
		$expand = array();


		if (!is_a($order, '\WC_Order') || $order->get_user_id() != get_current_user_id()) {
			return new WP_Error( "omnishop_api_cannot_access_order", "Customer has no access to this order or order does not exist", array( 'status' => 500 ) );
		}

		if ( ! empty( $filter['expand'] ) ) {
			$expand = explode( ',', $filter['expand'] );
		}

		$order_statuses = $this->get_order_statuses();
		$order_status_label = $order_statuses['order_statuses'][$order->get_status()];
		$order_status_label_translated = __($order_status_label, 'woocommerce');

		$order_data = array(
			'id'                        => $order->get_id(),
			'order_number'              => $order->get_order_number(),
			'order_key'                 => $order->get_order_key(),
			'created_at'                => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
			'updated_at'                => $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : 0,
			'completed_at'              => $order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : 0,
			'status'                    => $order->get_status(),
			'status_translated'         => $order_status_label_translated,
			'currency_code'             => $order->get_currency(),
			'total'                     => $this::format( $order->get_total(), $money_format_options),
			'subtotal'                  => $this::format( $order->get_subtotal(), $money_format_options ),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => $this::format( $order->get_total_tax(), $money_format_options ),
			'total_shipping'            => $this::format( $order->get_shipping_total(), $money_format_options ),
			'cart_tax'                  => $this::format( $order->get_cart_tax(), $money_format_options ),
			'shipping_tax'              => $this::format( $order->get_shipping_tax(), $money_format_options ),
			'total_discount'            => $this::format( $order->get_total_discount(), $money_format_options ),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details' => array(
				'method_id'    => $order->get_payment_method(),
				'method_title' => $order->get_payment_method_title(),
				'paid'         => ! is_null( $order->get_date_paid() ),
			),
			'billing_address' => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address_1'  => $order->get_billing_address_1(),
				'address_2'  => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'shipping_address' => array(
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'company'    => $order->get_shipping_company(),
				'address_1'  => $order->get_shipping_address_1(),
				'address_2'  => $order->get_shipping_address_2(),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			),
			'note'                      => $order->get_customer_note(),
			'customer_ip'               => $order->get_customer_ip_address(),
			'customer_user_agent'       => $order->get_customer_user_agent(),
			'customer_id'               => $order->get_user_id(),
			'view_order_url'            => $order->get_view_order_url(),
			'line_items'                => array(),
			'shipping_lines'            => array(),
			'tax_lines'                 => array(),
			'fee_lines'                 => array(),
			'coupon_lines'              => array(),
		);
		$order_data = array_merge($order_data, $this->get_format_currency_options($order->get_currency()));

		// Add line items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product    = $item->get_product();

			$hideprefix = ( isset( $filter['all_item_meta'] ) && 'true' === $filter['all_item_meta'] ) ? null : '_';
			$item_meta  = $item->get_all_formatted_meta_data( $hideprefix );

			foreach ( $item_meta as $key => $values ) {
				$item_meta[ $key ]->label = $values->display_key;
				unset( $item_meta[ $key ]->display_key );
				unset( $item_meta[ $key ]->display_value );
			}
			
			//we add the currency format data here for compatibility with storeAPI
			$line_item = array_merge( 
				array(
					'id'           => $item_id,
					'subtotal'     => $this::format( $order->get_line_subtotal( $item, false, false ), $money_format_options ),
					'subtotal_tax' => $this::format( $item->get_subtotal_tax(), $money_format_options ),
					'total'        => $this::format( $order->get_line_total( $item, true, false ), $money_format_options ),
					'total_tax'    => $this::format( $item->get_total_tax(), $money_format_options ),
					'price'        => $this::format( $order->get_item_total( $item, false, false ), $money_format_options ),
					'quantity'     => $item->get_quantity(),
					'tax_class'    => $item->get_tax_class(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
					'sku'          => is_object( $product ) ? $product->get_sku() : null,
					'meta'         => array_values( $item_meta ),
					'currency_code' => $order->get_currency(),
					'parent_id'    => method_exists($product, 'get_parent_id') ? $product->get_parent_id() : 0,
					'product_type' => method_exists($product, 'get_type') ? $product->get_type() : "",
				), 
				$this->get_format_currency_options($order->get_currency())
			);

			if ( in_array( 'products', $expand ) && is_object( $product ) ) {
				$_product_data = WC()->api->WC_API_Products->get_product( $product->get_id() );

				if ( isset( $_product_data['product'] ) ) {
					$line_item['product_data'] = $_product_data['product'];
				}
			}

			$order_data['line_items'][] = $line_item;
		}

		// Add shipping.
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$order_data['shipping_lines'][] = array_merge( 
				array(
					'id'           => $shipping_item_id,
					'method_id'    => $shipping_item->get_method_id(),
					'method_title' => $shipping_item->get_name(),
					'total'        => $this::format( $shipping_item->get_total(), $money_format_options ),
					'currency_code'             => $order->get_currency()
				), 
				$this->get_format_currency_options($order->get_currency())
			);
		}

		// Add taxes.
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {
			$tax_line = array_merge(
				array(
					'id'       => $tax->id,
					'rate_id'  => $tax->rate_id,
					'code'     => $tax_code,
					'title'    => $tax->label,
					'total'    => $this::format( $tax->amount, $money_format_options ),
					'compound' => (bool) $tax->is_compound,
					'currency_code'             => $order->get_currency()
				), 
				$this->get_format_currency_options($order->get_currency())
			);

			if ( in_array( 'taxes', $expand ) ) {
				$_rate_data = WC()->api->WC_API_Taxes->get_tax( $tax->rate_id );

				if ( isset( $_rate_data['tax'] ) ) {
					$tax_line['rate_data'] = $_rate_data['tax'];
				}
			}

			$order_data['tax_lines'][] = $tax_line;
		}

		// Add fees.
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$order_data['fee_lines'][] = array_merge(
				array(
					'id'        => $fee_item_id,
					'title'     => $fee_item->get_name(),
					'tax_class' => $fee_item->get_tax_class(),
					'total'     => $this::format( $order->get_line_total( $fee_item ), $money_format_options ),
					'total_tax' => $this::format( $order->get_line_tax( $fee_item ), $money_format_options ),
					'currency_code'             => $order->get_currency()
				), 
				$this->get_format_currency_options($order->get_currency())
			);
		}

		// Add coupons.
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
			$coupon_line = array_merge(
				array(
					'id'     => $coupon_item_id,
					'code'   => $coupon_item->get_code(),
					'amount' => $this::format( $coupon_item->get_discount(), $money_format_options ),
					'currency_code'             => $order->get_currency()
				), 
				$this->get_format_currency_options($order->get_currency())
			);

			if ( in_array( 'coupons', $expand ) ) {
				$_coupon_data = WC()->api->WC_API_Coupons->get_coupon_by_code( $coupon_item->get_code() );

				if ( ! is_wp_error( $_coupon_data ) && isset( $_coupon_data['coupon'] ) ) {
					$coupon_line['coupon_data'] = $_coupon_data['coupon'];
				}
			}

			$order_data['coupon_lines'][] = $coupon_line;
		}
		
		$server = null;
		return array( 'order' => apply_filters( 'woocommerce_api_order_response', $order_data, $order, $fields, $server) );
	}


	/**
	 * Get a list of valid order statuses
	 *
	 * Note this requires no specific permissions other than being an authenticated
	 * API user. Order statuses (particularly custom statuses) could be considered
	 * private information which is why it's not in the API index.
	 *
	 * @since 2.1
	 * @return array
	 */
	public function get_order_statuses() {

		$order_statuses = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {
			$order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
		}

		return array( 'order_statuses' => apply_filters( 'woocommerce_api_order_statuses_response', $order_statuses, $this ) );
	}


	/**
	 * Helper method to get order post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	protected function query_orders( $args ) {
		$post_type = 'shop_order';

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => $post_type,
			'post_status' => array_keys( wc_get_order_statuses() ),
		);

		// add status argument
		if ( ! empty( $args['status'] ) ) {
			$statuses                  = 'wc-' . str_replace( ',', ',wc-', $args['status'] );
			$statuses                  = explode( ',', $statuses );
			$query_args['post_status'] = $statuses;

			unset( $args['status'] );
		}

		//we always return current user orders
		$query_args['meta_query'] = array(
			array(
				'key'     => '_customer_user',
				'value'   => get_current_user_id(),
				'compare' => '=',
			),
		);

		$query_args = $this->merge_query_args( $query_args, $args );
		
		return new WP_Query( $query_args );
	}



	/**
	 * Add common request arguments to argument list before WP_Query is run
	 *
	 * @since 2.1
	 * @param array $base_args required arguments for the query (e.g. `post_type`, etc)
	 * @param array $request_args arguments provided in the request
	 * @return array
	 */
	protected function merge_query_args( $base_args, $request_args ) {

		$args = array();

		// search
		if ( ! empty( $request_args['q'] ) ) {
			$args['s'] = $request_args['q'];
		}

		// resources per response
		if ( ! empty( $request_args['limit'] ) ) {
			$args['posts_per_page'] = $request_args['limit'];
		}

		// resource offset
		if ( ! empty( $request_args['offset'] ) ) {
			$args['offset'] = $request_args['offset'];
		}

		// order (ASC or DESC, ASC by default)
		if ( ! empty( $request_args['order'] ) ) {
			$args['order'] = $request_args['order'];
		}

		// orderby
		if ( ! empty( $request_args['orderby'] ) ) {
			$args['orderby'] = $request_args['orderby'];

			// allow sorting by meta value
			if ( ! empty( $request_args['orderby_meta_key'] ) ) {
				$args['meta_key'] = $request_args['orderby_meta_key'];
			}
		}

		// allow post status change
		if ( ! empty( $request_args['post_status'] ) ) {
			$args['post_status'] = $request_args['post_status'];
			unset( $request_args['post_status'] );
		}

		// resource page
		$args['paged'] = ( isset( $request_args['page'] ) ) ? absint( $request_args['page'] ) : 1;

		$args = apply_filters( 'woocommerce_api_query_args', $args, $request_args );

		return array_merge( $base_args, $args );
	}





	/**
	 * SHIPPING API
	 */

	public function shipping_allowed_countries() {
		try {

			$countryAPI = new WC_Countries();
			$countries = $countryAPI->get_shipping_countries();
			//make the response coherent. If the list of countries is empty, return an empty json object, not an empty array
			if (is_array($countries) && empty($countries)) {
				$countries = new stdClass();
			}
			
			$states = $countryAPI->get_shipping_country_states();
			//make the response coherent. If the list of states is empty, return an empty json object, not an empty array
			foreach ($states as $code => $state) {
				if (is_array($state) && empty($state)) {
					$states[$code] = new stdClass();
				}
			}
			if (is_array($states) && empty($states)) {
				$states = new stdClass();
			}

			$locale = $countryAPI->get_country_locale();
			/**
			 * make the response coherent. If a country property is empty, return an empty json object, not an empty array
			 * Properties: city, state, postcode, address_1, address_2
			*/
			foreach ($locale as $code => $country) {
				foreach ($country as $key => $value) {
					if (is_array($country[$key]) && empty($value)) {
						$locale[$code][$key] = new stdClass();
					}
					
					//fantom values showing up such as "0": "first_name" .. we don't need them in the app
					if ($code == 'default' && is_string($value)) {
					    unset($locale[$code][$key]);
					}
				}
			}

			$response = ["locale_address_rules" => $locale, "allowed_shipping_countries" => $countries, "allowed_shipping_states" => $states];
			return $response;
			
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}


	/**
	 * Taxonomies and terms API
	 */
	public function filter_taxonomies() {
		$taxonomy_labels = wc_get_attribute_taxonomy_labels();
		
		foreach($taxonomy_labels as $label) {
			$taxonomies_with_terms[wc_attribute_taxonomy_name($label)] = [];
		}
		$terms = get_terms(array(
			'taxonomy'   => array_keys($taxonomies_with_terms),
			'hide_empty' => false,
		));
		foreach ($terms as $term) {
			$taxonomies_with_terms[$term->taxonomy][] = $term;
		}

		return $taxonomies_with_terms;
	}

	/**
	 * Returns all available coupons for the user
	 */
	public function available_coupons($request = null) {

		$parameters = $request->get_json_params();
		$email = sanitize_text_field($parameters['email']);
		
		if (!wc_coupons_enabled()) return [];

		$coupon_posts = get_posts( array(
			'posts_per_page'   => -1,
			'orderby'          => 'name',
			'order'            => 'asc',
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
			'meta_query' => array(
				array(
				  'key' => 'allow_for_mobile',
				  'value' => 'yes',
				  'compare' => '='
				)
			)
		) );
		
		$coupons = [];
		foreach ($coupon_posts as $coupon_post) {
			
			$coupon = new WC_Coupon( $coupon_post->post_title );
			
			//was a pain to filter out by expiry date in sql using meta_query
			$expiry = $coupon->expiry_date;
			if ($expiry != '' && $expiry < date("Y-m-d H:i:s")) continue;

			
			$available_for_user = $this->is_coupon_available_for_emails($coupon, $email);
			
			if (!$available_for_user) continue;

			// Get the decimal precession.
			$dp =  wc_get_price_decimals();
			$money_format_options = ['decimals' => $dp];
			$currency = get_woocommerce_currency(); //get_woocommerce_currency();

			$coupon_data = array(
				'id'                           => $coupon->id,
				'code'                         => $coupon->code,
				'amount'                       => $this::format( $coupon->amount, $money_format_options ),
				'type'                         => $coupon->type,
				'expiry_date'                  => $coupon->expiry_date,
				'apply_before_tax'             => $coupon->apply_before_tax(),
				'enable_free_shipping'         => $coupon->enable_free_shipping(),
				'minimum_amount'               => $this::format( $coupon->minimum_amount, $money_format_options ),
				'description'                  => $coupon_post->post_excerpt,
				// 'created_at'                   => $coupon_post->post_date_gmt,
				// 'updated_at'                   => $coupon_post->post_modified_gmt,
				// 'individual_use'               => ( 'yes' === $coupon->individual_use ),
				// 'product_ids'                  => array_map( 'absint', (array) $coupon->product_ids ),
				// 'exclude_product_ids'          => array_map( 'absint', (array) $coupon->exclude_product_ids ),
				// 'usage_limit'                  => ( ! empty( $coupon->usage_limit ) ) ? $coupon->usage_limit : null,
				// 'usage_limit_per_user'         => ( ! empty( $coupon->usage_limit_per_user ) ) ? $coupon->usage_limit_per_user : null,
				// 'limit_usage_to_x_items'       => (int) $coupon->limit_usage_to_x_items,
				// 'usage_count'                  => (int) $coupon->usage_count,
				// 'product_category_ids'         => array_map( 'absint', (array) $coupon->product_categories ),
				// 'exclude_product_category_ids' => array_map( 'absint', (array) $coupon->exclude_product_categories ),
				// 'exclude_sale_items'           => $coupon->exclude_sale_items(),
				// 'customer_emails'              => $coupon->customer_email,
				'currency_code'                 => $currency,
				'formatting_options'            => $this->get_format_currency_options($currency)
			);
			$coupons[]= $coupon_data;
		}

		
		return $coupons;
	}

	private function is_coupon_available_for_emails($coupon, $billing_email) {
		// Get user and posted emails to compare.
		$current_user  = wp_get_current_user();
		$check_emails  = array_unique(
			array_filter(
				array_map(
					'strtolower',
					array_map(
						'sanitize_email',
						array(
							$billing_email,
							$current_user->user_email,
						)
					)
				)
			)
		);
		//no emails to work with
		if (count($check_emails) == 0) return true;

		// Limit to defined email addresses.
		$restrictions = $coupon->get_email_restrictions();
		
		if ( is_array( $restrictions ) && 0 < count( $restrictions ) && ! $this->is_coupon_emails_allowed( $check_emails, $restrictions ) ) {
			return false;
		}
		
		$coupon_usage_limit = $coupon->get_usage_limit_per_user();

		if ( 0 < $coupon_usage_limit && 0 === get_current_user_id() ) {
			// For guest, usage per user has not been enforced yet. Enforce it now.
			$coupon_data_store = $coupon->get_data_store();
			$billing_email     = strtolower( sanitize_email( $billing_email ) );
			if ( $coupon_data_store && $coupon_data_store->get_usage_by_email( $coupon, $billing_email ) >= $coupon_usage_limit ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Taken from WC_Cart
	 * 
	 * Checks if the given email address(es) matches the ones specified on the coupon.
	 *
	 * @param array $check_emails Array of customer email addresses.
	 * @param array $restrictions Array of allowed email addresses.
	 * @return bool
	 */
	private function is_coupon_emails_allowed( $check_emails, $restrictions ) {

		foreach ( $check_emails as $check_email ) {
			// With a direct match we return true.
			if ( in_array( $check_email, $restrictions, true ) ) {
				return true;
			}

			// Go through the allowed emails and return true if the email matches a wildcard.
			foreach ( $restrictions as $restriction ) {
				// Convert to PHP-regex syntax.
				$regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
				preg_match( $regex, $check_email, $match );
				if ( ! empty( $match ) ) {
					return true;
				}
			}
		}

		// No matches, this one isn't allowed.
		return false;
	}
	
	
}
