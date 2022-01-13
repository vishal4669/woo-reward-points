<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://webbycrown.com
 * @since      1.0.0
 *
 * @package    Webbycrown
 * @subpackage Webbycrown/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Webbycrown
 * @subpackage Webbycrown/admin
 * @author     Webbycrown <webbycrown@gmail.com>
 */
class Webbycrown_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('add_meta_boxes_product', array($this, 'post_add_product_meta_boxes') );
		add_action('save_post', array($this, 'product_save_meta_box_data') );
		add_action( 'woocommerce_product_write_panel_tabs', array($this, 'my_custom_tab_action') );
		add_action( 'woocommerce_product_data_panels', array($this,  'custom_tab_panel' ));

		add_action( "wp_ajax_wc_woo_reward_point", array( $this,"wc_woo_reward_point" ),10,6 );
		add_action( "wp_ajax_nopriv_wc_woo_reward_point", array( $this,"wc_woo_reward_point" ),10,6 );

		add_action('admin_menu', array( $this,"register_my_custom_rewrd_point" ),99);


		add_action( "wp_ajax_wc_woo_reward_point_form", array( $this,"wc_woo_reward_point_form" ),110);
		add_action( "wp_ajax_nopriv_wc_woo_reward_point_form", array( $this,"wc_woo_reward_point_form" ),111);

		add_filter('woocommerce_is_purchasable', array( $this , 'wc_my_woocommerce_is_purchasable'), 10, 2);

		add_action( "init", array( $this,"wc_cart_total_reward_point_call_back_new" ),150);

		add_action('woocommerce_thankyou', array( $this, 'wc_reward_point_minus'), 10, 1);
		add_action( 'woocommerce_single_product_summary', array( $this, 'custom_field_display_below_title' ), 11 );

		/*My Account Page New Tabs : Reword History */
		add_action( 'init', array( $this, 'wc_althemist_add_premium_support_endpoint') );
		add_filter( 'query_vars', array( $this, 'wc_althemist_premium_support_query_vars'), 0 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_althemist_add_premium_support_link_my_account') );
		add_action( 'woocommerce_account_reward-history_endpoint', array( $this, 'wc_althemist_premium_support_content') );

		// Product Page Maximum Quantity
		add_filter( 'woocommerce_quantity_input_max', array( $this, 'wc_woocommerce_quantity_input_max_callback'), 10, 2 );

		// Product Page min & Maximum Quantity Validation
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'wao_wc_qty_add_to_cart_validation' ), 1, 5 );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Webbycrown_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Webbycrown_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webbycrown-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Webbycrown_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Webbycrown_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webbycrown-admin.js', array( 'jquery' ), $this->version, false );
		//wp_localize_script( $this->plugin_name, 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

	}

	/**
	 * Product Meta Box Start.
	 *
	 * @since    1.0.0
	 */
	public function product_change_data_meta_box( $post ){

		wp_nonce_field( basename( __FILE__ ), 'product_change_data_meta_box_nonce' );

		$woo_reward_point = get_post_meta( $post->ID, '_woo_woo_reward_point', true );

		?>
		<div class='inside options_group'>

			<p class="form-field">
				<label><?php _e( 'Woo Reward Point', 'woo_reward_plugin' ); ?></label>
				<input class="min-point" min="1" type="number" name="woo_reward_point" value="<?php echo $woo_reward_point; ?>" /> 
			</p>

		</div>
		<?php
	}

	public function product_save_meta_box_data( $post_id ){

		if ( !isset( $_POST['product_change_data_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['product_change_data_meta_box_nonce'], basename( __FILE__ ) ) ){
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ){
			return;
		}

		if ( isset( $_REQUEST['woo_reward_point'] ) ) {
			update_post_meta( $post_id, '_woo_woo_reward_point', sanitize_text_field( $_POST['woo_reward_point'] ) );
		}

	} 

	/**
	 * Product Adding a Advance Options tab.
	 *
	 * @since    1.0.0
	 */

	public function my_custom_tab_action() {
		?>
		<li class="custom_tab">
			<a href="#the_custom_panel">
				<span><?php _e( 'Woocommerce Reward Points', 'textdomain' ); ?></span>
			</a>
		</li>
		<?php
	}

	public function custom_tab_panel() {
		global $post;
		?>
		<div id="the_custom_panel" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php  
				$this->product_change_data_meta_box( $post );
				?>
			</div>
		</div>
		<?php
	}


	public function wc_woo_reward_point( $user_id = 0, $notes = '', $amount = '', $type = '', $date ='', $status = 0 ){
		global $wpdb;     
		$reward_point = 0;
		$reward_point = get_user_meta( $user_id, 'r_point', true );
		$total_rwd_point  =  0;

		if($type == 'credit') {

			$total_rwd_point =  (float)$reward_point + (float)$amount;

		} else {

			$total_rwd_point =  (float)$reward_point - (float)$amount;
		}

		update_user_meta( $user_id, 'r_point', $total_rwd_point );
		$table_name = $wpdb->prefix . 'woo_reward_point';     		    	
		$inser_data = array(
			'user_id' => $user_id,
			'note' => $notes,
			'amount' => $amount,
			'type' => $type,
			'date_time' => $date,
			'status' => $status,
			'balace'=>$total_rwd_point
		);

		$wpdb->insert($table_name, $inser_data);
		$reward_id = $wpdb->insert_id;
		
	}


	/**
	 * Product Adding a Woocommerece Sub tabs.
	 *
	 * @since    1.0.0
	 */

	public function register_my_custom_rewrd_point() {
		add_submenu_page( 'woocommerce', 'My Custom Submenu Page', 'My Woo Reward Point', 'manage_options', 'my-custom-submenu-page', array( $this, 'my_custom_rewrd_point_callback' ) ); 
	}
	public function my_custom_rewrd_point_callback() {
		echo '<h3>Reward Point</h3>'; ?>

		<style>
			form#reward_point_form input[type="number"], form#reward_point_form select, form#reward_point_form textarea {
				width: 350px;
			}
		</style>
		<form class="reward_point_form" id="reward_point_form" action="" method="post">
			<table id="table1"; cellspacing="5px" cellpadding="5%"; align="">  

				<tr>
					<td><label for="user_name"><b>Select User:</b></label></td>  
					<td>
						<select  id="user_id" required name="user_id" >
							<option value="">Select User</option>
							<?php 

							$blogusers = get_users();

							foreach ( $blogusers as $user ) {
								echo '<option value="' . esc_html( $user->ID ) . '" >' . esc_html( $user->display_name ) . '</option>';
							}
							?></select> 

						</td>  
					</tr>
					<tr>
						<td><label for="note"><b>Note:</b></label></td>
						<td><textarea  id="note" name="note" value="note" ></textarea>
						</td>
					</tr>
					<tr>
						<td><label for="type"><b>Choose a Type:</b></label></td>
						<td><select id="type" name="type"> 
							<option value="credit">Credit</option>
							<option value="debit">Debit</option>
						</select></td>
					</tr>

					<tr>
						<td><label for="point"><b>Reward Point:</b></label></td>
						<td><input type="number" id="point" required name="point" value="Reward point"></td>
					</tr>
					<tr>
						<td><input type="submit" class="button-primary" value="Submit">
							<input type="hidden" name="action"  value="wc_woo_reward_point_form">
						</td>
					</tr>
				</table>
			</form> 

			<script>
				jQuery(document).ready( function(){
					jQuery(document).on('submit','#reward_point_form',function(){
						jQuery.ajax({
							type : "POST",
							dataType : "json",
							url : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							data: jQuery('#reward_point_form').serialize(),
							success: function(data){
								var type = jQuery('#type').val();
								if ( type == 'debit' ) {
									type = "Debit";
								} else {
									type = "Credit";
								}
								document.getElementById("reward_point_form").reset();							
								jQuery('#reward_point_form').before('<div class="notice inline message done notice-success  is-dismissible " style=""><p>Reword point '+type+' successfully!</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button></div>');
								setTimeout(function(){
									jQuery('.message.done').remove();
								},2000);
							//window.location.reload(true);
							//return false;
						}
					});
					//window.location.reload(true);
					//jQuery('#reward_point_form')[0].reset();
					return  false;
				});
					return  false;
				});
			</script>

		<?php }

		public function wc_woo_reward_point_form(){

			$status = 0;
			$date = date("Y-m-d H:i:s");
			$amount=  $_POST['point'];
			$user_id = $_POST['user_id'];
			$notes = $_POST['note'];
			$type = $_POST['type'];

			$this->wc_woo_reward_point( $user_id, $notes, $amount, $type, $date, $status );

			

			wp_send_json(array('DONE'));
		}

		public function wc_my_woocommerce_is_purchasable($is_purchasable, $product) {
			$product_id = $product->get_id();
			$user_id = get_current_user_id();
			$reward_point = get_user_meta( $user_id, 'r_point', true );
			$woo_reward_point = get_post_meta( $product_id, '_woo_woo_reward_point', true );
			$cart_reward_point = $this->wc_cart_total_reward_point_call_back();
			
			if ( $reward_point && $cart_reward_point ) {
				$reward_point = $reward_point - $cart_reward_point;
			}
			if ( empty($reward_point) ){
				return false;
			}
			if($woo_reward_point >= $reward_point){

				return ($product->id == $product_id  ? false : $is_purchasable);	

			} else {

				return $is_purchasable;

			}
		}

		public function wc_cart_total_reward_point_call_back_new() {
			if (isset($_GET['test']) && !empty($_GET['test'])) {
				return $this->wc_cart_total_reward_point_call_back();
			}
		}


		public function wc_cart_total_reward_point_call_back() {
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();

			$cart_tottal = 0;
			if ( $items ) {
				foreach($items as $item => $values) { 
					
					$cart_reward_point = 0;
					$cart_reward_point = get_post_meta( $values['product_id'], '_woo_woo_reward_point', true );
					$cart_reward_point = $values['quantity'] * $cart_reward_point;
					$cart_tottal = (float)$cart_tottal + (float)$cart_reward_point;					
				}
			}
			return $cart_tottal;
		}


		
		public function wc_reward_point_minus( $order_id ) {
			if ( ! $order_id )
				return;
			
			$user_id = get_current_user_id();
			$reward_point = 0;
			
			if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {
				$order = wc_get_order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					$product_id = $item->get_product_id();	
					$item_data = $item->get_data();				
					$woo_reward_point = get_post_meta( $product_id, '_woo_woo_reward_point', true );
					$reward_point = $reward_point + ($woo_reward_point* $item_data['quantity']);				
				}
				$status = 0;
				$date = date("Y-m-d H:i:s");
				$notes = '#'.$order_id.' used reward point!';
				$type = 'debit';
				$this->wc_woo_reward_point( $user_id, $notes, $reward_point, $type, $date, $status );
			}
			
		}


