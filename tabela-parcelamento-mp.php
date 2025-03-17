<?php
/**
 * Plugin Name: Tabela de Parcelamento Mercado Pago
 * Plugin URI: https://renatofroes.com.br/tabela-parcelamento-mercado-pago
 * Description: Exibe automaticamente uma tabela de parcelamento do Mercado Pago na página do produto do WooCommerce.
 * Version: 1.0.1.1
 * Author: Renato Froes
 * Author URI: https://renatofroes.com.br
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tabela-parcelamento-mp
 */

if (!defined('ABSPATH')) {
    exit;
}

// ✅ Obtém automaticamente o Token do Mercado Pago do WooCommerce
function obter_token_mercado_pago() {
    return get_option('parcelamento_mp_token', '');
}

// ✅ Obtém as parcelas diretamente da API do Mercado Pago
function obter_parcelamento_mercado_pago($preco_produto) {
    $access_token = obter_token_mercado_pago();

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
function tabela_parcelamento_produto() {
    global $product;
    if (!$product) return '';

    $preco_produto = $product->get_price();
    $parcelas = obter_parcelamento_mercado_pago($preco_produto);

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
add_shortcode('parcelamento_mercado_pago', 'tabela_parcelamento_produto');

// ✅ Adiciona a tabela de parcelamento na página do produto
function exibir_tabela_parcelamento_dinamico() {
    remove_action('woocommerce_single_product_summary', 'exibir_tabela_parcelamento', 25);
    remove_action('woocommerce_after_add_to_cart_form', 'exibir_tabela_parcelamento', 10);
    remove_action('woocommerce_after_single_product_summary', 'exibir_tabela_parcelamento', 5);

    $posicao = get_option('parcelamento_mp_posicao', 'woocommerce_single_product_summary');
    add_action($posicao, 'exibir_tabela_parcelamento', 25);
}
add_action('init', 'exibir_tabela_parcelamento_dinamico');

// ✅ Adiciona automaticamente a tabela na página do produto
function exibir_tabela_parcelamento() {
    echo do_shortcode('[parcelamento_mercado_pago]');
}

// ✅ Adiciona um menu de configuração no painel do WordPress
function configuracao_parcelamento_mp_menu() {
    add_options_page(
        'Tabela de Parcelamento MP',
        'Parcelamento MP',
        'manage_options',
        'parcelamento_mp',
        'configuracao_parcelamento_mp_pagina'
    );
}
add_action('admin_menu', 'configuracao_parcelamento_mp_menu');

// ✅ Cria a interface de configuração no painel do WordPress
function configuracao_parcelamento_mp_pagina() {
    ?>
    <div class="wrap">
        <h1>Tabela de Parcelamento - Mercado Pago</h1>
        <form method="post" action="options.php">
            <?php settings_fields('parcelamento_mp_settings'); ?>
            <?php do_settings_sections('parcelamento_mp_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="parcelamento_mp_token">Token do Mercado Pago</label></th>
                    <td>
                        <input type="text" id="parcelamento_mp_token" name="parcelamento_mp_token" value="<?php echo esc_attr(get_option('parcelamento_mp_token', '')); ?>" class="regular-text">
                        <p class="description">Insira seu Access Token do Mercado Pago para conectar a API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="parcelamento_mp_posicao">Posição da Tabela</label></th>
                    <td>
                        <select id="parcelamento_mp_posicao" name="parcelamento_mp_posicao">
                            <option value="woocommerce_single_product_summary" <?php selected(get_option('parcelamento_mp_posicao'), 'woocommerce_single_product_summary'); ?>>Abaixo do preço</option>
                            <option value="woocommerce_after_add_to_cart_form" <?php selected(get_option('parcelamento_mp_posicao'), 'woocommerce_after_add_to_cart_form'); ?>>Abaixo do botão Comprar</option>
                            <option value="woocommerce_after_single_product_summary" <?php selected(get_option('parcelamento_mp_posicao'), 'woocommerce_after_single_product_summary'); ?>>Abaixo da descrição</option>
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
function carregar_estilos_parcelamento() {
    wp_enqueue_style('parcelamento-css', plugin_dir_url(__FILE__) . 'parcelamento-style.css');
}
add_action('wp_enqueue_scripts', 'carregar_estilos_parcelamento');

// ✅ Adiciona opções de configuração no painel do WordPress
function configuracao_parcelamento_mp_opcoes() {
    register_setting('parcelamento_mp_settings', 'parcelamento_mp_posicao');
}

function salvar_configuracoes_parcelamento_mp() {
    register_setting('parcelamento_mp_settings', 'parcelamento_mp_posicao');
    register_setting('parcelamento_mp_settings', 'parcelamento_mp_token');
}
add_action('admin_init', 'salvar_configuracoes_parcelamento_mp');