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
            public function admin_options()
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
             * Validate shipping
             * @return boolean
             */
            public function validate_shipping()
            {
                if ($this->instance_settings['enabled'] != 'yes') return false;
                return true;
            }

            /**
             * Retrieve the ming package width
             * @param int $width
             * @return int
             */
            public function min_package_width($width)
            {
                return ((float) $width > 11) ? $width : 11;
            }

            /**
             * Retrieve the ming package height
             * @param int $height
             * @return int
             */
            public function min_package_height($height)
            {
                return ((float) $height > 2) ? $height : 2;
            }

            /**
             * Retrieve the ming package length
             * @param int $length
             * @return int
             */
            public function min_package_length($length)
            {
                return ((float) $length > 16) ? $length : 16;
            }

            /**
             * Sumarize package data
             * @param array $package
             * @return array
             */
            public function sumarize_package($package)
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
