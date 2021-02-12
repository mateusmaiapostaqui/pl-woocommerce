<?php

/**
 * Check if cep is from state
 * @param string $cep
 * @param string $estado
 * @return boolean
 */
function postaqui_is_cep_from_state($cep, $estado)
{

    $cep = substr($cep, 0, 5); // 5 primeiros dígitos
    $cep = (int)$cep;

    switch ($estado) {
        case ('AC'):
            if ($cep > 69900 && $cep < 69999)
                return true;
            break;
        case ('AL'):
            if ($cep > 57000 && $cep < 57999)
                return true;
            break;
        case ('AP'):
            if ($cep > 68900 && $cep < 68999)
                return true;
            break;
        case ('AM'):
            if ($cep > 69400 && $cep < 69899)
                return true;
            break;
        case ('BA'):
            if ($cep > 40000 && $cep < 48999)
                return true;
            break;
        case ('CE'):
            if ($cep > 60000 && $cep < 63999)
                return true;
            break;
        case ('CE'):
            if ($cep > 60000 && $cep < 63999)
                return true;
            break;
        case ('DF'):
            if ($cep > 70000 && $cep < 73699)
                return true;
            break;
        case ('ES'):
            if ($cep > 29000 && $cep < 29999)
                return true;
            break;
        case ('GO'):
            if ($cep > 72800 && $cep < 76799)
                return true;
            break;
        case ('MA'):
            if ($cep > 65000 && $cep < 65999)
                return true;
            break;
        case ('MT'):
            if ($cep > 78000 && $cep < 78899)
                return true;
            break;
        case ('MS'):
            if ($cep > 79000 && $cep < 79999)
                return true;
            break;
        case ('MG'):
            $debug[] = 'MG';
            if ($cep > 30000 && $cep < 39999)
                return true;
            break;
        case ('PA'):
            if ($cep > 66000 && $cep < 68899)
                return true;
            break;
        case ('PB'):
            if ($cep > 58000 && $cep < 58999)
                return true;
            break;
        case ('PR'):
            if ($cep > 80000 && $cep < 87999)
                return true;
            break;
        case ('PE'):
            if ($cep > 50000 && $cep < 56999)
                return true;
            break;
        case ('PI'):
            if ($cep > 64000 && $cep < 64999)
                return true;
            break;
        case ('RJ'):
            if ($cep > 20000 && $cep < 28999)
                return true;
            break;
        case ('RN'):
            if ($cep > 59000 && $cep < 59999)
                return true;
            break;
        case ('RS'):
            if ($cep > 90000 && $cep < 99999)
                return true;
            break;
        case ('RO'):
            if ($cep > 78900 && $cep < 78999)
                return true;
            break;
        case ('RR'):
            if ($cep > 69300 && $cep < 69389)
                return true;
            break;
        case ('SC'):
            if ($cep > 88000 && $cep < 89999)
                return true;
            break;
        case ('SP'):
            if ($cep > 01000 && $cep < 19999)
                return true;
            break;
        case ('SE'):
            if ($cep > 49000 && $cep < 49999)
                return true;
            break;
        case ('TO'):
            if ($cep > 77000 && $cep < 77995)
                return true;
            break;
        default:
            return false;
    }
}

/**
 * Retrieve valid shipping methods for the given zip
 * @param string $cep_destinatario
 * @return array
 */
