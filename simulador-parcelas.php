<?php
/**
 * Plugin Name: Simulador de Parcelas para Loja Virtual
 * Plugin URI: https://renatofroes.com.br/simulador-parcelas
 * Description: Exibe automaticamente uma tabela de parcelamento com base nas condições de pagamento via Mercado Pago (utilizando a API pública), diretamente na página do produto do WooCommerce.
 * Version: 1.0.2
 * Author: Renato Froes
 * Author URI: https://renatofroes.com.br
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simulador-parcelas
 */

if (!defined('ABSPATH')) {
    exit;
}

// ✅ Obtém automaticamente o Token do Mercado Pago do WooCommerce
function simuparc_obter_token() {
    return get_option('simuparc_token', '');
}

// ✅ Obtém as parcelas diretamente da API do Mercado Pago
function simuparc_obter_parcelamento($preco_produto) {
    $access_token = simuparc_obter_token();

    if (empty($access_token)) {
        return '<p>Erro: Token do Mercado Pago não configurado.</p>';
    }

    $url = "https://api.mercadopago.com/v1/payment_methods/installments?amount={$preco_produto}&payment_method_id=visa&access_token={$access_token}";

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return '<p>Erro ao conectar à API do Mercado Pago: ' . esc_html($response->get_error_message()) . '</p>';
    }

    $body = wp_remote_retrieve_body($response);
    $parcelas = json_decode($body, true);

    if (empty($parcelas[0]['payer_costs'])) {
        return '<p>Não há opções de parcelamento disponíveis.</p>';
    }

    return $parcelas[0]['payer_costs'];
}

// ✅ Cria o shortcode para exibir a tabela de parcelamento
function simuparc_render_tabela() {
    global $product;
    if (!$product) return '';

    $preco_produto = $product->get_price();
    $parcelas = simuparc_obter_parcelamento($preco_produto);

    if (!is_array($parcelas)) {
        return '<p>' . esc_html($parcelas) . '</p>';
    }

    $output = '<table class="tabela-parcelamento">';
    $output .= '<tr><th>Parcelas</th><th>Valor</th><th>Total</th></tr>';

    foreach ($parcelas as $parcela) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($parcela['installments']) . 'x</td>';
        $output .= '<td>R$ ' . number_format($parcela['installment_amount'], 2, ',', '.') . '</td>';
        $output .= '<td>R$ ' . number_format($parcela['total_amount'], 2, ',', '.') . '</td>';
        $output .= '</tr>';
    }

    $output .= '</table>';

    return $output;
}
add_shortcode('simuparc_parcelamento', 'simuparc_render_tabela');

// ✅ Adiciona a tabela de parcelamento na página do produto
function simuparc_registrar_hook() {
    remove_action('woocommerce_single_product_summary', 'simuparc_exibir_shortcode', 25);
    remove_action('woocommerce_after_add_to_cart_form', 'simuparc_exibir_shortcode', 10);
    remove_action('woocommerce_after_single_product_summary', 'simuparc_exibir_shortcode', 5);

    $posicao = get_option('simuparc_posicao', 'woocommerce_single_product_summary');
    add_action($posicao, 'simuparc_exibir_shortcode', 25);
}
add_action('init', 'simuparc_registrar_hook');

// ✅ Adiciona automaticamente a tabela na página do produto
function simuparc_exibir_shortcode() {
    echo do_shortcode('[simuparc_parcelamento]');
}

// ✅ Adiciona um menu de configuração no painel do WordPress
function simuparc_config_menu() {
    add_options_page(
        'Simulador de Parcelas',
        'Simulador de Parcelas',
        'manage_options',
        'simuparc_settings',
        'simuparc_config_page'
    );
}
add_action('admin_menu', 'simuparc_config_menu');

// ✅ Cria a interface de configuração no painel do WordPress
function simuparc_config_page() {
    ?>
    <div class="wrap">
        <h1>Tabela de Parcelamento - Mercado Pago</h1>
        <form method="post" action="options.php">
            <?php settings_fields('simuparc_settings'); ?>
            <?php do_settings_sections('simuparc_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simuparc_token">Token do Mercado Pago</label></th>
                    <td>
                        <input type="text" id="simuparc_token" name="simuparc_token" value="<?php echo esc_attr(get_option('simuparc_token', '')); ?>" class="regular-text">
                        <p class="description">Insira seu Access Token do Mercado Pago para conectar a API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="simuparc_posicao">Posição da Tabela</label></th>
                    <td>
                        <select id="simuparc_posicao" name="simuparc_posicao">
                            <option value="woocommerce_single_product_summary" <?php selected(get_option('simuparc_posicao'), 'woocommerce_single_product_summary'); ?>>Abaixo do preço</option>
                            <option value="woocommerce_after_add_to_cart_form" <?php selected(get_option('simuparc_posicao'), 'woocommerce_after_add_to_cart_form'); ?>>Abaixo do botão Comprar</option>
                            <option value="woocommerce_after_single_product_summary" <?php selected(get_option('simuparc_posicao'), 'woocommerce_after_single_product_summary'); ?>>Abaixo da descrição</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvar Configurações'); ?>
        </form>
    </div>
    <?php
}

// ✅ Carrega CSS para estilizar a tabela
function simuparc_enqueue_styles() {
    wp_enqueue_style('simuparc-style', plugin_dir_url(__FILE__) . 'parcelamento-style.css');
}
add_action('wp_enqueue_scripts', 'simuparc_enqueue_styles');

// ✅ Salva as configurações do plugin
function simuparc_register_settings() {
    register_setting('simuparc_settings', 'simuparc_posicao', array(
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('simuparc_settings', 'simuparc_token', array(
        'sanitize_callback' => 'sanitize_text_field'
    ));
}
add_action('admin_init', 'simuparc_register_settings');