<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://omnishopapp.com
 * @since      1.0.0
 *
 * @package    Omnishop
 * @subpackage Omnishop/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Omnishop
 * @subpackage Omnishop/admin
 * @author     Dusan <dusan@omnishopapp.com>
 */
class Omnishop_Admin {

	private $plugin_name;

	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/omnishop-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// WordPress media uploader scripts
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script( 'jquery-ui-droppable' );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/omnishop-admin.js', array( 'jquery' ), $this->version, true );

	}

	public function add_admin_pages() {
		add_menu_page('Omnishop Settings', 'Omnishop', 'manage_options', 'omnishop_plugin', [$this, 'admin_index'], 'dashicons-smartphone', 100);
		add_submenu_page('omnishop_plugin', 'Omnishop Settings', 'Settings', 'manage_options', 'omnishop_plugin', [$this, 'admin_index'], 0);
		
		//Link to existing taxonomy infrastructure instead of making our own
		$permalink = admin_url( 'edit-tags.php' ).'?taxonomy=omnishop_banner';
		add_submenu_page('omnishop_plugin', 'Homepage Banners', 'Homepage Banners Taxonomy', 'manage_options', $permalink, NULL, 1);
		
		$this->setup_custom_fields();
	}

	public function admin_index() {
		require plugin_dir_path( __FILE__ ) . 'partials/omnishop-admin-display.php';
	}


	public function add_orders_column($columns) {
		$reordered_columns = array();

		// Inserting columns to a specific location
		foreach( $columns as $key => $column){
			$reordered_columns[$key] = $column;
			if( $key ==  'order_status' ){ // Inserting after "Status" column
				$reordered_columns['is-omnishop'] = __( 'Mobile purchase', 'omnishop');
			}
		}
		return $reordered_columns;
	}
	
	public function add_orders_column_values( $column, $post_id ) {
		if ($column == 'is-omnishop') {
            // Get custom post meta data
            $is_mobile_purchase = get_post_meta( $post_id, '_is_mobile_purchase', true );
            echo ($is_mobile_purchase == 'yes') ? 'Yes' : 'No';
		}
	}


	public function setup_custom_fields() {
		// Standard functions how to register a settings field:
		// 1. register_setting($option_group, $option_name, $args = []);
		// 2. add_settings_section($id, $title, $callback, $page);
		// 3. add_settings_field($id, $title, $callback, $page, $section = 'default', $args = []);

		register_setting('omnishop_plugin', 'homepage_sections', ['sanitize_callback' => function ($input){
			$sections_input = array_map('trim', explode(',', $input));
			if (is_array($sections_input)) {
				$allowed_values = array_keys(Omnishop::ALLOWED_HOME_SECTION_VALUES);
				$sections_verified = array_intersect($sections_input, $allowed_values);
				return implode(',', $sections_verified);
			}
			return '';
		}]);
		add_settings_section('omnishop_admin_index', 'Settings', function() {}, 'omnishop_plugin');
		
		add_settings_field(
			'homepage_sections', 
			'Homepage Sections',
			function() {
				echo '<div class="main-container">'.
						'<div class="drag-and-drop-div">'.
							'<div class="section-values-field">'.
								'<ul id="draggable">';

									foreach (Omnishop::ALLOWED_HOME_SECTION_VALUES as $section_value => $description) {
										echo '<li class="drag-clone background-li" value="' . $section_value . '">' .
										'<span class="value-description">' . $description . '</span>' .
										'<img class="section-img" src="' . plugins_url( 'images/sections/'.$section_value.'.png', __FILE__ ) .'" alt="' . $section_value . '">' .
										'</li>';
									}
				
				echo 			'</ul>'.
							'</div>'.

								'<div class="mockup-field">'.
									'<div class="mockup-image" style="background-image: url('. plugins_url( 'images/mockup.svg', __FILE__ ) .')">'.
										'<ul id="sortable">';

										$stored_section_values_array = explode(",", esc_attr( get_option('homepage_sections')));
										for ($i=0; $i < sizeof($stored_section_values_array); $i++) {
											foreach (Omnishop::ALLOWED_HOME_SECTION_VALUES as $section_value => $description) {
												if ($section_value == $stored_section_values_array[$i]) {
													echo '<li class="background-li verified-values" value="' . $stored_section_values_array[$i] . '">' .
													'<span class="value-description">' . $description . '</span>' .
													'<img class="section-img" src="' . plugins_url( 'images/sections/'.$stored_section_values_array[$i].'.png', __FILE__ ) .'" alt="' . $stored_section_values_array[$i] . '">' .
													'</li>';
												}
											}
										}
						
				echo 					'</ul>'.
										'<input type="hidden" name="homepage_sections" id="hidden-input" class="regular-text" value="' . esc_attr( get_option('homepage_sections')) . '">'.
									'</div>'.
									'<div class="remove-section-field">'.
									'<span>Drop section to remove it</span>'.
								'</div>'.
							'</div>'.
						'</div>'. 
					'</div>';
			},
			'omnishop_plugin',
			'omnishop_admin_index',
			['label_for' => 'homepage_sections', 'class' => 'example-class']
		);
	}

	public function register_taxonomy_banners() {

		$labels = array(
			'name'              	 => _x( 'Banners', 'taxonomy general name' ),
			'singular_name'     	 => _x( 'Banner', 'taxonomy singular name' ),
			'search_items'      	 => __( 'Search banners' ),
			'all_items'         	 => __( 'All banners' ),
			'edit_item'         	 => __( 'Edit Banner' ),
			'update_item'       	 => __( 'Update Banner' ),
			'add_new_item'      	 => __( 'Add New Banner' ),
			'new_item_name'     	 => __( 'New Banner Name' ),
			'menu_name'         	 => __( 'Banner' ),
		);
   
		$args   = array(
			'hierarchical'      	 => false,
			'labels'            	 => $labels,
			'show_ui'           	 => true,
			'publicly_queryablebool' => true,
			'show_in_nav_menus'		 => true,
			'query_var'         	 => true,
		);
		register_taxonomy( 'omnishop_banner', null, $args );
		
		add_filter( 'pre_insert_term', [$this, 'term_validate_slug_is_int'], 10, 3 );

		add_action( 'omnishop_banner_add_form_fields', [$this, 'add_term_fields'] );
		add_action( 'omnishop_banner_edit_form_fields', [$this, 'omnishop_banner_edit_term_fields'], 10, 2 );
		add_action( 'after-omnishop_banner-table', [$this, 'after_omnishop_banner_table'], 10, 1 );
		
		add_action( 'saved_omnishop_banner', [$this, 'omnishop_banner_save_term_fields'] );
		add_action( 'delete_omnishop_banner', [$this, 'omnishop_banner_delete_term_fields']);
		
		
		global $wp_taxonomies;
		$wp_taxonomies['omnishop_banner']->labels->desc_field_description = "Used as banner main text";
		$wp_taxonomies['omnishop_banner']->labels->slug_field_description = 'Used as position of the banner: 1, 2, ...';
		
		
		$this->add_category_fields();
	}

	public function term_validate_slug_is_int($term, $taxonomy, $args) {
		
		if ($taxonomy == 'omnishop_banner' && is_array($args) ) {
			$slug = $args['slug'];
			if (!is_numeric( $slug )) {
				$term = new WP_Error( 'slug_not_int', __( 'Please use a numeric value for Slug' ) );
			} else {
				$all_banners = get_terms(array(
					'taxonomy'   => 'omnishop_banner',
					'hide_empty' => false,
				));
				
				foreach ($all_banners as $banner) {
					if ($banner->slug == $slug) {
						$term = new WP_Error( 'slug_duplicate', __( "Your Slug value <strong>\"$slug\"</strong> already exists" ) );
					}
				}
			}
		}

		return $term;
	}

	public function add_category_fields() {
		add_action( 'product_cat_add_form_fields', [$this, 'add_product_cat_fields'], 10, 2 );
		add_action( 'product_cat_edit_form_fields', [$this, 'edit_product_cat_fields'], 10, 2);
		
		add_action( 'saved_product_cat', [$this, 'product_cat_save_term_fields'], 10);
		add_action( 'delete_product_cat', [$this, 'product_cat_delete_term_fields'], 10);
	}


	public function add_term_fields( $taxonomy ) {
		?>
			<div class="form-field">
				<label for="omnishop_banner_top_text">Banner top text</label>
				<input type="text" name="omnishop_banner_top_text" id="omnishop_banner_top_text" />
				
			</div>
			<div class="form-field">
				<label for="omnishop_banner_bottom_text">Banner bottom text</label>
				<input type="text" name="omnishop_banner_bottom_text" id="omnishop_banner_bottom_text" />
				
			</div>
			<div class="form-field">
				<label>Banner image</label>
				<a href="#" class="button omnishop_banner-upload">Upload image</a>
				<a href="#" class="omnishop_banner-remove" style="display:none">Remove image</a>
				<input type="hidden" name="omnishop_banner_img" value="">
				<p>
					Banner image should be 16:9 or square shape. Minimum width 900px so it is not pixelized on the phone.
				</p>
			</div>
			<div class="form-field">
				<label for="omnishop_banner_image_full_width">Banner image full width?</label>
				<input type="checkbox" value="1" name="omnishop_banner_image_full_width" id="omnishop_banner_image_full_width" />
				<p>Banner image covers the whole section or a half</p>
			</div>
			
			<div class="form-field">
				<label for="omnishop_banner_button_text">Banner action button text</label>
				<input type="text" name="omnishop_banner_button_text" id="omnishop_banner_button_text" />
				<p>Text on the action button on the banner</p>
			</div>
			<div class="form-field">
				<?php 
					$options = array_combine(Omnishop::ALLOWED_BANNER_ACTIONS, Omnishop::ALLOWED_BANNER_ACTIONS);
					echo $this->print_select_field('omnishop_banner_action', 'Banner action', ['' => '--'] + $options, '')
				?>
				<p>Action taken upon banner click</p>
			</div>
			<div class="form-field">
				<label for="omnishop_banner_action_param">Banner action param</label>
				<input type="text" name="omnishop_banner_action_param" id="omnishop_banner_action_param" />
				<p>Action parameter (category id, search term...)</p>
			</div>
			<div class="form-field">
				<label for="omnishop_banner_action_title">Banner action title</label>
				<input type="text" name="omnishop_banner_action_title" id="omnishop_banner_action_title" />
				<p>Action title (Category name, Tag name, Page name...)</p>
			</div>
			<div class="form-field">
				<label for="omnishop_banner_action_on_sale">Banner action "on sale"</label>
				<input type="checkbox" value="1" name="omnishop_banner_action_on_sale" id="omnishop_banner_action_on_sale" />
				<p>Products shown are marked "on sale"</p>
			</div>

		<?php
	}

	private function print_select_field($id, $label, $options, $selected_value) {
		
		$output = "<label for='$id'>$label</label>";
		$output .= "<select id='$id' name='$id'>";
		foreach ($options as $value) {
			$output .= "<option value='$value' " . ($value == $selected_value?'selected':'') . ">$value</option>";
		}
		$output .= "</select>";
		
		return $output;
	}


	public function after_omnishop_banner_table( $taxonomy ) {
		$example_banner_url = plugins_url( 'images/banners-explanation.jpg', __FILE__ );
		?>
			<p>&nbsp;</p>
			<h3>Banner preview example</h3>
			<p>
				<img src="<?php echo $example_banner_url ?>"  width="250" />
			<p>
		<?php
	}


	public function omnishop_banner_edit_term_fields( $term, $taxonomy ) {
		// get meta data value
		$image_id = get_term_meta( $term->term_id, 'omnishop_banner_img', true );
		$image_full_width = get_term_meta( $term->term_id, 'omnishop_banner_image_full_width', true );
		$top_text = get_term_meta( $term->term_id, 'omnishop_banner_top_text', true );
		$bottom_text = get_term_meta( $term->term_id, 'omnishop_banner_bottom_text', true );
		$button_text = get_term_meta( $term->term_id, 'omnishop_banner_button_text', true );
		$action = get_term_meta( $term->term_id, 'omnishop_banner_action', true );
		$action_param = get_term_meta( $term->term_id, 'omnishop_banner_action_param', true );
		$action_title = get_term_meta( $term->term_id, 'omnishop_banner_action_title', true );
		$action_on_sale = get_term_meta( $term->term_id, 'omnishop_banner_action_on_sale', true );

		?>
		
		<tr class="form-field">
			<th><label for="omnishop_banner_top_text">Banner top text</label></th>
			<td>
				<input name="omnishop_banner_top_text" id="omnishop_banner_top_text" type="text" value="<?php echo esc_attr( $top_text ) ?>" />
				
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="omnishop_banner_bottom_text">Banner bottom text</label></th>
			<td>
				<input name="omnishop_banner_bottom_text" id="omnishop_banner_bottom_text" type="text" value="<?php echo esc_attr( $bottom_text ) ?>" />
				
			</td>
		</tr>
		<tr class="form-field">
			<th>
				<label for="omnishop_banner_img">Banner image</label>
			</th>
			<td>
				<?php if( $image = wp_get_attachment_image_url( $image_id, 'thumbnail' ) ) : ?>
					<a href="#" class="omnishop_banner-upload">
						<img src="<?php echo esc_url( $image ) ?>" />
					</a>
					<a href="#" class="omnishop_banner-remove">Remove image</a>
					<input type="hidden" name="omnishop_banner_img" value="<?php echo absint( $image_id ) ?>">
					<?php else : ?>
						<a href="#" class="button omnishop_banner-upload">Upload image</a>
						<a href="#" class="omnishop_banner-remove" style="display:none">Remove image</a>
						<input type="hidden" name="omnishop_banner_img" value="">
						<?php endif; ?>
						
						<p>
							Banner image should be 16:9 or square shape. Minimum width 900px so it is not pixelized on the phone.
						</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="omnishop_banner_image_full_width">Banner image full width</label></th>
			<td>
				<input name="omnishop_banner_image_full_width" id="omnishop_banner_image_full_width" type="checkbox" value="1" <?php echo $image_full_width ? "checked":"" ?> />
				<p class="description">Banner image covers the whole section or a half</p>
			</td>
		</tr>
		
		<tr class="form-field">
			<th><label for="omnishop_banner_button_text">Banner action button text</label></th>
			<td>
				<input name="omnishop_banner_button_text" id="omnishop_banner_button_text" type="text" value="<?php echo esc_attr( $button_text ) ?>" />
				<p class="description">Text on the action button on the banner.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="omnishop_banner_action">Banner action</label></th>
			<td>
				<?php 
					$options = array_combine(Omnishop::ALLOWED_BANNER_ACTIONS, Omnishop::ALLOWED_BANNER_ACTIONS);
					echo $this->print_select_field('omnishop_banner_action', '', ['' => '--'] + $options, $action)
				?>
				<p class="description">Action taken upon banner click.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="omnishop_banner_action_param">Banner action parameter</label></th>
			<td>
				<input name="omnishop_banner_action_param" id="omnishop_banner_action_param" type="text" value="<?php echo esc_attr( $action_param ) ?>" />
				<p class="description">Action parameter (category id, search term, url...) </p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="omnishop_banner_action_title">Banner action title</label></th>
			<td>
				<input name="omnishop_banner_action_title" id="omnishop_banner_action_title" type="text" value="<?php echo esc_attr( $action_title ) ?>" />
				<p class="description">Action title (Category name, Tag name, Page name...) </p>
			</td>
		</tr>

		<tr class="form-field">
			<th><label for="omnishop_banner_action_on_sale">Banner action "on sale"</label></th>
			<td>
				<input name="omnishop_banner_action_on_sale" id="omnishop_banner_action_on_sale" type="checkbox" value="1" <?php echo $action_on_sale ? "checked":"" ?> />
				<p class="description">Products shown are marked "on sale"</p>
			</td>
		</tr>

		<?php
	}

	public function omnishop_banner_save_term_fields( $term_id ) {

		update_term_meta(
			$term_id,
			'omnishop_banner_image_full_width',
			absint( $_POST[ 'omnishop_banner_image_full_width' ] ) > 0 ? 1 : 0
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_top_text',
			sanitize_text_field( $_POST[ 'omnishop_banner_top_text' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_bottom_text',
			sanitize_text_field( $_POST[ 'omnishop_banner_bottom_text' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_img',
			absint( $_POST[ 'omnishop_banner_img' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_button_text',
			sanitize_text_field( $_POST[ 'omnishop_banner_button_text' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_action',
			sanitize_text_field( $_POST[ 'omnishop_banner_action' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_action_param',
			sanitize_text_field( $_POST[ 'omnishop_banner_action_param' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_action_title',
			sanitize_text_field( $_POST[ 'omnishop_banner_action_title' ] )
		);
		update_term_meta(
			$term_id,
			'omnishop_banner_action_on_sale',
			absint( $_POST[ 'omnishop_banner_action_on_sale' ] ) > 0 ? 1 : 0
		);
		
	}

	public function omnishop_banner_delete_term_fields($term_id) {
		
		delete_term_meta(
			$term_id,
			'omnishop_banner_image_full_width');
		delete_term_meta(
			$term_id,
			'omnishop_banner_button_text');
		delete_term_meta(
			$term_id,
			'omnishop_banner_top_text');
		delete_term_meta(
			$term_id,
			'omnishop_banner_bottom_text');
		delete_term_meta(
			$term_id,
			'omnishop_banner_img');

		delete_term_meta(
			$term_id,
			'omnishop_banner_action');
		delete_term_meta(
			$term_id,
			'omnishop_banner_action_param');
		delete_term_meta(
			$term_id,
			'omnishop_banner_action_title');
		delete_term_meta(
			$term_id,
			'omnishop_banner_action_on_sale');

	}


	/**
	 * Taxonomy - Product category fields
	 *
	 */

	public function add_product_cat_fields( $taxonomy ) {
		?>
			
			<div class="form-field">
				<label for="product_cat_fields_exclude_mobile">Exclude from mobile app</label>
				<input type="checkbox" value="1" name="product_cat_fields_exclude_mobile" id="product_cat_fields_exclude_mobile" />
				<p>[Omnishop] Exclude this category from listing in the mobile app</p>
			</div>

		<?php
	}

	public function edit_product_cat_fields( $term, $taxonomy ) {
		// get meta data value
		$exclude_mobile = get_term_meta( $term->term_id, 'product_cat_fields_exclude_mobile', true );
		
		?>
		
		
		<tr class="form-field">
			<th><label for="product_cat_fields_exclude_mobile">Exclude from mobile app</label></th>
			<td>
				<input name="product_cat_fields_exclude_mobile" id="product_cat_fields_exclude_mobile" type="checkbox" value="1" <?php echo $exclude_mobile ? "checked":"" ?> />
				<p class="description">[Omnishop] Exclude this category from listing in the mobile app</p>
			</td>
		</tr>
		
		<?php
	}

	public function product_cat_save_term_fields( $term_id ) {

		update_term_meta(
			$term_id,
			'product_cat_fields_exclude_mobile',
			absint( $_POST[ 'product_cat_fields_exclude_mobile' ] ) > 0 ? 1 : 0
		);
		
	}

	public function product_cat_delete_term_fields( $term_id) {
		
		delete_term_meta(
			$term_id,
			'product_cat_fields_exclude_mobile');
		
	}


	/**
	 * Coupons section
	 */

	// Add a custom field to Admin coupon settings pages
	public function add_coupon_allow_field() {
		woocommerce_wp_checkbox( array(
			'id'                => 'allow_for_mobile',
			'label'             => __( 'Allow for mobile app', 'omnishop' ),
			'description'       => __( 'Allow this coupon to be displayed to customers on your mobile app', 'omnishop' ),
			'desc_tip'    => true,

		) );
	}

	// Save the custom field value from Admin coupon settings pages
	public function save_coupon_allow_field( $post_id, $coupon ) {
		$allow_for_mobile = isset( $_POST['allow_for_mobile'] ) ? 'yes' : 'no';
   		update_post_meta( $post_id, 'allow_for_mobile', $allow_for_mobile );
	}

}
