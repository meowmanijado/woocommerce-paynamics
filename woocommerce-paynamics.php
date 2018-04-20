<?php
/**
 * Plugin Name: WooCommerce Paynamics
 * Description: Add Paynamics Payment Gateway
 * Version: 1.0.0
 * Author: Meow Manijado
 * License: GPL2
 */

add_action( 'plugins_loaded', 'init_paynamics_class' );

function init_paynamics_class() {
	class WC_Paynamics extends WC_Payment_Gateway {
		public function __construct()
		{
			$this->id 				  = 'paynamics';
			$this->method_title       = __( 'Paynamics', 'woocommerce' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title = $this->settings['title'];
			$this->merch_id = $this->settings['merch_id'];
			$this->merch_cert = $this->settings['merch_cert'];
			$this->ip_add = $this->settings['ip_add'];
			$this->noturl = $this->settings['noturl'];
			$this->resurl = $this->settings['resurl'];
			$this->cancelurl = $this->settings['cancelurl'];
			$this->state = $this->settings['state'];
			$this->country = $this->settings['country'];
			$this->currency = $this->settings['currency'];

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Paynamics', 'woocommerce' )
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce' ),
                    'type' => 'text',
                    'default' => __( 'Paynamics', 'woocommerce' )
                ),
                'merch_id' => array(
                    'title' => __( 'Merchant ID', 'woocommerce' ),
                    'type' => 'text'
				),
				'merch_cert' => array(
                    'title' => __( 'Merchant ID', 'woocommerce' ),
                    'type' => 'text'
				),
				'ip_add' => array(
                    'title' => __( 'IP Address', 'woocommerce' ),
                    'type' => 'text'
				),
				'noturl' => array(
                    'title' => __( 'Link response posted', 'woocommerce' ),
                    'type' => 'text'
                ),
				'resurl' => array(
                    'title' => __( 'Landing Page', 'woocommerce' ),
                    'type' => 'text'
                ),
				'cancelurl' => array(
                    'title' => __( 'Cance URL', 'woocommerce' ),
                    'type' => 'text'
				),
				'state' => array(
                    'title' => __( 'State', 'woocommerce' ),
					'type' => 'text',
					'default' => __( 'MM', 'woocommerce' )
                ),
				'country' => array(
                    'title' => __( 'Country', 'woocommerce' ),
					'type' => 'text',
					'default' => __( 'PH', 'woocommerce' )
                ),
				'currency' => array(
                    'title' => __( 'Currency', 'woocommerce' ),
					'type' => 'text',
					'default' => __( 'PHP', 'woocommerce' )
                )
            );
		}

		function process_payment( $order_id ) 
		{
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$items = array_shift($order->get_items());

			$order_items = $order->get_items();
			$order_amount = number_format((float)$order->get_total(), 2, '.', '');

			$_mid = $this->merch_id; //<-- your merchant id
		      $_requestid = substr(uniqid(), 0, 13);
		      $_ipaddress = $this->ip_add;
		      $_noturl = $this->noturl; // url where response is posted
		      $_resurl = $this->resurl; //url of merchant landing page
			  $_cancelurl = $this->cancelurl; //url of merchant landing page
		      $_fname = $items['First Name']; // kindly set this to first name of the cutomer
		      $_mname = $items['Last Name']; // kindly set this to middle name of the cutomer
		      $_lname = $items['Last Name']; // kindly set this to last name of the cutomer
		      $_addr1 = $items['Address']; // kindly set this to address1 of the cutomer
		      $_addr2 = "";// kindly set this to address2 of the cutomer
		      $_city = ""; // kindly set this to city of the cutomer
		      $_state = $this->state; // kindly set this to state of the cutomer
		      $_country = $this->country; // kindly set this to country of the cutomer
		      $_zip = ""; // kindly set this to zip/postal of the cutomer
		      $_sec3d = "try3d"; // 
		      $_email = $items['item_meta']['E-mail'][0]; // kindly set this to email of the cutomer
		      $_phone = $items['Contact Number']; // kindly set this to phone number of the cutomer
		      $_mobile = ""; // kindly set this to mobile number of the cutomer
		      $_clientip = $_SERVER['REMOTE_ADDR'];
		      $_amount = $order_amount; // kindly set this to the total amount of the transaction. Set the amount to 2 decimal point before generating signature.
		      $_currency = $this->currency; //PHP or USD
		      $forSign = $_mid . $_requestid . $_ipaddress . $_noturl . $_resurl .  $_fname . $_lname . $_mname . $_addr1 . $_addr2 . $_city . $_state . $_country . $_zip . $_email . $_phone . $_clientip . $_amount . $_currency . $_sec3d;
					$cert = $this->merch_cert; //<-- your merchant key
		
		      $_sign = hash("sha512", $forSign.$cert);
		      
			  $strxml = "";
		     
		      $strxml = $strxml . "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
		      $strxml = $strxml . "<Request>";
		      $strxml = $strxml . "<orders>";
			      $strxml = $strxml . "<items>";
			      
			      foreach($order_items as $item)
			        {
			        	$item_amount = number_format((float)$item['item_meta']['_line_total'][0], 2, '.', '');
			        	//print_r($item_amount);
			        	$strxml = $strxml . "<Items>";
			            $strxml = $strxml . "<itemname>".$item['name']."</itemname><quantity>".$item['item_meta']['_qty'][0]."</quantity><amount>".$item_amount."</amount>";
			            $strxml = $strxml . "</Items>";
			        }
			      
			      $strxml = $strxml . "</items>";
		      $strxml = $strxml . "</orders>";
		      $strxml = $strxml . "<mid>" . $_mid . "</mid>";
		      $strxml = $strxml . "<request_id>" . $_requestid . "</request_id>";
		      $strxml = $strxml . "<ip_address>" . $_ipaddress . "</ip_address>";
		      $strxml = $strxml . "<notification_url>" . $_noturl . "</notification_url>";
		      $strxml = $strxml . "<response_url>" . $_resurl . "</response_url>";
		      $strxml = $strxml . "<cancel_url>" . $_cancelurl . "</cancel_url>";
		      $strxml = $strxml . "<mtac_url></mtac_url>"; // pls set this to the url where your terms and conditions are hosted
		      $strxml = $strxml . "<descriptor_note>''</descriptor_note>"; // pls set this to the descriptor of the merchant ""
		      $strxml = $strxml . "<fname>" . $_fname . "</fname>";
		      $strxml = $strxml . "<lname>" . $_lname . "</lname>";
		      $strxml = $strxml . "<mname>" . $_mname . "</mname>";
		      $strxml = $strxml . "<address1>" . $_addr1 . "</address1>";
		      $strxml = $strxml . "<address2>" . $_addr2 . "</address2>";
		      $strxml = $strxml . "<city>" . $_city . "</city>";
		      $strxml = $strxml . "<state>" . $_state . "</state>";
		      $strxml = $strxml . "<country>" . $_country . "</country>";
		      $strxml = $strxml . "<zip>" . $_zip . "</zip>";
		      $strxml = $strxml . "<secure3d>" . $_sec3d . "</secure3d>";
		      $strxml = $strxml . "<trxtype>sale</trxtype>";
		      $strxml = $strxml . "<email>" . $_email . "</email>";
		      $strxml = $strxml . "<phone>" . $_phone . "</phone>";
		      $strxml = $strxml . "<mobile>" . $_mobile . "</mobile>";
		      $strxml = $strxml . "<client_ip>" . $_clientip . "</client_ip>";
		      $strxml = $strxml . "<amount>" . $_amount . "</amount>";
		      $strxml = $strxml . "<currency>" . $_currency . "</currency>";
		      $strxml = $strxml . "<mlogo_url></mlogo_url>";// pls set this to the url where your logo is hosted
		      $strxml = $strxml . "<pmethod></pmethod>";
		      $strxml = $strxml . "<signature>" . $_sign . "</signature>";
		      $strxml = $strxml . "</Request>";
		      $b64string =  base64_encode($strxml);

			    echo '<div class="container">';

				echo '<form name="surecollect" id="surecollect" method="post" action="https://testpti.payserv.net/webpaymentv2/default.aspx"><input type="hidden" name="paymentrequest" value="'.$b64string.'">

				<script>

					window.onload = function(){
					  document.forms["surecollect"].submit()

					}
				</script>';

				WC()->cart->empty_cart();
		}
	}
}
 
function add_paynamics_class( $methods ) {
	$methods[] = 'WC_Paynamics'; 
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_paynamics_class' );