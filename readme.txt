=== Simulador de Parcelas para Loja Virtual ===
Contributors: renatofroes  
Tags: woocommerce, parcelamento, mercado pago, pagamentos  
Requires at least: 5.0  
Tested up to: 6.7  
Requires PHP: 7.2  
Stable tag: 1.1.0
License: GPL2  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

== Descrição ==  
Exibe automaticamente uma tabela de parcelamento com base nas condições de pagamento via Mercado Pago (utilizando a API pública), diretamente na página do produto do WooCommerce.

== Serviços externos ==

Este plugin se conecta à API pública do Mercado Pago para obter as condições de parcelamento com base no preço do produto.

Dados enviados:
- O valor do produto (`amount`)
- Um método de pagamento fixo (ex.: `visa`)
- O Access Token configurado pelo administrador da loja

Finalidade:
- Exibir opções de parcelamento em tempo real nas páginas de produto da loja.

Serviço utilizado:
- API de Parcelamento do Mercado Pago  
- Termos de uso: https://www.mercadopago.com.br/ajuda/termos-e-condicoes_194  
- Política de privacidade: https://www.mercadopago.com.br/ajuda/politica-de-privacidade_188

== Instalação ==  
1. Baixe o plugin no WordPress.org.  
2. Vá até **Plugins > Adicionar Novo** e envie o arquivo ZIP.  
3. Ative o plugin e vá até **Configurações > Parcelamento MP**.  

== Capturas de Tela ==  
1. Configuração do plugin no painel do WordPress.  
2. Exibição da tabela na página do produto.  

== Changelog ==
= 1.1.0 =
* NOVO: Exibe informação de parcelamento nos cards de produto (listagens/loops do WooCommerce)
* NOVO: Opção de ativar/desativar a exibição nos cards via painel
* NOVO: Escolha de alinhamento do texto (esquerda, centro, direita)
* NOVO: Personalização da cor do texto com seletor nativo
* NOVO: Personalização do prefixo da frase exibida (ex: "Até", "A partir de")
* MELHORIA: Estilos agora aplicados dinamicamente conforme configurações
* OTIMIZAÇÃO: Uso de cache (transients) para evitar chamadas repetidas à API do Mercado Pago

= 1.0.1 =  
* Versão inicial do plugin.  

== Suporte ==  
Para suporte e contribuições, acesse: https://renatofroes.com.br/contatos  