function postaqui_get_metodos_de_entrega($cep_destinatario)
{

    $metodos_de_entrega = [];
    $delivery_zones = WC_Shipping_Zones::get_zones();

    // Temos zonas de entrega?
    if (count($delivery_zones) < 1) {
        return $metodos_de_entrega;
    }

    $metodos_de_entrega = [
        // 'retirar_no_local' => '',
        // 'frete_gratis' => '',
        'shipping_methods' => []
    ];

    foreach ($delivery_zones as $delivery_zone) {

        if (count($delivery_zone['shipping_methods']) < 1) {
            continue;
        }

        // O CEP informado participa desta delivery zone?
        $cep_destinatario_permitido = false;
        foreach ($delivery_zone['zone_locations'] as $zone_location) {
            switch ($zone_location->type) {
                case 'country':
                    if ($zone_location->code == 'BR')
                        $cep_destinatario_permitido = true;
                    break;
                case 'postcode':

                    $ceps = explode(PHP_EOL, $zone_location->code);
                    foreach ($ceps as $key => $value) {

                        // Range
                        if (strpos($zone_location->code, '...') !== false) {
                            $ranges = explode('...', $value);
                            if (count($ranges) == 2 && is_numeric($ranges[0]) && is_numeric($ranges[1])) {
                                if ($cep_destinatario > (int) $ranges[0] && $cep_destinatario < (int) $ranges[1]) {
                                    $cep_destinatario_permitido = true;
                                }
                            }
                            continue;
                        }

                        // Wildcard
                        if (strpos($zone_location->code, '*') !== false) {
                            $before_wildcard = strtok($zone_location->code, '*');
                            $tamanho_string = strlen($before_wildcard);
                            if (substr($cep_destinatario, 0, $tamanho_string) == $before_wildcard) {
                                $cep_destinatario_permitido = true;
                            }
                        } else {

                            // É uma comparação literal?
                            if ($cep_destinatario == $zone_location->code) {
                                $cep_destinatario_permitido = true;
                            }
                        }
                    }

                    break;
                case 'state':
                    // Estados específicos
                    $tmp = explode(':', $zone_location->code);
                    if ($tmp[0] == 'BR') {
                        if (postaqui_is_cep_from_state($cep_destinatario, $tmp[1])) {
                            $cep_destinatario_permitido = true;
                        }
                    }
                    break;
            }
        }

        // Loop pelas shipping zones
        foreach ($delivery_zone['shipping_methods'] as $key => $shipping_method) {

            // Retirar no local?
            // if (get_class($shipping_method) == 'WC_Shipping_Local_Pickup') {

            //     if ($shipping_method->enabled == 'yes' && $cep_destinatario_permitido) {
            //         $metodos_de_entrega['retirar_no_local'] = 'sim';
            //     }

            //     continue;
            // }

            // Frete Grátis
            // if (get_class($shipping_method) == 'WC_Shipping_Free_Shipping') {
            //     if ($shipping_method->options['exibir_frete_gratis'] == 'true') {
            //         if ($cep_destinatario_permitido) {
            //             if (empty($shipping_method->requires)) {
            //                 $metodos_de_entrega['frete_gratis'] = 'sim';
            //             } elseif ($shipping_method->requires == 'min_amount' || $shipping_method->requires == 'either') {
            //                 if (is_numeric($shipping_method->min_amount)) {
            //                     if ($preco_produto > $shipping_method->min_amount) {
            //                         $metodos_de_entrega['frete_gratis'] = 'sim';
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // }

            // O método atual é permitido?
            if (get_class($shipping_method) ==  "WC_woocommerce_postaqui") {
                // O método atual está habilitado?
                if ($shipping_method->enabled == 'yes') {
                    $metodos_de_entrega[$key] = $shipping_method;
                }
            }
        }
    }

    return $metodos_de_entrega;
}

/**
 * Enqueue scripts into wordpress
 * @return void
 */
function postaqui_enqueue_scripts()
{
    wp_enqueue_script('postaqui_scripts', WC_POSTAQUI_DIR_URL. "/assets/postaqui.js", array(), false, true);
}
add_action('wp_enqueue_scripts', 'postaqui_enqueue_scripts');
add_action('admin_enqueue_scripts', 'postaqui_enqueue_scripts');

/**
 * Print shipping form and estimation on product page
 * @return void
 */
