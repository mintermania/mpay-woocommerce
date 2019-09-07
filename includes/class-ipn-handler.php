<?php
/**
 * Handles responses from MPay IPN.
 *
 * @package WooCommerce/MPay
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_mpay_IPN_Handler extends WC_Payment_Gateway {

    /**
     * User choose order statuses.
     * @var array
     */
    private $selectedStatuses;

    /**
     * Merchant secret key.
     * @var string
     */
    private $secretkey;

    /**
     * Constructor.
     *
     * @param array $selectedStatuses User choose order statuses.
     * @param @secretkey
     */
    public function __construct( array $selectedStatuses, $secretkey ) {
        $this->selectedStatuses = $selectedStatuses;
        $this->secretkey        = $secretkey;

        add_action( 'woocommerce_api_wc_gateway_mpay', array( $this, 'handle_callback' ) );
    }

    /**
     * Check order status.
     */
    public function handle_callback(){

        global $woocommerce;

       // if( empty(sanitize_text_field($_POST['secret'])) ) return;

        /**
         * Calculated hash.
         * @var string
         */
        //$Hash = !empty(sanitize_text_field($_POST['secret'])) ? sanitize_text_field($_POST['secret']) : '';
        $Hash = substr($this->secretkey,(strpos($this->secretkey,':')+1),6);
        /**
         * mpay order id. it's unique.
         * @var string
         */
        $mpayOrderID = !empty(sanitize_text_field($_POST['id'])) ? sanitize_text_field($_POST['id']) : '';

        /**
         * Changeable merchant odrer id.
         * @var string
         */
        $MerchantOrderID = !empty(sanitize_text_field($_POST['key'])) ? sanitize_text_field($_POST['key']): '';

        /**
         * Payment status. (COMPLETED, CANCELED, PENDING, FAILED).
         * @var string
         */
        $Status = !empty(sanitize_text_field($_POST['status'])) ? sanitize_text_field($_POST['status']) : '';

        $Reason = '';
        /**
         * Calculate hash  with secret key and all string parameters which are passed to function.
         * @param String $mpayOrderId.
         * @param String $merchandOrderId.
         * @param String $status.
         * @param String $secretKey.
         */
        $calculateHash =  !empty(sanitize_text_field($_POST['secret'])) ? sanitize_text_field($_POST['secret']): '';

        /**
         * Compare passed $hash and calcualted new hash.
         * If equals @param $hash and @param $calculateHash update order status by which user selected status.
         * If not equals order update status cancelled.
         */

        if( $Hash == $calculateHash ){
            $orderID = explode("-", $MerchantOrderID);
            $order = new WC_Order( $orderID[1] );

            if ($Status == 1) {
                $Status = 'COMPLETED';
            } else {
                $Status = 'FAILED';
            }

            $order->update_status($this->selectedStatuses[$Status],__( 'Awaiting REDSYS payment', 'woocommerce' ));
            WC_Gateway_Wocommerce_mpay::log( 'Update order ('.$order->get_id().') status. set '.$this->selectedStatuses[$Status].' status. MPay status is '.$Status.' Reason: '.$Reason, 'info' );
            if($Status === 'COMPLETED'){
                WC_Gateway_Wocommerce_mpay::log( 'Complete payment order: '.$order->get_id(), 'success' );
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
            }
        }
        else{
            WC_Gateway_Wocommerce_mpay::log( 'Calculate hash not equal hash '.$Hash.'!=='.md5($calculateHash),'error' );
        }

    }
}
