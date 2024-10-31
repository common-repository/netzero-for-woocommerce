<?php
/**
 * Main Netzero settings page
 */
defined('ABSPATH') or die('Nope, not accessing this');
?>
<div class="wrap">
  <header style="display: flex; flex-direction: row; flex-wrap: nowrap; align-items:center;">
    <img src="<?php echo plugin_dir_url( __DIR__ ); ?>assets/netzero-logo.svg" style="margin-right: 16px;" height="32px" alt="Netzero logo. Green diamond representing two trees in different shades of green." />
    <h1><b>Netzero for WooCommerce</b></h1>
  </header>
  <p>Start offsetting your carbon emissions with Netzero. Our WooCommerce plugin calculates the carbon emitted for your product to be delivered.</p>
  <p><a href="https://net-zero.earth/sign-in/" target="_blank">Go to your Netzero dashboard</a> to set up your account, choose who pays for the offset and more.</p>

  <form method="post" action="options.php">
    <?php settings_fields('netzero-settings'); ?>
    <?php do_settings_sections('netzero-settings'); ?>

    <h2>API Key</h2>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="netzero-api-key">API Key</label></th>
          <td>
            <input name="netzero-api-key" type="text" id="netzero-api-key" value="<?php echo get_option('netzero-api-key'); ?>" class="large-text">
            <p class="description" id="netzero-api-key-description">To set up your API key and start using the service, you'll first need to <a href="https://net-zero.earth/register/" target="_blank">create an account</a>.</p>
          </td>
        </tr>
      </tbody>
    </table>

    <h2>Settings</h2>
    <p>Plugin default settings.</p>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="netzero-api-key">Default product weight</label></th>
          <td>
            <input name="netzero-default-weight" type="number" min="0.1" step="any" id="netzero-default-weight" value="<?php echo get_option('netzero-default-weight'); ?>" placeholder="0.1" required> <?php echo get_option('woocommerce_weight_unit'); ?>
            <p class="description" id="netzero-default-weight-description">Default product(s) weight if none set. Used to calculate carbon offsets.</p>
          </td>
        </tr>
        <tr>
          <th scope="row"></th>
          <td>
            <input type="checkbox" name="netzero-powered-by" value="1" <?php checked( 1, get_option( 'netzero-powered-by' ), true ) ?> /><label for="netzero-powered-by">Display 'Powered by Netzero'</label>
          </td>
        </tr>
      </tbody>
    </table>

    <h2>Appearance</h2>
    <p>Change the default appearance of the checkbox on the checkout page.</p>
    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="netzero-api-key">Font weight</label></th>
          <td>
            <select name="netzero-font-weight" id="netzero-font-weight">
              <option value="bold" <?php echo get_option('netzero-font-weight') === 'bold' ? 'selected="selected"' : ''; ?>>Bold</option>
              <option value="normal" <?php echo get_option('netzero-font-weight') === 'normal' ? 'selected="selected"' : ''; ?>>Normal</option>
            </select>
            <p class="description" id="netzero-font-weight-description">Font weight for text (default: bold).</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="netzero-api-key">Color</label></th>
          <td>
            <input name="netzero-color" type="text" minlength="7" maxlength="7" id="netzero-color" value="<?php echo get_option('netzero-color'); ?>" placeholder="#48bb78">
            <p class="description" id="netzero-color-description">Hex color for checkbox (default: #48bb78).</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="netzero-custom-css">Custom CSS</label></th>
          <td><textarea class="regular-text ltr" style="min-height: 160px;" name="netzero-custom-css"><?php echo get_option('netzero-custom-css'); ?></textarea></td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(); ?>
  </form>
</div>