/*
 * Changing the maximum quantity to 5 for all the WooCommerce products
 */

function wc_woocommerce_quantity_input_max_callback( $max, $product ) {
	$product_id = $product->get_id();
	$user_id = get_current_user_id();
	$reward_point = get_user_meta( $user_id, 'r_point', true );
	$cart_reward_point = $this->wc_cart_total_reward_point_call_back();
	if ( $reward_point && $cart_reward_point ) {
		$reward_point = $reward_point - $cart_reward_point;
	}
	$max = get_post_meta( $product_id, '_woo_woo_reward_point', true );
	$reward_point = $reward_point / $max;
		return floor($reward_point);

}

/* Validating the quantity on add to cart action with the quantity of the same product available in the cart. */

public function wao_wc_qty_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) {

	$user_id = get_current_user_id();
	$product_max = get_user_meta( $user_id, 'r_point', true );
	$cart_reward_point = $this->wc_cart_total_reward_point_call_back();

	if ( $product_max && $cart_reward_point ) {
		$product_max = $product_max - $cart_reward_point;
	}

	
	//$product_id = $product->get_id();
	//print_r($product_id);
	//$product_max = get_post_meta( $product_id, '_woo_woo_reward_point', true );
	//print_r($product_max);

	
	if ( ! empty( $product_max ) ) {
        // min is empty
		if ( false !== $product_max ) {
			$new_max = $product_max;
		} else {
            // neither max is set, so get out
			return $passed;
		}
	}

	$already_in_cart    = $this->wc_qty_get_cart_qty( $product_id );
	$product            = wc_get_product( $product_id );
	$product_title      = $product->get_title();
$product_max = get_post_meta( $product_id, '_woo_woo_reward_point', true );
	if ( !is_null( $new_max ) && !empty( $cart_reward_point ) ) {

		if ( ( $quantity * $product_max ) > $new_max ) {
            // oops. too much.
			$passed = false;            

			wc_add_notice( apply_filters( 'isa_wc_max_qty_error_message_already_had', sprintf( __( 'You can add a maximum of %1$s %2$s\'s to %3$s. You already have %4$s.', 'woocommerce-max-quantity' ), 
				$new_max,
				$product_title,
				'<a href="' . esc_url( wc_get_cart_url() ) . '">' . __( 'your cart', 'woocommerce-max-quantity' ) . '</a>',
				$cart_reward_point ),
			$new_max,
			$cart_reward_point ),
			'error' );

		}
	}

	return $passed;
}


