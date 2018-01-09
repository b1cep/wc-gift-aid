<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('WC_Gift_Aid_Woocommerce')) {

    class WC_Gift_Aid_Woocommerce {

        function __construct() {
            //add enable checkbox
            add_action('woocommerce_product_options_general_product_data', array($this, 'wcgaid_product_enable_checkbox'));
            //save field 
            add_action('woocommerce_process_product_meta', array($this, 'wcgaid_product_enable_checkbox_save'));
            //add gift fields in checkout
            add_filter('woocommerce_after_order_notes', array($this, 'wcgaid_product_checkout_fields'));
            //validate custom fields 
            add_action('woocommerce_checkout_process', array($this, 'wcgaid_product_checkout_fields_validation'));
            //save custom fields
            add_action('woocommerce_checkout_update_order_meta', array($this, 'wcgaid_product_checkout_fields_save'));
            //add plugin script & style
            add_action('wp_enqueue_scripts', array($this, 'wcgaid_product_checkout_scripts'));
            //show gift in thankyou page
            add_action('woocommerce_order_details_after_order_table', array($this, 'woocommerce_order_items_table_show_gift'));
            //show gift data in edit order 
            add_action('add_meta_boxes', array($this, 'wcgaid_order_gift_details'));
            //save order custom fields
            add_action('save_post', array($this, 'wcgaid_order_custom_fields_save'));
        }

        public function wcgaid_product_enable_checkbox() {
            ?>
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox(
                        array(
                            'id' => '_wcgaid_product_enable',
                            'label' => __('Whether or not to enable Gift Aid at checkout', 'wc-gift-aid')
                        )
                );
                ?>
            </div>
            <?php
        }

        public function wcgaid_product_enable_checkbox_save($post_id) {
            $wcgaid_product_enable = isset($_POST['_wcgaid_product_enable']) ? 'yes' : 'no';
            update_post_meta($post_id, '_wcgaid_product_enable', $wcgaid_product_enable);
        }

        public function wcgaid_product_checkout_fields($checkout) {
            //check if gift aid enabled
            $show_gift = false;
            $gift_price = 0;
            $cart_items = WC()->cart->get_cart();
            foreach ($cart_items as $key => $item) {
                $product_id = $item['product_id'];
                if (get_post_meta($product_id, '_wcgaid_product_enable', true) == 'yes') {
                    $show_gift = true;
                }
                //calculate gift price
                $qty = $item['quantity'];
                $product = wc_get_product($product_id);
                $price = $item['line_total'];
                if (get_post_meta($product_id, '_wcgaid_product_enable', true) == 'yes') {
                    $gift_price += round($price * $qty * 0.25, 2);
                }
            }

            if ($show_gift):
                $wcgaid_gift_aid_label = get_option('wcgaid_section_heading');
                if (!$wcgaid_gift_aid_label)
                    $wcgaid_gift_aid_label = 'Reclaim Gift Aid';

                $wcgaid_gift_aid_label = str_replace('{gift_aid}', wc_price($gift_price), $wcgaid_gift_aid_label);

                $wcgaid_gift_aid_label2 = get_option('wcgaid_checkbox_label');
                if (!$wcgaid_gift_aid_label2)
                    $wcgaid_gift_aid_label2 = 'Checkbox Label';

                $description = get_option('wcgaid_description');
                ?>
                <div id="wcgaid_product_checkout_fields">
                    <?php
                    woocommerce_form_field('wcgaid_gift_aid', array(
                        'type' => 'checkbox',
                        'class' => array('wcgaid-gift-aid form-row-wide'),
                        'label' => $wcgaid_gift_aid_label,
                            ), $checkout->get_value('wcgaid_gift_aid'));
                    ?>
                    <div class="wcgaid-gift-aid hide">
                        <?php
                        if ($description):
                            ?>
                            <p><?php echo $description; ?></p>
                            <?php
                        endif;
                        woocommerce_form_field('wcgaid_gift_aid_2', array(
                            'type' => 'checkbox',
                            'class' => array('wcgaid-gift-aid form-row-wide'),
                            'label' => $wcgaid_gift_aid_label2,
                                ), $checkout->get_value('wcgaid_gift_aid_2'));
                        ?>
                    </div>

                    <div class="wcgaid-gift-aid fields">
                        <?php
                        woocommerce_form_field('wcgaid_gift_aid_title', array(
                            'type' => 'text',
                            'class' => array('wcgaid-gift-aid form-row-wide'),
                            'label' => 'Title <span class="edd-required-indicator">*</span>',
                                ), $checkout->get_value('wcgaid_gift_aid_title'));

                        woocommerce_form_field('wcgaid_gift_aid_house', array(
                            'type' => 'text',
                            'class' => array('wcgaid-gift-aid form-row-wide'),
                            'label' => 'House Name/ Number <span class="edd-required-indicator">*</span>',
                                ), $checkout->get_value('wcgaid_gift_aid_house'));

                        woocommerce_form_field('wcgaid_gift_aid_post_code', array(
                            'type' => 'text',
                            'class' => array('wcgaid-gift-aid form-row-wide'),
                            'label' => 'Post Code <span class="edd-required-indicator">*</span>',
                                ), $checkout->get_value('wcgaid_gift_aid_post_code'));
                        ?>
                    </div>
                </div>
                <?php
            endif;
        }

        public function wcgaid_product_checkout_scripts() {
            if (is_checkout()) {
                wp_enqueue_script('wcgaid-script', WCGAID_PLUGIN_URL . '/assets/js/scripts.js', array('jquery'), null, true);
                wp_enqueue_style('wcgaid-style', WCGAID_PLUGIN_URL . '/assets/style/style.css');
            }
        }

        public function wcgaid_product_checkout_fields_validation() {
            if (isset($_POST['wcgaid_gift_aid']) && isset($_POST['wcgaid_gift_aid_2'])) {
                if (!$_POST['wcgaid_gift_aid_title'])
                    wc_add_notice(__('Gift title is a required field.', 'wc-gift-aid'), 'error');

                if (!$_POST['wcgaid_gift_aid_house'])
                    wc_add_notice(__('Gift house name/number is a required field.', 'wc-gift-aid'), 'error');

                if (!$_POST['wcgaid_gift_aid_post_code'])
                    wc_add_notice(__('Gift post code is a required field.', 'wc-gift-aid'), 'error');
            }
        }

        public function wcgaid_product_checkout_fields_save($order_id) {
            if (isset($_POST['wcgaid_gift_aid']) && isset($_POST['wcgaid_gift_aid_2'])) {
                update_post_meta($order_id, '_gift_aid', 'yes');

                if (!empty($_POST['wcgaid_gift_aid_title'])) {
                    update_post_meta($order_id, '_wcgaid_gift_aid_title', sanitize_text_field($_POST['wcgaid_gift_aid_title']));
                }

                if (!empty($_POST['wcgaid_gift_aid_house'])) {
                    update_post_meta($order_id, '_wcgaid_gift_aid_house', sanitize_text_field($_POST['wcgaid_gift_aid_house']));
                }

                if (!empty($_POST['wcgaid_gift_aid_post_code'])) {
                    update_post_meta($order_id, '_wcgaid_gift_aid_post_code', sanitize_text_field($_POST['wcgaid_gift_aid_post_code']));
                }

                //calculate gift price
                $gift_price = 0;
                $cart_items = WC()->cart->get_cart();
                foreach ($cart_items as $key => $item) {
                    $product_id = $item['product_id'];
                    $qty = $item['quantity'];
                    $product = wc_get_product($product_id);
                    $price = $item['line_total'];
                    if (get_post_meta($product_id, '_wcgaid_product_enable', true) == 'yes') {
                        $gift_price += round($price * $qty * 0.25, 2);
                    }
                }

                update_post_meta($order_id, '_wcgaid_gift_price', $gift_price);
            }
        }

        public function woocommerce_order_items_table_show_gift($order) {
            $order_id = $order->get_id();
            $gift = get_post_meta($order_id, '_wcgaid_gift_price', true);
            if ($gift) {
                ?>
                <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                    <tr class="">
                        <th scope="row">
                            <?php _ex('Gift Aid:', 'wc-gift-aid'); ?>
                        </th>

                        <td class="woocommerce-table__product-total product-total">
                            <?php echo wc_price($gift); ?>
                        </td>

                    </tr>
                </table>
                <?php
            }
        }

        public function wcgaid_order_gift_details() {
            global $post;
            $order_id = $post->ID;
            if (get_post_meta($order_id, '_wcgaid_gift_price', true))
                add_meta_box('gift-aid-details', __('Gift Aid Details', 'wc-gift-aid'), array($this, 'wcgaid_order_gift_details_html'), 'shop_order');

            add_meta_box('gift-sponsored-event', __('Sponsored Event', 'wc-gift-aid'), array($this, 'wcgaid_order_gift_sponsored_event_html'), 'shop_order');
        }

        public function wcgaid_order_gift_details_html($order) {
            $order_id = $order->ID;
            ?>
            <p><strong><?php _e('Gift Aid Title', 'wc-gift-aid'); ?>:</strong> <?php echo get_post_meta($order_id, '_wcgaid_gift_aid_title', true); ?></p>
            <p><strong><?php _e('Gift Aid House', 'wc-gift-aid'); ?>:</strong> <?php echo get_post_meta($order_id, '_wcgaid_gift_aid_house', true); ?></p>
            <p><strong><?php _e('Gift Aid Post Code', 'wc-gift-aid'); ?>:</strong> <?php echo get_post_meta($order_id, '_wcgaid_gift_aid_post_code', true); ?></p>
            <p><strong><?php _e('Gift Aid Amount', 'wc-gift-aid'); ?>:</strong> <?php echo wc_price(get_post_meta($order_id, '_wcgaid_gift_price', true)); ?></p>
            <?php
        }

        public function wcgaid_order_gift_sponsored_event_html($order) {
            $order_id = $order->ID;
            ?>
            <div id="edd-sponsored-event">
                <input type="checkbox" <?php if (get_post_meta($order_id, '_wcgaid_sponsored_event', true) == 'yes'): ?>checked="checked"<?php endif; ?> value="yes" name="wcgaid_sponsored_event">
                <span>If checked then sponsored event show yes in export csv.</span>
            </div>
            <?php
            wp_nonce_field('wcgaid_sponsored_event_action', 'wcgaid_sponsored_event_nonce_field');
        }

        public function wcgaid_order_custom_fields_save($post_id) {
            if (!isset($_POST['wcgaid_sponsored_event_nonce_field']) || !wp_verify_nonce($_POST['wcgaid_sponsored_event_nonce_field'], 'wcgaid_sponsored_event_action'))
                return;

            if (isset($_POST['post_type']) && $_POST['post_type'] == "shop_order") {
                if (isset($_POST['wcgaid_sponsored_event'])) {
                    update_post_meta($post_id, '_wcgaid_sponsored_event', 'yes');
                } else {
                    update_post_meta($post_id, '_wcgaid_sponsored_event', 'no');
                }
            }
        }

    }

    //create instance
    $instance = new WC_Gift_Aid_Woocommerce();
}
