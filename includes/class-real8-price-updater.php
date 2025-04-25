<?php
if (!defined('ABSPATH')) {
    exit;
}

use ZuluCrypto\StellarSdk\Horizon\ApiClient;

class Actualizar_Precio_REAL8 {
    public function __construct() {
        // Schedule the price update event
        add_action('wp', [$this, 'schedule_price_update']);
        add_action('real8_price_update_event', [$this, 'update_real8_price']);
    }

    /**
     * Schedule the hourly price update
     */
    public function schedule_price_update() {
        if (!wp_next_scheduled('real8_price_update_event')) {
            wp_schedule_event(time(), 'hourly', 'real8_price_update_event');
        }
    }

    /**
     * Fetch and update the REAL8 price
     */
    public function update_real8_price() {
        // Step 1: Fetch REAL8 price from Horizon
        $price_in_xlm = $this->fetch_real8_price();

        if (!$price_in_xlm) {
            error_log('REAL8 Price Updater: No recent trades found for REAL8.');
            return;
        }

        // Step 2: Convert to USD
        $price_in_usd = $this->convert_to_usd($price_in_xlm);

        if (!$price_in_usd) {
            error_log('REAL8 Price Updater: Failed to fetch XLM to USD conversion rate.');
            return;
        }

        // Step 3: Update WooCommerce product
        $this->update_woocommerce_price($price_in_usd);
    }

    /**
     * Fetch the latest REAL8 price from Horizon
     * @return float|null
     */
    private function fetch_real8_price() {
        try {
            $client = new ApiClient(ApiClient::NETWORK_PUBLIC);
            $asset_code = 'REAL8';
            $issuer = 'GBVYYQ7XXRZW6ZCNNCL2X2THNPQ6IM4O47HAA25JTAG7Z3CXJCQ3W4CD';

            $trades = $client->getTrades([
                'selling_asset_type' => 'credit_alphanum4',
                'selling_asset_code' => $asset_code,
                'selling_asset_issuer' => $issuer,
                'buying_asset_type' => 'native',
                'limit' => 1,
                'order' => 'desc'
            ]);

            $latest_trade = $trades->getRecords()[0] ?? null;
            if ($latest_trade) {
                return $latest_trade->getPrice()->getNumerator() / $latest_trade->getPrice()->getDenominator();
            }
        } catch (Exception $e) {
            error_log('REAL8 Price Updater: Horizon API error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Convert XLM price to USD using CoinGecko
     * @param float $price_in_xlm
     * @return float|null
     */
    private function convert_to_usd($price_in_xlm) {
        try {
            $response = wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=stellar&vs_currencies=usd', [
                'timeout' => 10
            ]);

            if (is_wp_error($response)) {
                error_log('REAL8 Price Updater: CoinGecko API error: ' . $response->get_error_message());
                return null;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            $xlm_to_usd = $data['stellar']['usd'] ?? null;

            if ($xlm_to_usd) {
                return $price_in_xlm * $xlm_to_usd;
            }
        } catch (Exception $e) {
            error_log('REAL8 Price Updater: CoinGecko API error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Update the WooCommerce product price
     * @param float $price_in_usd
     */
    private function update_woocommerce_price($price_in_usd) {
        // Get product ID from settings
        $product_id = get_option('real8_product_id', '');

        if (!$product_id) {
            error_log('REAL8 Price Updater: Product ID not set in settings.');
            return;
        }

        $product = wc_get_product($product_id);
        if ($product) {
            $formatted_price = round($price_in_usd, 2);
            $product->set_regular_price($formatted_price);
            $product->save();
            error_log("REAL8 Price Updater: Updated REAL8 price to $formatted_price USD");
        } else {
            error_log('REAL8 Price Updater: Product not found for ID ' . $product_id);
        }
    }

    /**
     * Deactivate the cron event on plugin deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('real8_price_update_event');
    }
}

// Register deactivation hook
register_deactivation_hook(REAL8_PLUGIN_DIR . 'real8-price-updater.php', ['Real8_Price_Updater', 'deactivate']);