function postaqui_shipping_forecast_on_product_page()
{
    global $product;
    global $woocommerce;

    if( !is_product() ){
        return;
    }

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

    $request_shipping = isset($_POST['postaqui_forecast_zip_code']) ? true : false;
    $metodos_de_entrega = postaqui_get_metodos_de_entrega($target_zip_code);

    // PostAqui shipping not found
    if (count($metodos_de_entrega) == 0){
        return;
    }

    foreach ($metodos_de_entrega as $metodo) {
        if (is_object($metodo) && get_class($metodo) == "WC_woocommerce_postaqui") {
            $postaqui_class = $metodo;
            break;
        }
    }

    if ($postaqui_class->instance_settings['enabled'] != 'yes') return;
    if ($postaqui_class->instance_settings['show_estimate_on_product_page'] != 'yes') return;

    $token = $postaqui_class->instance_settings['token'];
    $height = (int)preg_replace("/[^0-9]/", "", $product->get_height());
    $width = (int)preg_replace("/[^0-9]/", "", $product->get_width());
    $length = (int)preg_replace("/[^0-9]/", "", $product->get_length());

    $dimensions = [];
    $dimensions[] = wc_get_dimension($length, 'cm');
    $dimensions[] = wc_get_dimension($width, 'cm');
    $dimensions[] = wc_get_dimension($height, 'cm');

    sort($dimensions);

    $length = $postaqui_class->min_package_length($dimensions[2]);
    $width = $postaqui_class->min_package_width($dimensions[1]);
    $height = $postaqui_class->min_package_height($dimensions[0]);

    $weight = wc_get_weight($product->get_weight(), 'kg');
    $price = $product->get_price();

    if ($weight == 0 || $height == 0 || $width == 0 || $length == 0){
        return;
    }

    $rates = [];

    if (trim($target_zip_code) != "" && $request_shipping ){

        $show_delivery_time = $postaqui_class->instance_settings['show_delivery_time'];
        $target_zip_code = preg_replace("/[^0-9]/", "", $target_zip_code);
        $source_zip_code = preg_replace("/[^0-9]/", "", $postaqui_class->instance_settings['source_zip_code']);
        // $source_zip_code = get_option('woocommerce_store_postcode');

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

        if (count($received_rates) != 0){;
            foreach ($received_rates as $rate) {

                $prazo_texto = ('yes' === $show_delivery_time) ? " (" . $rate->deadline . ")" : "";

                $rate_item = [];
                $rate_item['label'] = $rate->name . $prazo_texto;
                $rate_item['cost'] = wc_price($rate->price_finish);

                $rates[] = $rate_item;

            }
        }

    }
    ?>
    <style>
    .postaqui_product_form {
        padding-top: 20px;
    }
    .postaqui_product_form form {
        display: flex;
        flex-wrap: wrap;
    }
    .postaqui_product_form_group {
        flex: 0 1 auto;
    }
    .postaqui_product_form input {
        width: 100%;
        max-width: 100px;
        text-align: center;
    }
    .postaqui_product_rates table {
        padding: 20px 0px;
        width: 100%;
    }
    .woocommerce div.product form.cart div.quantity,
    .woocommerce div.product form.cart .button {
        top: auto;
    }
    </style>
    <div style='clear:both;'></div>
    <div class='postaqui_product_form'>

        <form method='POST' action='<?php $product->get_permalink() ?>'>
            <div class="postaqui_product_form_group">
                <input
                    type='text'
                    value='<?php echo $target_zip_code ?>'
                    class='postaqui_mask_zip_code'
                    name='postaqui_forecast_zip_code'
                    placeholder="00000-000" />
            </div>
            <div class="postaqui_product_form_group">
                <button type='submit'
                    class='single_add_to_cart_button button alt'>
                    Calcular frete
                </button>
            </div>
        </form>

        <div class='postaqui_product_rates'>
            <?php if( $rates ): ?>
            <table>
                <thead>
                    <tr>
                        <th>Modalidade de envio</th>
                        <th>Custo estimado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $rate): ?>
                        <tr>
                            <td><?php echo $rate['label'] ?></td>
                            <td><?php echo $rate['cost'] ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>

    </div>
    <?php
}
add_action('woocommerce_after_add_to_cart_form', 'postaqui_shipping_forecast_on_product_page', 50);

/**
 * Print shipping estimation
 * @param object $shipping_method
 * @return void
 */
function postaqui_shipping_delivery_estimate($shipping_method)
{

    $method_name = $shipping_method->get_method_id();
    if ($method_name != 'woocommerce_postaqui') return;

    $meta_data = $shipping_method->get_meta_data();
    $estimate = isset($meta_data['_postaqui_delivery_estimate']) ? intval($meta_data['_postaqui_delivery_estimate']) : 0;

    if ($estimate) {
        echo "<p><small>Entrega em {$estimate} dias úteis</small></p>";
    }
}
// add_action( 'woocommerce_after_shipping_rate', 'postaqui_shipping_delivery_estimate');

/**
 * Send labels and and add order note
 * @param int $order_id
 * @return void
 */
