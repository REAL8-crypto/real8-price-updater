<?php
if (!defined('ABSPATH')) {
    exit;
}

class Real8_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page under WooCommerce menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('REAL8 Price Updater Settings', 'real8-price-updater'),
            __('REAL8 Price Updater', 'real8-price-updater'),
            'manage_options',
            'real8-price-updater',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('real8_settings_group', 'real8_product_id', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('REAL8 Price Updater Settings', 'real8-price-updater'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('real8_settings_group');
                do_settings_sections('real8-price-updater');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="real8_product_id"><?php esc_html_e('REAL8 Product ID', 'real8-price-updater'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="real8_product_id" id="real8_product_id" value="<?php echo esc_attr(get_option('real8_product_id', '')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Enter the WooCommerce Product ID for the REAL8 product. You can find this in the Products admin page.', 'real8-price-updater'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
