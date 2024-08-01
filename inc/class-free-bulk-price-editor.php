<?php
class Free_Bulk_Price_Editor {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_fbpe_update_prices', array($this, 'update_prices'));
        add_action('wp_ajax_nopriv_fbpe_update_prices', array($this, 'update_prices')); // Optional if needed for non-logged-in users

    }

    public function add_admin_menu() {
        add_menu_page(
            'Bulk Price Editor',
            'Bulk Price Editor',
            'manage_options',
            'bulk-price-editor',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        include(FBPE_PLUGIN_PATH . 'views/settings-page.php');
    }

    public function register_settings() {
        register_setting('fbpe_settings_group', 'fbpe_price_settings', array($this, 'sanitize_settings'));

        add_settings_section('fbpe_main_section', 'Bulk Price Settings', null, 'bulk-price-editor');

        add_settings_field('fbpe_formula_field', 'Formula', array($this, 'type_formula_callback'), 'bulk-price-editor', 'fbpe_main_section');
        add_settings_field('fbpe_type_field', 'Type', array($this, 'type_field_callback'), 'bulk-price-editor', 'fbpe_main_section');
        add_settings_field('fbpe_price_field', 'Price Adjustment', array($this, 'price_field_callback'), 'bulk-price-editor', 'fbpe_main_section');
    }

    public function type_formula_callback(){
        $options = get_option('fbpe_price_settings');
        $selected_formula = isset($options['formula']) ? esc_attr($options['formula']) : '';
        ?>
        <select name="fbpe_price_settings[formula]" id="fbpe-formula-type">
            <option disabled selected>Select Formula</option>
            <option value="0" <?php selected($selected_formula, '0'); ?>>Increment</option>
            <option value="1" <?php selected($selected_formula, '1'); ?>>Decrement</option>
            <option value="2" <?php selected($selected_formula, '2'); ?>>Multiply</option>
            <option value="3" <?php selected($selected_formula, '3'); ?>>Divide</option>
        </select>
        <?php
    }

    public function type_field_callback(){
        $options = get_option('fbpe_price_settings');
        $selected_type = isset($options['type']) ? esc_attr($options['type']) : '';
        ?>
        <select name="fbpe_price_settings[type]" id="fbpe-type">
            <option disabled selected>Select Type</option>
            <option value="0" <?php selected($selected_type, '0'); ?>>Fixed</option>
            <option value="1" <?php selected($selected_type, '1'); ?>>Percentage</option>
        </select>
        <?php
    }

    public function price_field_callback() {
        $options = get_option('fbpe_price_settings');
        ?>
        <input type="text" name="fbpe_price_settings[price]" value="<?php echo isset($options['price']) ? esc_attr($options['price']) : ''; ?>" />
        <p class="description">Enter the price adjustment amount. E.g., -10 for a $10 decrease, 20 for a $20 increase.</p>
        <?php
    }

    public function sanitize_settings($settings) {
        $sanitized_settings = array();
        $sanitized_settings['formula'] = isset($settings['formula']) ? sanitize_text_field($settings['formula']) : '';
        $sanitized_settings['type'] = isset($settings['type']) ? sanitize_text_field($settings['type']) : '';
        $sanitized_settings['price'] = isset($settings['price']) ? floatval($settings['price']) : 0;
        return $sanitized_settings;
    }

    public function update_prices() {
        check_ajax_referer('fbpe_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have sufficient permissions.'));
            return;
        }

        if (!self::is_woocommerce_active()) {
            wp_send_json_error(array('message' => 'WooCommerce is not activated.'));
            return;
        }

        $price_adjustment = isset($_POST['price']) ? floatval(sanitize_text_field($_POST['price'])) : 0;
        $adjustment_type = isset($_POST['type']) ? intval(sanitize_text_field($_POST['type'])) : 0;
        $formula = isset($_POST['formula']) ? intval(sanitize_text_field($_POST['formula'])) : 0;
        $batch_size = 20; 
        $offset = isset($_POST['offset']) ? intval(sanitize_text_field($_POST['offset'])) : 0;

        $args = array(
            'post_type'   => 'product',
            'posts_per_page' => $batch_size,
            'offset'      => $offset,
        );

        $products = get_posts($args);
        $total_products = isset($_POST['total']) ? intval(sanitize_text_field($_POST['total'])) : count(get_posts(array('post_type' => 'product', 'posts_per_page' => -1))); // Total products count

        if (empty($products)) {
            wp_send_json_success(array('message' => 'All products have been updated.', 'progress' => 100));
            return;
        }

        $processed_count = $offset + count($products);

        $updated_products = array();

        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            $product_name = $wc_product->get_name();

            if ($wc_product->is_type('simple')) {
                $regular_price = $wc_product->get_regular_price();
                $sale_price = $wc_product->get_sale_price();

                switch ($formula) {
                    case 0: 
                        if ($adjustment_type == 0) { 
                            $wc_product->set_regular_price($regular_price + $price_adjustment);
                            $wc_product->set_sale_price($sale_price ? $sale_price + $price_adjustment : $price_adjustment);
                        } else if ($adjustment_type == 1) { 
                            $adjustment_amount = $regular_price * ($price_adjustment / 100);
                            $wc_product->set_regular_price($regular_price + $adjustment_amount);
                            $sale_price_amount = $sale_price ? $sale_price * ($price_adjustment / 100) : 0;
                            $wc_product->set_sale_price($sale_price + $sale_price_amount);
                        }
                        break;
                    case 1: 
                        if ($adjustment_type == 0) { 
                            $wc_product->set_regular_price($regular_price - $price_adjustment);
                            $wc_product->set_sale_price($sale_price ? $sale_price - $price_adjustment : $price_adjustment);
                        } else if ($adjustment_type == 1) { 
                            $adjustment_amount = $regular_price * ($price_adjustment / 100);
                            $wc_product->set_regular_price($regular_price - $adjustment_amount);
                            $sale_price_amount = $sale_price ? $sale_price * ($price_adjustment / 100) : 0;
                            $wc_product->set_sale_price($sale_price - $sale_price_amount);
                        }
                        break;
                    case 2: 
                        $wc_product->set_regular_price($regular_price * $price_adjustment);
                        if ($sale_price) {
                            $wc_product->set_sale_price($sale_price * $price_adjustment);
                        }
                        break;
                    case 3: 
                        $wc_product->set_regular_price($regular_price / $price_adjustment);
                        if ($sale_price) {
                            $wc_product->set_sale_price($sale_price / $price_adjustment);
                        }
                        break;
                }

                $wc_product->save();
            }

            if ($wc_product->is_type('variable')) {
                $variations = $wc_product->get_children();

                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    $regular_price = $variation->get_regular_price();
                    $sale_price = $variation->get_sale_price();

                    switch ($formula) {
                        case 0: 
                            if ($adjustment_type == 0) { 
                                $variation->set_regular_price($regular_price + $price_adjustment);
                                $variation->set_sale_price($sale_price ? $sale_price + $price_adjustment : $price_adjustment);
                            } else if ($adjustment_type == 1) { 
                                $adjustment_amount = $regular_price * ($price_adjustment / 100);
                                $variation->set_regular_price($regular_price + $adjustment_amount);
                                $sale_price_amount = $sale_price ? $sale_price * ($price_adjustment / 100) : 0;
                                $variation->set_sale_price($sale_price + $sale_price_amount);
                            }
                            break;
                        case 1: 
                            if ($adjustment_type == 0) { 
                                $variation->set_regular_price($regular_price - $price_adjustment);
                                $variation->set_sale_price($sale_price ? $sale_price - $price_adjustment : $price_adjustment);
                            } else if ($adjustment_type == 1) { 
                                $adjustment_amount = $regular_price * ($price_adjustment / 100);
                                $variation->set_regular_price($regular_price - $adjustment_amount);
                                $sale_price_amount = $sale_price ? $sale_price * ($price_adjustment / 100) : 0;
                                $variation->set_sale_price($sale_price - $sale_price_amount);
                            }
                            break;
                        case 2: 
                            $variation->set_regular_price($regular_price * $price_adjustment);
                            if ($sale_price) {
                                $variation->set_sale_price($sale_price * $price_adjustment);
                            }
                            break;
                        case 3: 
                            $variation->set_regular_price($regular_price / $price_adjustment);
                            if ($sale_price) {
                                $variation->set_sale_price($sale_price / $price_adjustment);
                            }
                            break;
                    }

                    $variation->save();
                }
            }

            $updated_products[] = $product_name;
        }

        $next_offset = $offset + $batch_size;
        $remaining_products = $total_products - $next_offset;
        $progress = ($next_offset / $total_products) * 100;

        if ($remaining_products > 0) {
            wp_send_json_success(array(
                'message' => 'Batch processed. Continue processing...',
                'next_offset' => $next_offset,
                'total' => $total_products,
                'progress' => $progress,
                'updated_products' => $updated_products
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'All products have been updated.',
                'progress' => 100,
                'updated_products' => $updated_products
            ));
        }
    }

    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    public static function activate() {
    }

    public static function deactivate() {
        remove_menu_page('bulk-price-editor');
    }
}