function postaqui_order_processing($order_id)
{

    $order = wc_get_order($order_id);
    $shp_main_data = current($order->get_shipping_methods());

    if (strpos($shp_main_data->get_method_id(), "woocommerce_postaqui") === false) return;

    // Verifica o ID e o tipo de envio
    $meta_data = [];
    foreach ($shp_main_data->get_meta_data() as $meta) {
        $meta_data[$meta->key] = $meta->value;
    }

    $id = $meta_data['_postaqui_id'];
    $shipping_type = $meta_data['_type_send'];
    $token = $meta_data['_postaqui_token'];

    $postaqui = new Postaqui($token);

    // Busca os dados do destinatário
    if ($order->has_shipping_address()) {
        $destinatario_nome = $order->get_formatted_shipping_full_name();
        $destinatario_cnpjCpf = "bazinga!";
        $destinatario_endereco = $order->get_shipping_address_1();
        $destinatario_numero = 0;
        $destinatario_complemento = $order->get_shipping_address_2();
        $destinatario_bairro = "";
        $destinatario_cidade = $order->get_shipping_city();
        $destinatario_uf = $order->get_shipping_state();
        $destinatario_cep = $order->get_shipping_postcode();
        $destinatario_celular = $order->get_billing_phone();
    } else {
        $destinatario_nome = $order->get_formatted_billing_full_name();
        $destinatario_cnpjCpf = "bazinga!";
        $destinatario_endereco = $order->get_billing_address_1();
        $destinatario_numero = 0;
        $destinatario_complemento =  $order->get_billing_address_2();
        $destinatario_bairro = "";
        $destinatario_cidade = $order->get_billing_city();
        $destinatario_uf = $order->get_billing_state();
        $destinatario_cep = $order->get_billing_postcode();
        $destinatario_celular = $order->get_billing_phone();
    }

    $total_value = $order->get_subtotal();
    $total_weight = 0;
    $titles = [];
    $volumes = [];
    $items = $order->get_items();

    foreach ($items as $item) {
        $prod = $item->get_product();
        $weight = wc_get_weight($prod->get_weight(), 'kg');
        $volume = [
            'altura' => wc_get_dimension($prod->get_height(), 'cm'),
            'comprimento' => wc_get_dimension($prod->get_length(), 'cm'),
            'largura' => wc_get_dimension($prod->get_width(), 'cm'),
            'peso' => $weight,
        ];
        $total_weight += $weight;
        $volumes[] = $volume;
        $titles[] = $prod->get_title();
    }

    $lista_de_produtos = implode(",", $titles);

    // Prepara objeto para envio para api
    $label_data = [
        '_id' => $id,
        'conteudo' => $lista_de_produtos,
        'peso_total' => $total_weight,
        'valor_total' => $total_value,
        'tipo_envio' => $shipping_type,
        'destinatario' => [
            'nome' => $destinatario_nome,
            'cnpjCpf' => $postaqui->only_numbers($destinatario_cnpjCpf),
            'endereco' => $destinatario_endereco,
            'numero' => $destinatario_numero,
            'complemento' => $destinatario_complemento,
            'bairro' => $destinatario_bairro,
            'cidade' => $destinatario_cidade,
            'uf' => $destinatario_uf,
            'cep' => $postaqui->only_numbers($destinatario_cep),
            'celular' => $postaqui->only_numbers($destinatario_celular)
        ],
        'volume' => $volumes,
        'pedido' => $order->get_order_number(),
        'origem' => 'woocommerce-postaqui',
        'email' => $order->get_billing_email()
    ];

    $meta_group = $order->get_meta_data();

    if (count($meta_group) > 0) {
        foreach ($meta_group as $meta) {
            if ($meta->key == "_billing_persontype") $persontype = $meta->value;
            if ($meta->key == "_billing_cpf") $cpf = $postaqui->only_numbers($meta->value);
            if ($meta->key == "_billing_cnpj") $cnpj = $postaqui->only_numbers($meta->value);
            if ($meta->key == "_billing_number") $billing_number = $meta->value;
            if ($meta->key == "_billing_neighborhood") $billing_neighborhood = $meta->value;
            if ($meta->key == "_billing_cellphone") $billing_cellphone = $postaqui->only_numbers($meta->value);
            if ($meta->key == "_shipping_number") $shipping_number = $meta->value;
            if ($meta->key == "_shipping_neighborhood") $shipping_neighborhood = $meta->value;
        }
        if (isset($persontype)) {
            if ($persontype == 1) {
                $label_data['destinatario']['cnpjCpf'] = $cpf;
            } else {
                $label_data['destinatario']['cnpjCpf'] = $cnpj;
            }
        }
        if ($order->has_shipping_address()) {
            if (isset($shipping_number)) $label_data['destinatario']['numero'] = $shipping_number;
            if (isset($shipping_neighborhood)) $label_data['destinatario']['bairro'] = $shipping_neighborhood;
        } else {
            if (isset($billing_number)) $label_data['destinatario']['numero'] = $billing_number;
            if (isset($billing_neighborhood)) $label_data['destinatario']['bairro'] = $billing_neighborhood;
        }
        if (isset($billing_cellphone) && trim($billing_cellphone) != "") {
            $label_data['destinatario']['celular'] = $billing_cellphone;
        }
    }

    $return = $postaqui->send_labels($label_data);

    if (isset($return->error)) {
        $order->add_order_note($return->message);
        return;
    }

    $order->add_order_note($return->data->message);
}
add_action('woocommerce_order_status_processing', 'postaqui_order_processing');
