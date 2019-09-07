<?php
/**
 * Handles backlink.
 *
 * @package WooCommerce/MPay
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_mpay_Back_Link_Handler extends WC_Payment_Gateway {

    /**
     * Success and failed messages.
     * @var array $messages.
     */
    private $messages;

    /**
     * Constructor.
     *
     * @param array @messages
     */
    public function __construct( array $messages ) {
        $this->messages = $messages;

        add_action( 'woocommerce_api_wc_gateway_mpay_order', array( $this, 'handle_order' ) );
    }

    public function handle_order(){

        if(!empty( sanitize_text_field($_GET['ordercode']) )){

            $this->successOrder(sanitize_text_field($_GET['ordercode']));
        }
    }

    /**
     * success order and redirect url.
     * @param $orderId
     */
    protected function successOrder($orderId){

        global $woocommerce;

        $order = new WC_Order( $orderId );

        WC_Gateway_Wocommerce_mpay::log( 'Complete payment order: '.$order->get_id().'. and redirect order receive page', 'success' );
        wc_add_notice( sprintf( __( $this->messages['success']) , "mpay" )  ,'success' );
        $woocommerce->cart->empty_cart();
        $redirectUrl = esc_url_raw($this->get_return_url( $order ));
        wp_redirect( $redirectUrl ); exit;
    }

}