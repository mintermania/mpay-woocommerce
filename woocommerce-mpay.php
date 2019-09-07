<?php
/*
   Plugin Name: MPay Payment Gateway For WooCommerce
   Description: Extends WooCommerce to Process Payments with MPay gateway.
   Version: 1.0.0
   Plugin URI: https://mpay.ms/
   Author: MPay
   Author URI: https://mpay.ms
   License: MIT
   Text Domain: mpay
   Domain Path: /languages
*/

add_action( 'plugins_loaded', 'true_load_plugin_textdomain' );
 
function true_load_plugin_textdomain() {
    load_plugin_textdomain( 'mpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'init_woocommerce_mpay_class' );

function init_woocommerce_mpay_class() {

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	class WC_Gateway_Wocommerce_mpay extends WC_Payment_Gateway {

	    /**
         * Whether or not logging is enabled
         *
         * @var bool
         */
        public static $log_enabled = false;

        /**
         * Logger instance
         *
         * @var WC_Logger
         */
        public static $log = false;

		/**
		 * Set if the place order button should be renamed on selection.
		 * @var string
		 */
		public $order_button_text;

		/**
		 * yes or no based on whether the method is enabled.
		 * @var string
		 */
		public $enabled;

		/**
		 * Payment method title for the frontend.
		 * @var string
		 */
		public $title;

		/**
		 * Payment method description for the frontend.
		 * @var string
		 */
		public $description;

		/**
		 * Gateway title.
		 * @var string
		 */
		public $method_title = '';

		/**
		 * Gateway description.
		 * @var string
		 */
		public $method_description = '';	

		/**
		 * Icon for the gateway.
		 * @var string
		 */
		public $icon;
	
		/**
		 * Unique Gateway ID.
		 * @var string
		 */
		public $id;
		
		/**
		 * MPay create order live url.
		 * @var string
		 */
		public $liveurl;


		/**
		 * Merchant secret key.
		 * @var string
		 */
		private $secretkey;

		/**
		 * Message to be displayed on successful transaction.
		 * @var string
		 */
		private $success_message;

		/**
		 * Message to be displayed on failed transaction.
		 * @var string
		 */
		private $failed_message;

		/**
		 * Call back url.
		 * @var string
		 */
		private $call_back_URL;

		/**
		 * Logo which the user see during create order.
		 * @var string
		*/
		private $logo;

		/**
		 * Slogan which the user see during create order.
		 * @var string
		*/
		private $slogan;

		/**
		 * User choose order statuses.
		 * @var array
		 */
		private $selectedStatuses = array();

		/**
		 * Enable/Disable order-description in create order page.
		 * @var string yes/no
		*/
		private $orderDescriptionStatus;

		/**
         * Constructor for the gateway.
         */
		public function __construct(){

			 // Setup general properties
			 $this->setup_properties();

			// Define fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Get settings.
			$this->settings();

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}

			add_action('woocommerce_cancel_mpay',array(&$this, 'cancel_page'));
		

            require_once plugin_dir_path( __FILE__ ) . '/includes/class-ipn-handler.php';
            new WC_Gateway_mpay_IPN_Handler( $this->selectedStatuses, $this->secretkey);

            require_once plugin_dir_path( __FILE__ ) . '/includes/class-back-link-handler.php';
            new WC_Gateway_mpay_Back_Link_Handler( array('success' => $this->success_message, 'failed' => $this->failed_message));

			global $woocommerce;
	   	}


		/**
		 * Setup general properties for the gateway.
		 */
    	protected function setup_properties() {
			$this->id                 = 'mpay';
			$this->method_title       = __('MPay Checkout', 'mpay');
			$this->method_description = __('MPay Checkout is a payment gateway plugin that allows you to take any Minter cryptocurrency via mpay.ms','mpay');
			$this->liveurl            = 'https://mpay.ms/api/payment/new';
		}

		/**
		 * Generate Title HTML.
		 *
		 * @param  mixed $key
		 * @param  mixed $data
		 * @since  1.0.0
		 * @return string
		 */
		public function generate_title_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title' => '',
				'class' => '',
				'description' => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			if($key !== 'call_back_URL') {
			?>
				</table>
				<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
				<?php if ( ! empty( $data['description'] ) ) : ?>
					<p><?php echo wp_kses_post( $data['description'] ); ?></p>
				<?php endif; ?>
				<table class="form-table">
				<hr>
			<?php
            }
            else { ?>
                <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo wp_kses_post($this->get_tooltip_html( $data )); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <?php echo wp_kses_post($this->get_description_html( $data )); ?>
                </fieldset>
            </td>
        </tr> <?php
            }
			return ob_get_clean();
		}

		/**
		 * Get gateway icon.
		 *
		 * @return string
		 */
		public function get_icon() {
			$icon_html = '';
			$icon      =  $this->get_icon_image();
			
				$icon_html .= '<img src="' . esc_attr( $icon ) . '" alt="' . esc_attr__( 'MPay', 'mpay' ) . '" />';

			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Get MPay images for a size.
		 *
		 * @return array of image URLs
		 */
		protected function get_icon_image(  ) {
			
				$icon = esc_url(WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/icons/icon_small.png');
			
			
			
			return apply_filters( 'woocommerce_mpay_icon', $icon );
		}

		/**
		 * Generate Image HTML.
		 *
		 * @param  mixed $key
		 * @param  mixed $data
		 * @since  1.5.0
		 * @return string
		 */
		


		/**
     	* Output the admin options table.
     	*/
		public function admin_options(){
			?>
			<div class="simplify-commerce-banner updated">
				<img  src="<?php echo esc_attr(WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo-admin.png'); ?>" />
				<p class="main">
					<h3>
						<?php esc_html_e( 'MPay Checkout', 'mpay' ); ?>
					</h3>
					<strong><?php esc_html_e( 'Getting started', 'mpay' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'mpay.ms is one of the most popular payment processors for Minter, MPay Checkout extension provides the most integrated checkout experience possible with WooCommerce to accept any Minter currency.', 'mpay' ); ?>
				</p>
				<p>
					<a href="<?php esc_html_e("https://mpay.ms/panel/register") ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Register in MPay', 'mpay' ); ?></a>
					<a href="<?php esc_html_e("https://mpay.ms") ?>" target="_blank" class="button"><?php esc_html_e( 'Learn more', 'mpay' ); ?></a>
				</p>
				<hr>
				<strong><?php esc_html_e("MPay Support", 'mpay'); ?></strong>

				<a href="<?php esc_html_e("https://t.me/mpayms_en") ?>" target="_blank"><?php esc_html_e("Our telegram channel and chat (en, ru)", 'mpay'); ?></a></p>
				<p><strong><?php esc_html_e("Version: ", 'mpay'); ?> 1.0.0</strong><p>

			</div>

			<table class="form-table">
				<?php esc_html($this->generate_settings_html()); ?>
			</table>
			<?php
		}

		/**
		 * Define user set variables.
		 */
		protected function settings(){

		    // Get current locale
			get_locale() == 'ka_GE' ? $locale = 'ka_GE' : $locale = 'en_US';

			'yes' === $this->settings['debug'] ? $this->debug = true : $this->debug = false;

			self::$log_enabled              = $this->debug;
			$this->title                    = __($this->settings['title'], 'mpay');
			$this->description              = __($this->settings['description'], 'mpay');
			$this->order_button_text  		= __($this->settings['button-title'], 'mpay');
			$this->secretkey  		        = $this->settings['secretkey'];
			$this->success_message          = $this->settings['success_message'];
			$this->failed_message           = $this->settings['failed_message'];
			$this->call_back_URL            = $this->settings['call_back_URL'];
			$this->logo                     = esc_url(WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/icons/logo_small.png');
			$this->slogan                   = $this->settings['slogan'];
            $this->orderDescriptionStatus   = $this->settings['order-description'];
            $this->orderQuantityStatus      = $this->settings['order-quantity'];
			$this->selectedStatuses   		= array(
				'COMPLETED'    => $this->settings['completed'],
				'FAILED'       => 'failed'
			);

		}

		/**
         * Logging method.
         *
         * @param string $message Log message.
         * @param string $level Optional. Default 'info'. Possible values:
         *                      emergency|alert|critical|error|warning|notice|info|debug.
         */
        public static function log( $message, $level = 'info' ) {
            if ( self::$log_enabled ) {
                if ( empty( self::$log ) ) {
                    self::$log = wc_get_logger();
                }
                self::$log->log( $level, $message, array( 'source' => 'mpay' ) );
            }
        }

 		/**
     	* Initialise settings form fields.
     	*
     	* Add an array of fields to be displayed
     	* on the gateway's settings screen.
     	*
     	* @since  1.0.0
     	*/
	   	public function init_form_fields(){
			$this->form_fields = include( 'includes/settings.php' );
      	}

		/**
		 * Process the payment and return the result.
		 *
		 * return the success and redirect in an array. e.g:
		 *
		 *        return array(
		 *            'result'   => 'success',
		 *            'redirect' => $this->get_return_url( $order )
		 *        );
		 *
		 * @param int $order_id
		 * @return array
		 */
	  	public function process_payment( $order_id ) {

			global $woocommerce;

			/**
			 * Create new order object.
			 * @var string $order_id
			 */

			$order = new WC_Order( $order_id );

			$responseItem = $this->item( $order, $woocommerce->cart->get_cart() );

			$responseCurl = $this->curl(array('environmentUrl' => esc_url($this->liveurl), 'password' => $responseItem['password'], 'opts' => $responseItem['opts']));			
			//$this->log( 'Payment Error: ' . $responseCurl->status.' 342', 'error' );

			

			if(empty($responseCurl)){
				wc_add_notice( sprintf( __( 'Incorect some parameters', "mpay" ) ) ,'error' );
			}

			/**
			 * Done payment and return array if @var $responseCurl->Errorcode not zero and isset @var $responseCurl->Data->Checkout
			 */
			if ( !isset($responseCurl->error) ){

				add_post_meta($order_id, 'mpayOrderID', $responseCurl->id);
				$order->add_order_note( __( $responseCurl->id, 'mpay' ) );
				$order->reduce_order_stock();
				$this->log( 'Success order '.$order->get_id().' and redirect MPay checkout page', 'success' );
				return array(
					'result'   => 'success',
					'redirect' => esc_url($responseCurl->url),
				);
			}else{
				$order->add_order_note( esc_html(__( 'MPay payment failed. Payment declined. Please Check your Admin settings', 'mpay' )) );
				$this->log( 'Payment Error: ' . $responseCurl->error, 'error' );
				wc_add_notice( sprintf( __( $responseCurl->error, "mpay" ) ) ,'error' );
			}

		}

		/**
		 * Parse each items.
		 * @var object $order
		 * @var object $items
		 * @return array hash and opts
		 */
		protected function item( $order, $items ){
global $woocommerce;        
	
	$currenctCurrency = get_woocommerce_currency();
			/**
			 * count total items.
			 * @var $totalItems
			 */
			$totalItems = count($items);

			/**
			 * Get cart page url for backLink.
			 * @var string $cartPageUrl
			 */
			$cartPageUrl = get_permalink( wc_get_page_id( 'cart' ) );

            is_user_logged_in() ? $merchantUser = get_current_user_id() : $merchantUser = 'GUEST';
			 $language = get_locale();

			//$backLink = base64_encode(add_query_arg(array('wc-api'=>'WC_Gateway_mpay_Order','ordercode'=>$order->id),home_url('/')) . "|" . $cartPageUrl);
			$backLink = add_query_arg(array('wc-api'=>'WC_Gateway_mpay_Order','ordercode'=>$order->id),home_url('/'));
			/**
			 * Get each item and assemble array for crypt.
			 * If $totalItems > 1 @var array $itemsArray
			 */
			foreach ( $items as $item ){

                $this->orderDescriptionStatus == 'yes' ?  $productDescription = preg_replace( '/[^\p{L}0-9\s]+/u', ' ',substr( get_post($item['product_id'])->post_content, 0,250 )) : $productDescription = '';
                $productTitle =  preg_replace('/[^\p{L}0-9\s]+/u',' ',$item['data']->post->post_title);

				if($totalItems > 1){
                    $this->orderQuantityStatus == 'yes' ? $quantity = $item['quantity'] : $quantity = '';
					$price = $item['line_subtotal'];
					$itemsArray[] = $price.'|'.$quantity.'|'.$productTitle.'|'.$productDescription;
				}
			}

			$arr = array(
				//"MerchantID"       => $this->merchantid,
				//"MerchantUser"     => $merchantUser,
				"title" => __('Order', 'mpay').' #'.($order->id),
				"key"  => uniqid('UN') . '-' . $order->id,
				"currency"	   => $currenctCurrency,
				"value"       => $order->order_total,
				"return_url"         => $backLink,
				"webhook" =>add_query_arg(array('wc-api'=>'WC_Gateway_mpay'),home_url('/')),
			);

			$orderItems = array();

			if($totalItems == 1){
				$arr['title'] = $productTitle;
				//$arr['OrderDescription'] = $productDescription;
			} else {
				$orderItems['Items'] = $itemsArray;
			}														
			
			$hash = array('Hash'=> md5($this->secretkey.'|'.implode('|',$arr)));
			$hash = !is_null($orderItems) ? array_merge($hash, $orderItems) : $hash;			
			$result = array_merge($hash, $arr);

			// Return thank you redirect
			return array(
				'password' => $this->secretkey,
				'opts' =>http_build_query($arr),
				//'opts'     => json_encode($result)
			);
		}

		/**
		 * Curl
		 * @param array $params
		 * @return curl $response
		 */
		protected function curl(array $params){
						
			$curl = curl_init($params['environmentUrl']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params['opts']); 
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                        
					'Content-Type: application/x-www-form-urlencoded',
					'Authorization: '.$params['password'])                                                               
				);  
			curl_setopt($curl, CURLOPT_HEADER, false);
	//wp_mail('getsev96@yandex.ru',$params['merchantId'],$params['password']);
			$curlResponse = curl_exec($curl);				
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			if ( $status != 200 ) {
			    $this->log( "Error: call to URL failed with status $status, response $curlResponse, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl), 'error' );
				die("Error: call to URL failed with status $status, response $curlResponse, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
			}
			
			$response = json_decode($curlResponse);			
			
			curl_close($curl);		
			
			return $response;
		}

		/**
		 * Cancel page
		 * @param $oder_id
		 */
		public function cancel_page($order_id){
		    $this->log( 'Cancell order '.$order_id.' and redirect cart page', 'info' );
			echo sprintf(__($this->failed_message, 'mpay'), 'error');
		}
		
	}
}


/**
  * Add this Gateway to WooCommerce
**/
function woocommerce_add_mpay_gateway($methods) {
	
      $methods[] = 'WC_Gateway_Wocommerce_mpay';
      return $methods;
}
add_filter('woocommerce_payment_gateways', 'woocommerce_add_mpay_gateway' );

add_filter('manage_edit-shop_order_columns', 'ST4_columns_head', 10);
add_action('manage_shop_order_posts_custom_column', 'ST4_columns_shop_order_content', 10, 2);
//ADD TWO NEW COLUMNS

function ST4_columns_head($defaults) {
    $defaults['mpayOrderID']  = 'mpayOrderID';
    return $defaults;
}
function ST4_columns_shop_order_content($column_name, $post_ID) {
    if ($column_name == 'mpayOrderID') {
        echo get_post_meta($post_ID,'mpayOrderID',true);
    }
}

function filter_gateways($gateways){
	global $woocommerce;        
	
	$currenctCurrency = get_woocommerce_currency();

	if ($currenctCurrency != "BIP") {		
		//unset($gateways['mpay']);
	}
	
	return $gateways;
}

add_filter('woocommerce_available_payment_gateways','filter_gateways');

add_filter( 'woocommerce_currencies', 'add_my_currency' );
function add_my_currency( $currencies ) {
     $currencies['BIP'] = __( 'BIP', 'woocommerce' );
     return $currencies;
}