/*
* Get the total quantity of the product available in the cart.
*/ 
public function wc_qty_get_cart_qty( $product_id ) {
	global $woocommerce;
    $running_qty = 0; // iniializing quantity to 0

    // search the cart for the product in and calculate quantity.
    foreach($woocommerce->cart->get_cart() as $other_cart_item_keys => $values ) {
    	//if ( $product_id == $values['product_id'] ) {   
    
					$cart_reward_point = get_post_meta( $values['product_id'], '_woo_woo_reward_point', true );
					$cart_reward_point = $values['quantity'] * $cart_reward_point;
					$running_qty = (float)$running_qty + (float)$cart_reward_point;	  
    	//}
    }

    return $running_qty;
}

public function wc_get_product_max_limit( $product_id ) {
	$qty = get_post_meta( $product_id, '_woo_woo_reward_point', true );
	if ( empty( $qty ) ) {
		$limit = false;
	} else {
		$limit = (int) $qty;
	}
	return $limit;
}


/**
 * tested with WooCommerce My Account Pgae New tabs
 */

// ------------------
// STEP 1. Add new endpoint to use on the My Account page
// IMPORTANT*: After uploading Permalinks needs to be rebuilt in order to avoid 404 error on the newly created endpoint

public function wc_althemist_add_premium_support_endpoint() {
	add_rewrite_endpoint( 'reward-history', EP_ROOT | EP_PAGES );
}

