<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Init the shipping class
 * @return void
 */
function woocommerce_postaqui_init()
{
    if (!class_exists('WC_woocommerce_postaqui')) {

        class WC_woocommerce_postaqui extends WC_Shipping_Method
        {

            /**
             * Constructor
             * @param integer $instance_id
             */
            public function __construct($instance_id = 0)
            {

                $this->id = 'woocommerce_postaqui';
                $this->instance_id = absint($instance_id);
                $this->title = 'Postaqui';
                $this->method_title = 'Postaqui';
                $this->method_description = 'Calculadora de frete Postaqui';
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                );

                $this->init();
            }

            /**
             * Init instance settings
             * @return void
             */
            public function init()
            {
                $this->init_form_fields();
                $this->init_instance_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            /**
             * Define form fields
             * @return void
             */
            public function init_form_fields()
            {
                $this->instance_form_fields = [
                    'enabled' => [
                        'title' => __('Ativo', 'woocommerce_postaqui'),
                        'type' => 'checkbox',
                        'label' => 'Ativo',
                        'default' => 'yes',
                        'description' => 'Informe se este método de frete é válido'
                    ],
                    'token' => [
                        'title' => __('Seu token de acesso ao Postaqui', 'woocommerce_postaqui'),
                        'type' => 'text',
                        'default' => '',
                        'description' => 'Caso ainda não tenha seu token, entre em contato com a Postaqui'
                    ],
                    'source_zip_code' => [
                        'title' => __('CEP de origem para cálculo'),
                        'type' => 'text',
                        'default' => '00000-000',
                        'class' => 'postaqui_mask_zip_code',
                        'description' => 'Peso mínimo para o cliente poder escolher esta modalidade'
                    ],
                    'show_delivery_time' => [
                        'title' => __('Mostrar prazo de entrega', 'woocommerce_postaqui'),
                        'type' => 'checkbox',
                        'label' => 'Mostrar prazo de entrega',
                        'default' => 'yes',
                        'description' => 'Informe se devemos mostrar o prazo de entrega'
                    ],
                    'show_estimate_on_product_page' => [
                        'title' => __('Calcular frete na página do produto', 'woocommerce_postaqui'),
                        'type' => 'checkbox',
                        'label' => 'Frete na página do produto',
                        'default' => 'yes',
                        'description' => 'Informe se quer que o cliente tenha uma previsão do frete na página do produto'
                    ],
                ];
            }

            /**
             * Render admin options
             * @return void
             */
            function admin_options()
            {

                if (!$this->instance_id) {
                    echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
                }
                echo wp_kses_post(wpautop($this->get_method_description()));
                echo $this->get_admin_options_html();
            }

            /**
             * Calculate shipping
             * @param boolean $package
             * @return void
             */
            public function calculate_shipping($package = false)
            {

                $use_this_method = $this->validate_shipping();
                if (!$use_this_method) return false;

                $product_statements = $this->sumarize_package($package);

                $token = $this->instance_settings['token'];

                $postaqui = new Postaqui($token);
                $postaqui->set_weight($product_statements['total_weight']);
                $postaqui->set_source_zip_code($this->instance_settings['source_zip_code']);
                $postaqui->set_target_zip_code($package['destination']['postcode']);
                $postaqui->set_package_value($product_statements['total_value']);
                $postaqui->set_width($product_statements['maximum_width']);
                $postaqui->set_height($product_statements['maximum_height']);
                $postaqui->set_length($product_statements['maximum_length']);
                $postaqui->calculate_shipping();

                $received_rates = $postaqui->get_rates();

                if (isset($received_rates->error)) {
                    echo "<pre>";
                    print_r($received_rates->message);
                    echo "</pre>";
                    return;
                }

                if (count($received_rates) == 0) return;

                $show_delivery_time = $this->instance_settings['show_delivery_time'];

                foreach ($received_rates as $rate) {

                    // Display delivery.
                    $meta_delivery_time = [];
                    $prazo_em_dias = preg_replace("/[^0-9]/", "", $rate->deadline);

                    $meta_delivery = array(
                        '_postaqui_id' => $rate->_id,
                        '_type_send' => $rate->type_send,
                        '_postaqui_token' => $this->instance_settings['token']
                    );

                    if ('yes' === $show_delivery_time) $meta_delivery['_postaqui_delivery_estimate'] = intval($prazo_em_dias);

                    $prazo_texto = "";
                    if ('yes' === $show_delivery_time) $prazo_texto = " (" . $rate->deadline . ")";

                    $rates = [
                        'id' => 'woocommerce_postaqui_' . $rate->name,
                        'label' => $rate->name . $prazo_texto,
                        // 'cost' => $rate->price_postaqui,
                        'cost' => $rate->price_finish,
                        'meta_data' => $meta_delivery
                    ];

                    $this->add_rate($rates, $package);
                }
            }

            /**
             * Forecast shipping on product
             * @param boolean $postaqui_product
             * @return void
             */
            public function forecast_shipping($postaqui_product = false)
            {
                global $product;
                global $woocommerce;

                if ($this->instance_settings['enabled'] != 'yes') return;
                if ($this->instance_settings['show_estimate_on_product_page'] != 'yes') return;

                $token = $this->instance_settings['token'];
                $height = (int)preg_replace("/[^0-9]/", "", $product->get_height());
                $width = (int)preg_replace("/[^0-9]/", "", $product->get_width());
                $length = (int)preg_replace("/[^0-9]/", "", $product->get_length());

                $dimensions = [];
                $dimensions[] = wc_get_dimension($length, 'cm');
                $dimensions[] = wc_get_dimension($width, 'cm');
                $dimensions[] = wc_get_dimension($height, 'cm');

                sort($dimensions);

                $length = $this->min_package_length($dimensions[2]);
                $width = $this->min_package_width($dimensions[1]);
                $height = $this->min_package_height($dimensions[0]);

                $weight = wc_get_weight($product->get_weight(), 'kg');
                $price = $product->get_price();

                if ($weight == 0 || $height == 0 || $width == 0 || $length == 0) return;

                $product_link = $product->get_permalink();

                if (isset($_POST['postaqui_forecast_zip_code'])) {
                    $target_zip_code = $_POST['postaqui_forecast_zip_code'];
                } else {
                    $shipping_zip_code = $woocommerce->customer->get_shipping_postcode();
                    if (trim($shipping_zip_code) != "") {
                        $target_zip_code = $shipping_zip_code;
                    } else {
                        $target_zip_code = $woocommerce->customer->get_billing_postcode();
                    }
                }

                ?>
                <style>
                    .as-row {
                        margin: 0px -15px;
                    }
                    .as-row::before,
                    .as-row::after {
                        display: table;
                        content: ' ';
                    }
                    .as-row::after {
                        clear: both;
                    }
                    .as-col-xs-12,
                    .as-col-sm-4,
                    .as-col-sm-8,
                    .as-col-md-9,
                    .as-col-md-3,
                    .as-col-sm-12,
                    .as-col-md-12 {
                        position: relative;
                        min-height: 1px;
                        padding-right: 15px;
                        padding-left: 15px;
                    }
                    .as-col-xs-12 {
                        float: left;
                        width: 100%;
                    }

                    @media (min-width:600px) {

                        .as-col-sm-4,
                        .as-col-sm-8,
                        .as-col-sm-12 {
                            float: left;
                        }
                        .as-col-sm-4 {
                            width: 33.33%;
                        }
                        .as-col-sm-8 {
                            width: 66.33%;
                        }
                        .as-col-sm-12 {
                            width: 100%;
                        }
                    }

                    @media (min-width:992px) {
                        .as-col-md-3,
                        .as-col-md-9,
                        .as-col-md-12 {
                            float: left;
                        }
                        .as-col-md-3 {
                            width: 25%;
                        }
                        .as-col-md-9 {
                            width: 75%
                        }
                        .as-col-md-12 {
                            width: 100%;
                        }
                    }

                    .postaqui_shipping_forecast_form {
                        padding-top: 20px;
                    }
                    .postaqui_shipping_forecast_form input {
                        max-width: 100% !important;
                        text-align: center;
                        height: 42px;
                    }
                    .postaqui_shipping_forecast_table {
                        padding: 20px 0px;
                    }
                    .postaqui_shipping_forecast_table table {
                        width: 100%;
                    }
                    .woocommerce div.product form.cart div.quantity,
                    .woocommerce div.product form.cart .button {
                        top: auto;
                    }
                </style>
                <div style='clear:both;'></div>
                <div class='postaqui_shipping_forecast_form as-row'>
                    <div class=''>
                        <form method='post' action='{$product_link}' id='postaqui_shipping_forecast'>
                            <div class=''>
                                <div class='as-col-md-3 as-col-sm-4 as-col-xs-12'>
                                    <input type='text' value='{$target_zip_code}' class=postaqui_mask_zip_code' name='postaqui_forecast_zip_code' />
                                </div>
                                <div class='as-col-md-9 as-col-sm-8 as-col-xs-12'>
                                    <button type='submit' id='postaqui_shipping_forecast_submit' class='single_add_to_cart_button button alt'>Calcular frete</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php

                if (trim($target_zip_code) == "") return;

                $target_zip_code = preg_replace("/[^0-9]/", "", $target_zip_code);
                $source_zip_code = preg_replace("/[^0-9]/", "", $this->instance_settings['source_zip_code']);
                // $source_zip_code = get_option('woocommerce_store_postcode');

                $rates = [];

                $postaqui = new Postaqui($token);
                $postaqui->set_weight($weight);
                $postaqui->set_source_zip_code($source_zip_code);
                $postaqui->set_target_zip_code($target_zip_code);
                $postaqui->set_package_value($price);
                $postaqui->set_width($width);
                $postaqui->set_height($height);
                $postaqui->set_length($length);

                $postaqui->calculate_shipping();
                $received_rates = $postaqui->get_rates();

                if (isset($received_rates->error)) {
                    echo "<pre>";
                    echo "<p>" . $received_rates->message . "</p>";
                    echo "</pre>";
                    return;
                }

                if (count($received_rates) == 0) return [];

                $show_delivery_time = $this->instance_settings['show_delivery_time'];
                $rates = [];

                foreach ($received_rates as $rate) {

                    $prazo_texto = ('yes' === $show_delivery_time) ? " (" . $rate->deadline . ")" : "";

                    $rate_item = [];
                    $rate_item['label'] = $rate->name . $prazo_texto;
                    $rate_item['cost'] = wc_price($rate->price_finish);

                    $rates[] = $rate_item;
                }

                if (count($rates) == 0) return;
                ?>

                <div class='postaqui_shipping_forecast_table as-row'>
                    <div class='as-col-xs-12 as-col-sm-12 as-col-md-12'>
                        <table>
                            <thead>
                                <tr>
                                    <th>Modalidade de envio pelo Postaqui</th>
                                    <th>Custo estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rates as $rate) {
                                    echo "<tr>";
                                    echo "<td>" . $rate['label'] . "</td>";
                                    echo "<td>" . $rate['cost'] . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
            }

            /**
             * Validate shipping
             * @return boolean
             */
            private function validate_shipping()
            {
                if ($this->instance_settings['enabled'] != 'yes') return false;
                return true;
            }

            /**
             * Retrieve the ming package width
             * @param int $width
             * @return int
             */
            private function min_package_width($width)
            {
                return ((float) $width > 11) ? $width : 11;
            }

            /**
             * Retrieve the ming package height
             * @param int $height
             * @return int
             */
            private function min_package_height($height)
            {
                return ((float) $height > 2) ? $height : 2;
            }

            /**
             * Retrieve the ming package length
             * @param int $length
             * @return int
             */
            private function min_package_length($length)
            {
                return ((float) $length > 16) ? $length : 16;
            }

            /**
             * Sumarize package data
             * @param array $package
             * @return array
             */
            private function sumarize_package($package)
            {

                $package_values = [
                    'total_weight' => 0,
                    'total_value' => 0,
                    'maximum_length' => 0,
                    'maximum_width' => 0,
                    'maximum_height' => 0
                ];

                foreach ($package['contents'] as $item) {

                    $product = $item['data'];

                    $height = (int)preg_replace("/[^0-9]/", "", $product->get_height());
                    $width = (int)preg_replace("/[^0-9]/", "", $product->get_width());
                    $length = (int)preg_replace("/[^0-9]/", "", $product->get_length());

                    $dimensions = [];
                    $dimensions[] = wc_get_dimension($length, 'cm');
                    $dimensions[] = wc_get_dimension($width, 'cm');
                    $dimensions[] = wc_get_dimension($height, 'cm');

                    sort($dimensions);

                    $length = $dimensions[2];
                    $width = $dimensions[1];
                    $height = $dimensions[0];

                    $weight = wc_get_weight($product->get_weight(), 'kg');
                    $package_values['total_weight'] += $weight * $item['quantity'];

                    $value = $item['line_total'];
                    $package_values['total_value'] += $value;
                    $package_values['maximum_length'] += $length * $item['quantity'];

                    if ($width > $package_values['maximum_width']) {
                        $package_values['maximum_width'] = $width;
                    }
                    if ($height > $package_values['maximum_height']) {
                        $package_values['maximum_height'] = $height;
                    }
                }

                $package_values['maximum_width'] = $this->min_package_width($package_values['maximum_width']);
                $package_values['maximum_height'] = $this->min_package_height($package_values['maximum_height']);
                $package_values['maximum_length'] = $this->min_package_length($package_values['maximum_length']);

                return $package_values;
            }
        }
    }
}
add_action('woocommerce_shipping_init', 'woocommerce_postaqui_init');

/**
 * Add postaqui as shipping option on WooCommerce
 * @param array $methods
 * @return array
 */
function add_woocommerce_postaqui($methods)
{
    $methods['woocommerce_postaqui'] = 'WC_woocommerce_postaqui';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_woocommerce_postaqui');
