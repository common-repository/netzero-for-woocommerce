<?php
/**
 * Netzero plugin styles
 */
defined('ABSPATH') or die('Nope, not accessing this');

if (!class_exists('netzero_styles')) {

  class netzero_styles {

    public function __construct() {
      // Stylesheet
      add_action('wp_enqueue_scripts', array(__class__, 'netzero_plugin_styles'), 10);

      // Custom styles
      add_action('wp_head', array(__class__, 'netzero_custom_styles'));
    }

    // Main plugin stylesheet
    public static function netzero_plugin_styles() {
      wp_enqueue_style(
          'netzero-plugin-style',
          plugins_url('/assets/css/netzero.css', dirname(__FILE__)),
          '',
          '',
          'all'
      );
    }

    // Custom styles
    public static function netzero_custom_styles() {
      $color = strip_tags(get_option('netzero-color'));
      $font_weight = strip_tags(get_option('netzero-font-weight'));
      $custom_styles = strip_tags(get_option('netzero-custom-css'));

      $styles = '<style>';
      $styles .= $color 
        ? '#netzero-wrapper input { border: 3px solid '. $color .'; } #netzero-wrapper input:active, #netzero-wrapper input:checked:active, #netzero-wrapper input:checked { background-color: '. $color .'; }'
        : '#netzero-wrapper input { border: 3px solid #48bb78; } #netzero-wrapper input:active, #netzero-wrapper input:checked:active, #netzero-wrapper input:checked { background-color: #48bb78; }';
      $styles .= $font_weight
        ? '#netzero-wrapper label, #netzero-company-wrapper { font-weight: '. $font_weight .'; }'
        : '#netzero-wrapper label, #netzero-company-wrapper { font-weight: bold; }';
      $styles .= $custom_styles;
      $styles .= '</style>';
      echo $styles;
    }
  }

  new netzero_styles();
}