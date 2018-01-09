<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('WC_Gift_Aid_Settings')) {

    class WC_Gift_Aid_Settings {

        function __construct() {
            //add setting menu item
            add_action('admin_menu', array($this, 'wcgaid_settings_menu'));
            add_action('admin_init', array($this, 'wcgaid_settings_menu_init'));
        }

        public function wcgaid_settings_menu() {
            add_submenu_page('woocommerce', 'Gift Aid Settings', 'Gift Aid', 'manage_options', 'gift-aid-settings', array($this, 'gift_aid_settings_html'));
            add_submenu_page('woocommerce', 'Gift Aid Report', 'Gift Aid Report', 'manage_options', 'gift-aid-report', array($this, 'gift_aid_report_html'));
        }

        public function gift_aid_settings_html() {
            ?>
            <div class="wrap">
                <h1>Square Recurring Settings</h1>

                <form method="post" action="options.php">
                    <?php settings_fields('wcgaid-settings-group'); ?>
                    <?php do_settings_sections('wcgaid-settings-group'); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Section Heading</th>
                            <td>
                                <input type="text" name="wcgaid_section_heading" value="<?php echo esc_attr(get_option('wcgaid_section_heading')); ?>" />
                                <p>Optional heading for the Gift Aid section at the checkout. Defaults to "Reclaim Gift Aid", use {gift_aid} to show gift price.</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Checkbox Label</th>
                            <td>
                                <input type="text" name="wcgaid_checkbox_label" value="<?php echo esc_attr(get_option('wcgaid_checkbox_label')); ?>" />
                                <p>Label for the checkbox. Must be populated in order for the Gift Aid option to appear at the checkout.</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Description</th>
                            <td>
                                <textarea style="width: 80%;" name="wcgaid_description"><?php echo esc_attr(get_option('wcgaid_description')); ?></textarea>
                                <p>Description Text explaining Gift Aid to the donor. Must be populated in order for the Gift Aid option to appear at the checkout.</p>
                            </td>
                        </tr>


                        <tr valign="top">
                            <th scope="row">Aggregated Donations</th>
                            <td>
                                <input type="text" name="wcgaid_aggregated_donations" value="<?php echo esc_attr(get_option('wcgaid_aggregated_donations')); ?>" />
                                <p>Text that add aggregated donations in export downloads.</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>

                </form>
            </div>
            <?php
        }

        public function wcgaid_settings_menu_init() {
            register_setting('wcgaid-settings-group', 'wcgaid_section_heading');
            register_setting('wcgaid-settings-group', 'wcgaid_checkbox_label');
            register_setting('wcgaid-settings-group', 'wcgaid_description');
            register_setting('wcgaid-settings-group', 'wcgaid_aggregated_donations');
        }

        public function wcgaid_get_orders_by_product($product_id) {

            global $wpdb;

            $raw = "
        SELECT
            `items`.`order_id`,
            MAX(CASE WHEN `itemmeta`.`meta_key` = '_product_id' THEN `itemmeta`.`meta_value` END) AS `product_id`
        FROM
            `{$wpdb->prefix}woocommerce_order_items` AS `items`
        INNER JOIN
            `{$wpdb->prefix}woocommerce_order_itemmeta` AS `itemmeta`
        ON
            `items`.`order_item_id` = `itemmeta`.`order_item_id`
        WHERE
            `items`.`order_item_type` IN('line_item')
        AND
            `itemmeta`.`meta_key` IN('_product_id')
        GROUP BY
            `items`.`order_item_id`
        HAVING
            `product_id` = %d";

            $sql = $wpdb->prepare($raw, $product_id);

            return array_map(function ($data) {
                return wc_get_order($data->order_id);
            }, $wpdb->get_results($sql));
        }

        public function gift_aid_report_html() {
            if (isset($_POST['wcgaid_export_product_orders'])) {
                $product_id = $_POST['wcgaid_export_product_orders'];
                if ($product_id) {
                    //get product orders
                    $orders = $this->wcgaid_get_orders_by_product($product_id);

                    header("Content-Description: File Transfer");
                    header("Content-Type: application/octet-stream");
                    header("Content-Disposition: Attachment; filename=$product_id-orders-export.csv");

                    ob_end_clean();
                    $handle = fopen("php://output", "w");
                    $header_date = array('ID', 'Email', 'First Name', 'Last Name', 'Gift Aid', 'Gift Title', 'Gift House', 'Gift Post Code', 'Donation Date', 'Payment Amount', 'Aggregated Donations', 'Sponsored Event');

                    header('Content-Type: text/csv; charset=utf-8');
                    header("Content-Disposition: Attachment; filename=$product_id-orders-export.csv");

                    fputcsv($handle, $header_date, ',');

                    foreach ($orders as $order) {
                        $order_id = $order->get_id();
                        $gift_price = get_post_meta($order_id, '_wcgaid_gift_price', true);
                        $comleted_date = $order->get_date_completed();
                        $order_data = array(
                            'id' => $order_id,
                            'email' => $order->get_billing_email(),
                            'first' => $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name(),
                            'last' => $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name(),
                            'gift_aid' => $gift_price ? $gift_price : '',
                            'gift_title' => get_post_meta($order_id, '_wcgaid_gift_aid_title', true),
                            'gift_house' => get_post_meta($order_id, '_wcgaid_gift_aid_house', true),
                            'gift_post_code' => get_post_meta($order_id, '_wcgaid_gift_aid_post_code', true),
                            'payment_complete' => $comleted_date ? $comleted_date->format('d/m/Y') : '',
                            'payment_amount' => $gift_price ? $gift_price : '',
                            'gift_aid_donations' => get_option('wcgaid_aggregated_donations'),
                            'gift_aid_sponsored_event' => get_post_meta($order_id, '_wcgaid_sponsored_event', true)
                        );
                        fputcsv($handle, $order_data, ',');
                    }

                    fclose($handle);
                    exit();
                }
            }
            ?>
            <p>Download a CSV of product orders.</p>
            <p></p>
            <form method="post">
                <select name="wcgaid_export_product_orders">
                    <option value="0">Select Product</option>
                    <?php
                    $products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1));

                    foreach ($products as $product) {
                        echo '<option value="' . $product->ID . '">' . $product->post_title . '</option>';
                    }
                    ?>
                </select>                
                <input value="Generate CSV" class="button-secondary" type="submit">
            </form>            
            <?php
        }

    }

    //create instance
    $instance = new WC_Gift_Aid_Settings();
}