// ------------------
// 2. Add new query var

public function wc_althemist_premium_support_query_vars( $vars ) {
	$vars[] = 'reward-history';
	return $vars;
}

// ------------------
// 3. Insert the new endpoint into the My Account menu

public function wc_althemist_add_premium_support_link_my_account( $items ) {
	$items['reward-history'] = 'Reward History';
	return $items;
}

// ------------------
// 4. Add content to the new endpoint

public function wc_althemist_premium_support_content() {?> 
<style>/**
 * All of the CSS for your admin-specific functionality should be
 * included in this file.
 */

 .post_editor_list.ddd {
 	padding: 15px;
 }

 .navigation ul {
 	text-align: center;
 	/*text-align: end;*/
 	padding: 0;
 }

 .navigation li a,
 .navigation li a:hover,
 .navigation li.active a,
 .navigation li.disabled {
 	/*color: #fff;*/
 	font-weight: 600;
 	color: #2f4858;
 	text-decoration:none;
 }

 .navigation li {
 	display: inline;
 	margin: 2px;
 }

 .navigation li a,
 .navigation li a:hover,
 .navigation li.active a,
 .navigation li.disabled {
 	background-color: #efe5df;
 	border-radius: 5px;
 	cursor: pointer;
 	padding: 10px;
 	margin: 5px;
 }

 .navigation li a:hover,
 .navigation li.active a {
 	background-color: #d7bdae;
 	color: #fff;
 	/*border: 1px solid #000; */
 }
</style>
<h3 style="text-align: center;">Reward History  <span><b>Balance:</b> <?php $user_id = get_current_user_id();
echo get_user_meta( $user_id, 'r_point', true ); ?></span></h3>

<table id="woo_reward_point_table" width="100%">
	<thead style="text-align: center;">
		<th>#ID</th>
		<th>Note</th>
		<th>Amount</th>
		<th>Type</th>
		<th>Balance</th>
		<th>Date Time</th>
	</thead>
	<tbody>

		<?php
		global $wpdb,$wp;
		$user_id = get_current_user_id();
		$paged = 1;
		$wp = (array)$wp;
if ( isset($wp['query_vars']) ){
	if ( isset($wp['query_vars']['reward-history']) ){
		if ( !empty($wp['query_vars']['reward-history']) ){
	$paged = $wp['query_vars']['reward-history'];
}
}
}
		$per_page = 5;
		$spaged = ( ($paged - 1 )*$per_page);	
//echo "SELECT * FROM {$wpdb->prefix}woo_reward_point WHERE user_id = {$user_id} ORDER BY id DESC LIMIT {$per_page} OFFSET {$spaged}";
		$post_id = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woo_reward_point WHERE user_id = {$user_id} ORDER BY id DESC LIMIT {$per_page} OFFSET {$spaged}");

		if ($post_id) {
			foreach($post_id as $key => $data){ ?>

				<tr>
					<td><?php echo $data->id; ?></td>
					<td><?php echo $data->note; ?></td>
					<td><?php echo $data->amount; ?></td>
					<td><?php echo $data->type; ?></td>
					<td><?php echo $data->balace; ?></td>
					<td><?php $date=date_create($data->date_time);
					echo date_format($date,"Y/m/d H:i:s"); ?></td>
				</tr>

				<?php
			}
		} else {
			?>
			<tr>
				<td colspan="6" >No History Found!</td>

			</tr>
			<?php
		}


		?>

	</tbody>
</table>
<div class="post_editor_list ddd">
	<?php 
	$post_id = $wpdb->get_results("SELECT count(*) as sum FROM {$wpdb->prefix}woo_reward_point WHERE user_id = {$user_id}");
	if ( $post_id[0]->sum > $per_page ) {
		$max_num_pages = $post_id[0]->sum/$per_page; 

		$max   = intval( $max_num_pages );

		/* Add current page to the array */
		if ( $paged >= 1 )
			$links[] = $paged;

		/* Add the pages around the current page to the array */
		if ( $paged >= 3 ) {
			$links[] = $paged - 1;
			$links[] = $paged - 2;
		}

		if ( ( $paged + 2 ) <= $max ) {
			$links[] = $paged + 2;
			$links[] = $paged + 1;
		}

		echo '<div class="navigation"><ul>' . "\n";



		/* Link to first page, plus ellipses if necessary */
		if ( ! in_array( 1, $links ) ) {
			$class = 1 == $paged ? ' class="active"' : '';

			printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( str_replace('/page','',site_url( 'my-account/reward-history' )) ), '1' );

			if ( ! in_array( 2, $links ) )
				echo '<li>…</li>';
		}

		/* Link to current page, plus 2 pages in either direction if necessary */
		sort( $links );
		foreach ( (array) $links as $link ) {

			$class = $paged == $link ? ' class="active"' : '';
			printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( site_url( 'my-account/reward-history/'.$link )  ), $link );
		}

		/* Link to last page, plus ellipses if necessary */
		if ( ! in_array( $max, $links ) ) {
			if ( ! in_array( $max - 1, $links ) )
				echo '<li>…</li>' . "\n";

			$class = $paged == $max ? ' class="active"' : '';
			printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( site_url( 'my-account/reward-history/'.$max )  ), $max );
		}


		echo '</ul></div>' . "\n";
	}
	?>
</div>


<?php }
public function custom_field_display_below_title(){
	global $product;
	$user_id = get_current_user_id();
	
    // Get the custom field value
	$custom_field = get_post_meta( $product->get_id(), '_woo_woo_reward_point', true );
	$reward_point = get_user_meta( $user_id, 'r_point', true );

    // Display
	if( ! empty($custom_field) ){
		echo '<p class="my-custom-field">Product Reward Points: '.$custom_field.'</p>';
	}

	if( ! empty($reward_point) ){
		echo '<p class="my-custom-field">My Reward Balance: '.$reward_point.'</p>';
	}

}


}