<?php

/**
 * Plugin Name:          Postaqui para WooCommerce
 * Description:          Adiciona método de entrega Postaqui à sua loja WooCommerce.
 * Author:               Augesystems, Phellipe K Ribeiro, Diego G P Lopes, Mateus Souza
 * Author URI:           https://www.augesystems.com.br,https://profiles.wordpress.org/pkelbert/,https://profiles.wordpress.org/diegpl/
 * Version:              1.2.0
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 3.0.0
 * WC tested up to:      5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_POSTAQUI_VERSION', '1.2.0');
define('WC_POSTAQUI_PLUGIN_FILE', __FILE__);
define('WC_POSTAQUI_DIR', plugin_dir_path(__FILE__));
define('WC_POSTAQUI_DIR_URL', plugin_dir_url(__FILE__));
define('WC_POSTAQUI_REQUIRED_VERSION', '4.9.5');

if (!class_exists('WC_woocommerce_postaqui')) {
    include_once WC_POSTAQUI_DIR . "/includes/class-api-postaqui.php";
    include_once WC_POSTAQUI_DIR . "/includes/functions-postaqui.php";
    include_once WC_POSTAQUI_DIR . '/includes/class-wc-postaqui.php';
}

register_activation_hook(__FILE__, 'wc_postaqui_activation');

function wc_postaqui_activation()
{

    global $wp_version;

    if (version_compare($wp_version, WC_POSTAQUI_REQUIRED_VERSION, '<')) {
        wp_die("Este plugin requer no mínimo a versão " . WC_POSTAQUI_REQUIRED_VERSION . " do Wordpress");
    }

    if (!function_exists('curl_version')) {
        wp_die("Para a utilização deste plugin é obrigatória a habilitação da extensão CURL do PHP");
    }

}
