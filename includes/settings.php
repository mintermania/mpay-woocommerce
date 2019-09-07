<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_enqueue_js( "
	jQuery( function( $ ) {
		var ppec_mark_fields	= '#woocommerce_mpay_image-color, #woocommerce_mpay_image-type, #woocommerce_mpay_image-size';				

		$( '#woocommerce_mpay_image-status' ).change(function(){
			if ( $( this ).is( ':checked' ) ) {
				$( ppec_mark_fields ).closest( 'tr' ).show();
			} else {
				$( ppec_mark_fields ).closest( 'tr' ).hide();
			}
		}).change();

	});
" );

$settings =  array(
	'enabled'			=> array(
			'title'        => __('Enable/Disable', 'mpay'),
			'type'         => 'checkbox',						
			'label'        => __('Enable MPay Payment Module.', 'mpay'),
			'default'      => 'yes',
	),
	'secretkey' 	    => array(
			'title'        => __('MPay API token', 'mpay'),
			'type'         => 'text',
			'desc_tip'     => __('Create new API token in your MPay control panel','mpay'),
	),
	'call_back_URL'    	=> array(
			'title'        => __('Webhook URL','mpay'),
			'type'         => 'title',
			'desc_tip'     => __('Setup webhook URL in MPay control panel', 'mpay'),
            'description'  => sprintf('%s','<code>'.get_site_url()."/?wc-api=WC_Gateway_mpay".'</code>'),
	),
    'debug'                 => array(
            'title'        => __( 'Log', 'woocommerce' ),
            'type'         => 'checkbox',
            'label'        => __( 'Enable log', 'woocommerce' ),
            'desc_tip'     => __('Plugin logging for debug', 'mpay'),
            'description'  => sprintf( __( '%s', 'mpay' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'mpay' ) . '</code>' ),
    ),
	'checkout-page' 	=> array(
            'title'       => __( 'Checkout Page', 'mpay' ),
            'type'        => 'title',
            'description' => __('Advanced options of Checkout page','mpay'),
	),
    'title'		 		=> array(
			'title'        => __('Title:', 'mpay'),
			'type'         => 'text',
			'default'      => __('MPay Checkout', 'mpay'),
			'desc_tip'     => __('Title which the user sees during checkout', 'mpay'),
  	),
    'description'		=> array(
			'title'        => __('Description:', 'mpay'),
			'type'         => 'textarea',
			'default'      => __('Pay securely by any Minter cryptocurrency through MPay Secure Servers.', 'mpay'),
			'css'		   => 'max-width:400px;',
			'desc_tip'     => __('The description which the user sees during checkout','mpay'),
	),
	'button-title'		=> array(
			'title'        	=> __('Button', 'mpay'),
			'type'    		=> 'text',
			'default'      	=> __('Pay Now', 'mpay'),
			'desc_tip'     	=> __('The button text which the user sees during checkout', 'mpay'),
	  ),	
	

/*	'process-payment' 	=> array(
		'title'       => __( 'Payment status', 'mpay' ),
		'type'        => 'title',
		'description' => __('MPay  process status','mpay'),
	),
  	'completed' 		=> array(
			'title'        => __('MPay Completed status', 'mpay'),
			'type'         => 'select',						
			'description'  =>  __('Set status, when MPay order is "Completed"', 'mpay'),
			'options'      => array(
				'completed'  	=> __('completed','mpay'), 
				'processing'	=> __('processing','mpay'),
				'cancelled'  	=> __('cancelled','mpay'), 
				'pending'    	=> __('pending','mpay'), 
				'on-hold'    	=> __('on-hold','mpay'), 
				'refuned'    	=> __('refuned','mpay'), 
				'failed'     	=> __('failed', 'mpay'),
      		)
  	),				
  	'processing' 		=> array(
			'title'        => __('MPay Processing Status', 'mpay'),
			'type'         => 'select',						
			'description'  =>  __('Set status, when MPay order is "Processing"', 'mpay'),
			'options'      => array(
				'processing'	=> __('processing','mpay'),
				'completed'  	=> __('completed','mpay'), 
				'cancelled'  	=> __('cancelled','mpay'), 
				'pending'    	=> __('pending','mpay'), 
				'on-hold'    	=> __('on-hold','mpay'), 
				'refuned'    	=> __('refuned','mpay'), 
				'failed'     	=> __('failed', 'mpay'),
      		)
  	),*/
    'success_message'=> array(
			'title'        => __('Success Message', 'mpay'),
			'type'         => 'textarea',
			'desc_tip'  =>  __('Message to be displayed on successful transaction.', 'mpay'),
			'default'      => __('Your order has been processed successfully.', 'mpay'),
			'css'		   => 'max-width:400px;',
  	),            	
	'failed_message' => array(
			'title'        => __('Failed Message', 'mpay'),
			'type'         => 'textarea',
			'desc_tip'  =>  __('Message to be displayed on failed transaction.', 'mpay'),
			'default'      => __('Your order has been declined.', 'mpay'),
			'css'		   => 'max-width:400px;',
	),
/*	'mpay-checkout-page' => array(
            'title'       => __( 'MPay Checkout page', 'mpay' ),
            'type'        => 'title',
            'description' => __('Advanced options of MPay Checkout page','mpay'),
	),
	'order-description' => array(
			'title'        => __('Order Description', 'mpay'),
			'type'         => 'checkbox',						
			'label'        => __('Enable Order Description', 'mpay'),
			'default'      => 'yes',
			'desc_tip'  =>  __('This controls  the description  which the user sees during MPay  checkout page', 'mpay'),
	),	
	'order-quantity' 	=> array(
			'title'        => __('Order Quantity', 'mpay'),
			'type'         => 'checkbox',						
			'label'        => __('Enable Order Quantity', 'mpay'),
			'default'      => 'yes',
			'desc_tip'     =>  __('This controls the order quantity which the user sees during MPay checkout page', 'mpay'),
	),																					
  	'slogan'    		=> array(
			'title'        => __('Slogan', 'mpay'),
			'type'         => 'text',
			'desc_tip'     => __('Add text is  displayed as your slogan of the MPay checkout page (max. 70 symbols)', 'mpay'),
			'placeholder'  => 'Optional',
  ),
	'logo_image_url'	 => array(
			'title'         => __( 'Logo Image (150x80)', 'mpay' ),
			'type'          => 'image',
			'desc_tip'      => __('Upload image is displayed as your logo in the upper left corner of  the MPay checkout page', 'mpay'),
            'description'   => __( 'The image must be PNG or JPG format.', 'mpay' ),
			
	),*/
);

return apply_filters( 'woocommerce_mpay_checkout_settings', $settings );