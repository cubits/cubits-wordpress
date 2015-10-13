<?php
/**
 * Plugin Name: Cubits
 * Description: Add Cubits payment buttons to your WordPress site.
 * Version: 1.0
 * Author: Dooga Ltd.
 * Author URI: https://cubits.com
 * License: GPLv2 or later
 */

/*

Copyright (C) 2014 Dooga Ltd.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

define('Cubits_PATH', plugin_dir_path( __FILE__ ));
define('Cubits_URL', plugins_url( '', __FILE__ ));

require_once(plugin_dir_path( __FILE__ ) . 'cubits-php/lib/Cubits.php');
require_once(plugin_dir_path( __FILE__ ) . 'widget.php');

class WP_Cubits {

  private $plugin_path;
  private $plugin_url;
  private $l10n;
  private $wpsf;

  function __construct() {
    $this->plugin_path = plugin_dir_path( __FILE__ );
    $this->plugin_url = plugin_dir_url( __FILE__ );
    $this->l10n = 'wp-settings-framework';
    add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
    add_action( 'admin_init', array(&$this, 'admin_init'), 99 );
    add_action('admin_enqueue_scripts', array(&$this, 'admin_styles'), 1);
    add_action('admin_enqueue_scripts', array(&$this, 'widget_scripts'));

    // Include and create a new WordPressSettingsFramework
    require_once( $this->plugin_path .'wp-settings-framework.php' );
    $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/Cubits.php' );

    add_shortcode('cubits_button', array(&$this, 'shortcode'));
  }

  function admin_menu() {
    add_submenu_page( 'options-general.php', __( 'Cubits', $this->l10n ), __( 'Cubits', $this->l10n ), 'update_core', 'cubits', array(&$this, 'settings_page') );
  }

  function admin_init() {
    register_setting ( 'cubits', 'cubits-tokens' );
  }

  function settings_page() {
    $api_key = wpsf_get_setting( 'cubits', 'general', 'api_key' );
    $api_secret = wpsf_get_setting( 'cubits', 'general', 'api_secret' );

    ?>
      <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2>Cubits</h2>

    <?php
        $this->wpsf->settings();
    ?>
      </div>
    <?php
  }

  function shortcode( $atts, $content = null ) {
    $defaults = array(
          'name'               => 'test',
          'price_string'       => '1.23',
          'price_currency_iso' => 'USD',
          'custom'             => 'Order123',
          'description'        => 'Sample description',
          'type'               => 'buy_now',
          'style'              => 'buy_now_large',
          'text'               => 'Pay with Bitcoin',
          'choose_price'       => false,
          'variable_price'     => false,
          'price1'             => '0.0',
          'price2'             => '0.0',
          'price3'             => '0.0',
          'price4'             => '0.0',
          'price5'             => '0.0',
    );

    $args = shortcode_atts($defaults, $atts, 'cubits_button');

    // Clear default price suggestions
    for ($i = 1; $i <= 5; $i++) {
      if ($args["price$i"] == '0.0') {
        unset($args["price$i"]);
      }
    }

    $transient_name = 'cb_ecc_' . md5(serialize($args));
    $cached = get_transient($transient_name);
    // if($cached !== false) {
    //   echo var_dump($cached);
    //   return $cached;
    // }

    $api_key = wpsf_get_setting( 'cubits', 'general', 'api_key' );
    $api_secret = wpsf_get_setting( 'cubits', 'general', 'api_secret' );
    if( $api_key && $api_secret ) {
      try {
        Cubits::configure("https://pay.cubits.com/api/v1/",true);
        $cubits = Cubits::withApiKey($api_key, $api_secret);
        $options = array(
          'callback_url'       => null,
          'reference'          => time(),
          'success_url'        => null,
          'cancel_url'         => null
        );

        $link = $cubits->createInvoice($args["name"], $args["price_string"], $args["price_currency_iso"], $options)->invoice_url;
        $button = "<a href='".$link."'>Go to invoice</a>";
      } catch (Exception $e) {
        $msg = $e->getMessage();
        error_log($msg);
        return "There was an error connecting to Cubits: $msg. Please check your internet connection and API credentials.";
      }
      set_transient($transient_name, $button);
      return $button;
    } else {
      return "The Cubits plugin has not been properly set up - please visit the Cubits settings page in your administrator console.yy";
    }
  }
  public function admin_styles() {
    wp_enqueue_style( 'cubits-admin-styles',  Cubits_PATH.'/css/cubits-admin.css', array(), '1', 'all' );
  }

  public function widget_scripts( $hook ) {
    if( 'widgets.php' != $hook )
      return;
    wp_enqueue_script( 'cubits-widget-scripts', Cubits_PATH .'/js/cubits-widget.js', array('jquery'), '', true );
  }
}
new WP_Cubits();

?>