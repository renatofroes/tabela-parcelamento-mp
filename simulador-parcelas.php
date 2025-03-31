<?php
/**
 * Plugin Name: Simulador de Parcelas para Loja Virtual
 * Plugin URI: https://renatofroes.com.br/simulador-parcelas
 * Description: Exibe automaticamente uma tabela de parcelamento com base nas condições de pagamento via Mercado Pago (utilizando a API pública), diretamente na página do produto do WooCommerce.
 * Version: 1.1.0
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
        <h1>Simulador de Parcelamento</h1>
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
                <tr>
                    <th scope="row"><label for="simuparc_exibir_em_loop">Exibir nos cards de produto</label></th>
                    <td>
                        <label for="simuparc_exibir_em_loop">
                            <input type="checkbox" id="simuparc_exibir_em_loop" name="simuparc_exibir_em_loop" value="1" <?php checked(1, get_option('simuparc_exibir_em_loop', 0)); ?> />
                            Ativar exibição do parcelamento abaixo do preço no card do produto
                        </label>
                    </td>
                </tr>
                <tr class="simuparc-alinhamento-row" style="<?php echo get_option('simuparc_exibir_em_loop') ? '' : 'display:none'; ?>">
                    <th scope="row"><label for="simuparc_loop_alinhamento">Alinhamento da informação no card</label></th>
                    <td>
                        <select id="simuparc_loop_alinhamento" name="simuparc_loop_alinhamento">
                            <option value="start" <?php selected(get_option('simuparc_loop_alinhamento'), 'start'); ?>>Esquerda</option>
                            <option value="center" <?php selected(get_option('simuparc_loop_alinhamento'), 'center'); ?>>Centralizado</option>
                            <option value="end" <?php selected(get_option('simuparc_loop_alinhamento'), 'end'); ?>>Direita</option>
                        </select>
                    </td>
                </tr>
                <tr class="simuparc-alinhamento-row" style="<?php echo get_option('simuparc_exibir_em_loop') ? '' : 'display:none'; ?>">
                    <th scope="row"><label for="simuparc_loop_cor_texto">Cor do texto</label></th>
                    <td>
                        <input type="color" id="simuparc_loop_cor_texto" name="simuparc_loop_cor_texto" value="<?php echo esc_attr(get_option('simuparc_loop_cor_texto', '#555555')); ?>" />
                        <p class="description">Selecione uma cor para o texto exibido nos cards.</p>
                    </td>
                </tr>
                <tr class="simuparc-alinhamento-row" style="<?php echo get_option('simuparc_exibir_em_loop') ? '' : 'display:none'; ?>">
                    <th scope="row"><label for="simuparc_loop_prefixo">Prefixo da frase</label></th>
                    <td>
                        <input type="text" id="simuparc_loop_prefixo" name="simuparc_loop_prefixo" value="<?php echo esc_attr(get_option('simuparc_loop_prefixo', 'Até')); ?>" class="regular-text" />
                        <p class="description">Texto que aparece antes da parcela (ex: "Até", "A partir de")</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvar Configurações'); ?>
        </form>
    </div>
    <script>
        document.getElementById('simuparc_exibir_em_loop').addEventListener('change', function() {
            const display = this.checked ? '' : 'none';
            document.querySelectorAll('.simuparc-alinhamento-row').forEach(el => {
                el.style.display = display;
            });
        });
    </script>
    <?php
}

// ✅ Carrega CSS para estilizar a tabela
function simuparc_enqueue_styles() {
    wp_enqueue_style('simuparc-style', plugin_dir_url(__FILE__) . 'parcelamento-style.css');
    
    $alinhamento = get_option('simuparc_loop_alinhamento', 'start');
    $cor_texto = get_option('simuparc_loop_cor_texto', '#555555');
    $custom_css = "
        .simuparc-loop-info {
            font-size: 0.85em;
            color: {$cor_texto};
            margin-top: 5px;
            text-align: {$alinhamento};
        }
    ";
    wp_add_inline_style('simuparc-style', $custom_css);
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
    register_setting('simuparc_settings', 'simuparc_exibir_em_loop', array(
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));
    register_setting('simuparc_settings', 'simuparc_loop_alinhamento', array(
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('simuparc_settings', 'simuparc_loop_cor_texto', array(
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    register_setting('simuparc_settings', 'simuparc_loop_prefixo', array(
        'sanitize_callback' => 'sanitize_text_field'
    ));
}
add_action('admin_init', 'simuparc_register_settings');

// ✅ Exibe informações de parcelamento nos cards de produto
function simuparc_exibir_info_loop() {
    if (!get_option('simuparc_exibir_em_loop')) return;

    global $product;
    if (!$product || !$product->get_price()) return;

    $product_id = $product->get_id();
    $transient_key = "simuparc_parcelas_{$product_id}";
    $parcelas = get_transient($transient_key);

    if (!$parcelas) {
        $parcelas = simuparc_obter_parcelamento($product->get_price());
        if (is_array($parcelas)) {
            set_transient($transient_key, $parcelas, 6 * HOUR_IN_SECONDS);
        } else {
            return;
        }
    }

    if (is_array($parcelas) && !empty($parcelas)) {
        $ultima = end($parcelas);
        $prefixo = get_option('simuparc_loop_prefixo', 'Até');
        echo '<p class="simuparc-loop-info">' . esc_html($prefixo) . ' ' . esc_html($ultima['installments']) . 'x de R$ ' . number_format($ultima['installment_amount'], 2, ',', '.') . '</p>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'simuparc_exibir_info_loop', 12);
?>