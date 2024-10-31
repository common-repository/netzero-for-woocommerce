<?php
/**
 * Woocommerce checkout changes
 */
defined('ABSPATH') or die('Nope, not accessing this');

if (!class_exists('netzero_add_to_order')) {

  class netzero_add_to_order {

    public function __construct() {
      // Woocommerce functions
      add_action( 'woocommerce_review_order_before_submit', array(__class__, 'add_netzero_to_order') );
      add_action( 'woocommerce_cart_calculate_fees', array(__class__, 'netzero_fee'), 20, 1 );
      add_action( 'woocommerce_thankyou', array(__class__, 'netzero_estimate_to_order' ) );
      add_action( 'woocommerce_admin_order_data_after_billing_address', array( __class__, 'netzero_purchase_id_after_billing' ), 10, 1 );

      // jQuery
      add_action( 'wp_footer', array(__class__, 'netzero_cart_refresh') );
      add_action( 'wp_footer', array(__class__, 'netzero_refresh_checkout') );
      add_action( 'wp_ajax_woo_get_nzero_action', array(__class__, 'netzero_action') );
      add_action( 'wp_ajax_nopriv_woo_get_nzero_action', array(__class__, 'netzero_action') );
      add_action( 'wp_ajax_woo_get_nzero_data', array(__class__, 'netzero_choice_set_session') );
      add_action( 'wp_ajax_nopriv_woo_get_nzero_data', array(__class__, 'netzero_choice_set_session') );
    }

    /**
     * Add checkbox to order
     */
    public static function add_netzero_to_order( $checkout ) {
      if ( ! is_checkout() ) return;
      $customer_postcode = WC()->customer->get_shipping_postcode();
      if (!$customer_postcode) return;

      // If already have an estimate and customer postcode + destinations match, don't re-call API
      // Recalling the API will reset the sessions and choices etc.
      if (!WC()->session->get('estimate_id') || $customer_postcode !== WC()->session->get('destination')) {
        // Get store and customer info
        $store_postcode = WC()->countries->get_base_postcode();
        $weight_unit = get_option('woocommerce_weight_unit');
        $api_key = get_option('netzero-api-key');

        // Get product weights and add them together
        // This is required in case no weight is set in the product itself
        // as the fallback will be the default weight set in the dashboard.
        $weight = 0;
        $items = WC()->cart->get_cart();
        foreach($items as $item => $values) { 
          $product = wc_get_product( $values['data']->get_id() );
          $weight = $product->weight
            ? $weight = $weight + $product->weight
            : $weight = $weight + get_option('netzero-default-weight');
        }

        // Ensure weight is always in KG for the API
        if ($weight_unit == 'kg')  $weight = $weight;
        if ($weight_unit == 'g')   $weight = $weight / 1000;
        if ($weight_unit == 'lbs') $weight = $weight / 2.2;
        if ($weight_unit == 'oz')  $weight = $weight / 35.274;

        // Only do the API call if everything is in place
        if ($weight) {
          // Init REST call
          $bearer_auth = 'Bearer api-key:' . $api_key;
          $args = array(
            'method'  => 'POST',
            'headers' => array(
              'Authorization' => $bearer_auth,
              'Content-type'  => 'application/json'
            ),
            'sslverify' => false,
            'body' => '{
              "origin": "'. $store_postcode. '",
              "destination": "'. $customer_postcode .'",
              "weight": "'. $weight .'"
            }',
            'data_format' => 'body',
            'cookies'     => array()
          );

          // Get values from API call
          $response = wp_remote_post ( 'https://api.net-zero.earth/v1/estimates/create-carbon', $args );
          $data = json_decode(wp_remote_retrieve_body( $response ), true);

          // If no price (400 response), return nothing
          if (!$data['price']) return;

          // Set woocommerce sessions
          WC()->session->set('netzero_checked', false);
          WC()->session->set('payment_by', $data['paymentby']);
          WC()->session->set('price', $data['price']);
          WC()->session->set('destination', $data['destination']);
          WC()->session->set('estimate_id', $data['id']);
          WC()->session->set('currency', $data['currency']);
          WC()->session->set('sandbox', $data['sandbox']);
        }
      }

      // Split out the data
      $payment_by = WC()->session->get('payment_by');
      $price = WC()->session->get('price');
      $estimate_id = WC()->session->get('estimate_id');
      $currency = WC()->session->get('currency');
      $sandbox = WC()->session->get('sandbox');
      $powered_by = get_option('netzero-powered-by');

      // If not sandbox, show checkbox
      if (!$sandbox) {
        switch($currency) {
          case 'usd':
            $currency_symbol = '$';
            break;
          case 'gbp':
            $currency_symbol = '£';
            break;
          case 'eur':
            $currency_symbol = '€';
            break;
          default:
            $currency_symbol = '£';
            break;
        }

        if ($payment_by === 'company') {
          echo '<div id="netzero-company-wrapper">';
          echo '<p>We are paying '. $currency_symbol . number_format($price, 2, '.', '') .' towards offsetting carbon emissions for this order.';
          echo $powered_by ? '<span class="powered-by"><a href="https://net-zero.earth" target="_blank">Powered by Netzero</a></span>' : '';
          echo '</div>';
        } else {
          // Display checkbox
          echo '<div id="netzero-wrapper">';
          woocommerce_form_field( 'netzero_checkbox', array(
            'type'          => 'checkbox',
            'class'         => array('nzero-checkbox'),
            'label_class'   => array('nzero-label'),
            'input_class'   => array('nzero-checkbox'),
            'label'         => __('Offset carbon emissions for '. $currency_symbol . number_format($price, 2, '.', '') .'.'),
          ), WC()->session->get('netzero_checked')
          );
          echo $powered_by ? '<span class="powered-by"><a href="https://net-zero.earth" target="_blank">Powered by Netzero</a></span>' : '';
          echo '</div>';
        }
      }
    }

    /**
     * AJAX update cart when postcode field updated
     */
    public static function netzero_refresh_checkout() {
      if ( ! is_checkout() ) return; ?>
        <script type="text/javascript">
          jQuery(function($) {
            $('#billing_postcode, #shipping_postcode').blur(function() {
              $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                  'action': 'woo_get_nzero_action'
                },
                success: function (result) {
                  $('body').trigger('update_checkout');
                }
              });
            });
          });
        </script>
      <?php
    }

    /**
     * Calculate the cart
     */
    public static function netzero_fee( $cart ) {
      if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
      $checked = WC()->session->get('netzero_checked');
      if ($checked) {
        $fee = WC()->session->get('price');
        $cart->add_fee( __('Carbon offset', 'woocommerce'), $fee );
      }
    }

    /**
     * AJAX for updating the cart
     */
    public static function netzero_cart_refresh() {
      if ( ! is_checkout() ) return;
      ?>
      <script type="text/javascript">
      jQuery(function($) {
        $('form.checkout').on('change', 'input[name=netzero_checkbox]', function(e) {
          e.preventDefault();
          var checked;
          if (document.getElementById('netzero_checkbox').checked) {
            checked = 'checked';
          } else {
            checked = 'unchecked';
          }
          $.ajax({
            type: 'POST',
            url: wc_checkout_params.ajax_url,
            data: {
              'action': 'woo_get_nzero_data',
              'checked': checked,
            },
            success: function (result) {
              $('body').trigger('update_checkout');
            }
          });
        });
      });
      </script>
      <?php
    }

    /**
     * Add checkbox choice to session
     */
    public static function netzero_choice_set_session() {
      if ( isset($_POST['checked']) ){
        $checked = sanitize_key($_POST['checked']);
        if ($checked == 'checked') {
          WC()->session->set('netzero_checked', true );
        } else {
          WC()->session->set('netzero_checked', false );
        }
      }
      die();
    }

    /**
     * Add netzero estimate id to order if option chosen
     */
    public static function netzero_estimate_to_order( $order_id ) {
      if ( ! $order_id ) return;

      $order = wc_get_order( $order_id );
      $checked = WC()->session->get( 'netzero_checked' );
      $payment_by = WC()->session->get( 'payment_by' );

      // If the checkbox is checked, send the estimate ID to conver to a purchase
      if ($checked || $payment_by === 'company') {
        // Add custom meta so API call only made after order is complete
        $api_key = get_option('netzero-api-key');
        $bearer_auth = 'Bearer api-key:' . $api_key;
        $estimate_id = WC()->session->get('estimate_id');

        $args = array(
          'method' => 'POST',
          'headers' => array(
            'Authorization' => $bearer_auth,
            'Content-type'  => 'application/json'
          ),
          'httpversion' => '1.0',
          'body'        => '{"estimate": "'. $estimate_id .'"}'
        );

        // Make API call and set purchase ID for backend
        $response = wp_remote_post ( 'https://api.net-zero.earth/v1/purchases/create', $args );
        $data = json_decode(wp_remote_retrieve_body( $response ), true);
        add_post_meta( $order_id, '_netzero_purchase_id', $data['id'], true );

        // Unset all custom WC sessions
        WC()->session->__unset( 'netzero_checked' );
        WC()->session->__unset( 'payment_by' );
        WC()->session->__unset( 'price' );
        WC()->session->__unset( 'destination' );
        WC()->session->__unset( 'estimate_id' );
        WC()->session->__unset( 'currency' );
        WC()->session->__unset( 'sandbox' );
      }
    }

    /**
     * Display the purchase ID on order
     */
    public static function netzero_purchase_id_after_billing( $order ) {
      echo '<p><strong>'.__('Netzero purchase ID').':</strong> <br/>' . get_post_meta( $order->get_id(), '_netzero_purchase_id', true ) . '</p>';
    }

  }

  // Init class
  new netzero_add_to_order();

}