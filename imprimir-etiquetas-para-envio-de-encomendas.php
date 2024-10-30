<?php
/**
 * Plugin Name: Imprimir Etiquetas para Envio de Encomendas
 * Plugin URI: https://wordpress.org/plugins/imprimir-etiquetas-para-envio-de-encomendas
 * Description: Plugin para imprimir etiquetas de envio de encomendas com o WooCommerce. Está preparado para imprimir 12 etiquetas por folha (A4). O tamanho da etiqueta deverá ser de 105 x 48 mm.
 * Version: 1.0.2
 * Author: Pedro Miguel Martins
 * Author URI: https://pedromartins.com/
 * Text Domain: imprimir-etiquetas-para-envio-de-encomendas
 * Domain Path: /languages
 * Requires at least: 4.7
 * Requires PHP: 7.0
 * License: GPLv3 or later
 * WC requires at least: 3.0
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança: impedir acesso direto.
}

if ( ! class_exists( 'IEE_Imprimir_Etiquetas_Plugin' ) ) :

class IEE_Imprimir_Etiquetas_Plugin {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'iee_imprimir_etiquetas_enqueue_scripts' ) );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'iee_imprimir_etiquetas_add_print_button_to_order_page' ), 30, 1 );
        add_action( 'admin_post_imprimir_etiquetas_imprimir_etiqueta', array( $this, 'iee_imprimir_etiquetas_handle_print_label_request' ) );
    }

    public function iee_imprimir_etiquetas_enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        $allowed_screens = array( 'woocommerce_page_wc-orders', 'shop_order', 'post' );

        if ( in_array( $screen->id, $allowed_screens, true ) ) {
            $script_handle = 'iee-imprimir-etiquetas-admin';
            $style_handle  = 'iee-imprimir-etiquetas-admin-css';

            wp_register_script(
                $script_handle,
                plugins_url( 'assets/ieee-admin.js', __FILE__ ),
                array( 'jquery' ),
                filemtime( plugin_dir_path( __FILE__ ) . 'assets/ieee-admin.js' ),
                true
            );

            wp_register_style(
                $style_handle,
                plugins_url( 'assets/ieee-admin.css', __FILE__ ),
                array(),
                filemtime( plugin_dir_path( __FILE__ ) . 'assets/ieee-admin.css' )
            );

            wp_enqueue_script( $script_handle );
            wp_enqueue_style( $style_handle );
        }
    }

    public function iee_imprimir_etiquetas_add_print_button_to_order_page( $order ) {
        $order_id  = $order->get_id();
        $image_url = plugins_url( 'assets/etiquetas-exemplo.jpg', __FILE__ );

        // Verificação de capacidade
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $nonce = wp_create_nonce( 'imprimir_etiquetas_print_label_nonce' );
        $url   = admin_url(
            'admin-post.php?action=imprimir_etiquetas_imprimir_etiqueta&order_id='
            . $order_id
            . '&start_line=1&_wpnonce='
            . $nonce
        );

        echo '<div class="imprimir-etiquetas">';
        echo '<h3>' . esc_html__( 'Imprimir Etiqueta de Envio', 'imprimir-etiquetas-envio-encomendas' ) . '</h3>';
        echo '<p><strong>'
            . esc_html__( 'Não esquecer:', 'imprimir-etiquetas-envio-encomendas' )
            . '</strong> '
            . esc_html__(
                'Este plugin está preparado para imprimir 12 Etiquetas por Folha, 105 x 48 mm.',
                'imprimir-etiquetas-envio-encomendas'
            )
            . ' <a href="#" id="imprimir_etiquetas_help_link">'
            . esc_html__( 'Saiba mais.', 'imprimir-etiquetas-envio-encomendas' )
            . '</a></p>';
        echo '<div><label for="start_line">'
            . esc_html__( 'Linha de Início:', 'imprimir-etiquetas-envio-encomendas' )
            . '</label>';
        echo '<select id="start_line" name="start_line" onchange="imprimir_etiquetas_update_print_url(this.value, '
            . esc_attr( $order_id ) . ')">';
        for ( $i = 1; $i <= 6; $i++ ) {
            echo '<option value="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</option>';
        }
        echo '</select></div>';
        echo '<a href="' . esc_url( $url )
            . '" class="button button-primary" id="imprimir-etiquetas-print-label-button" target="_blank">'
            . esc_html__( 'Imprimir', 'imprimir-etiquetas-envio-encomendas' )
            . '</a>';

        echo '<div id="imprimir_etiquetas_help_modal" class="imprimir-etiquetas-modal">';
        echo '<div class="imprimir-etiquetas-modal-content">';
        echo '<span class="imprimir-etiquetas-close">&times;</span>';
        echo '<h2>' . esc_html__(
            'Ajuda - Imprimir Etiqueta de Envio',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</h2>';
        echo '<p><strong>' . esc_html__( 'Como funciona?', 'imprimir-etiquetas-envio-encomendas' )
            . '</strong><br/>';
        echo esc_html__(
            'Basta colocar a folha de etiquetas na impressora e selecionar "Imprimir". Pode escolher onde quer começar a impressão, indicando a linha de início.',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</p>';
        echo '<p><strong>' . esc_html__(
            'Qual o tamanho / modelo das etiquetas a usar?',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</strong><br/>';
        echo esc_html__(
            'O plugin está preparado para imprimir folhas A4, com 12 etiquetas no total; cada etiqueta deve ter a medida de 105 x 48 mm. Veja um exemplo abaixo:',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</p>';
        echo '<img src="' . esc_url( $image_url ) . '" alt="'
            . esc_attr__( 'Exemplo de Etiquetas', 'imprimir-etiquetas-envio-encomendas' )
            . '" width="100%" />';
        echo '<p><strong>' . esc_html__(
            'Configurar a impressão',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</strong><br/>';
        echo esc_html__(
            'Em Windows, no painel de impressão, configurar "Tamanho do papel" para A4 e retirar opção "Cabeçalhos e rodapés".',
            'imprimir-etiquetas-envio-encomendas'
        ) . '<br />';
        echo esc_html__(
            'Em macOS, no diálogo de impressão, selecione "A5" como o tamanho do papel. Nas opções de impressão, desmarque a caixa "Imprimir cabeçalhos e rodapés".',
            'imprimir-etiquetas-envio-encomendas'
        ) . '<br />';
        echo esc_html__(
            'Verifique também a orientação do papel na impressora, se tem alguma opção própria para impressão de etiquetas, pois pode variar dependendo da marca e modelo.',
            'imprimir-etiquetas-envio-encomendas'
        ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Enfileirar o script e adicionar o código JavaScript de forma segura
        $script_handle = 'iee-imprimir-etiquetas-admin';

        wp_enqueue_script( $script_handle );

        $base_url = add_query_arg(
            array(
                'action'   => 'imprimir_etiquetas_imprimir_etiqueta',
                'order_id' => $order_id,
                '_wpnonce' => $nonce,
            ),
            admin_url( 'admin-post.php' )
        );

        $script = '
            function imprimir_etiquetas_update_print_url(startLine, orderId) {
                var baseUrl = ' . wp_json_encode( $base_url ) . ';
                var url = baseUrl + "&start_line=" + encodeURIComponent(startLine);
                document.getElementById("imprimir-etiquetas-print-label-button").href = url;
            }
        ';

        wp_add_inline_script( $script_handle, $script );
    }

    public function iee_imprimir_etiquetas_handle_print_label_request() {
        // Verificação de capacidade
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'imprimir-etiquetas-envio-encomendas' ) );
        }

        if (
            isset( $_GET['order_id'] )
            && isset( $_GET['start_line'] )
            && check_admin_referer( 'imprimir_etiquetas_print_label_nonce' )
        ) {
            $order_id   = intval( $_GET['order_id'] );
            $start_line = intval( $_GET['start_line'] );

            if ( $order_id > 0 && $start_line > 0 && $start_line <= 6 ) {
                // Gera o conteúdo das etiquetas
                $this->iee_imprimir_etiquetas_print_labels( array( $order_id ), $start_line );
            } else {
                wp_die(
                    esc_html__(
                        'Erro nos parâmetros de impressão. Verifique se o ID da encomenda e a linha de início são válidos.',
                        'imprimir-etiquetas-envio-encomendas'
                    )
                );
            }
        } else {
            wp_die(
                esc_html__(
                    'Erro de autorização. Nonce inválido ou parâmetros faltantes.',
                    'imprimir-etiquetas-envio-encomendas'
                )
            );
        }
    }

    public function iee_imprimir_etiquetas_print_labels( $order_ids = array(), $start_line = 1 ) {
        if ( empty( $order_ids ) ) {
            return;
        }

        // Suprimir a barra de administração
        add_filter( 'show_admin_bar', '__return_false' );

        // Pegar os dados da loja.
        $store_info = array(
            'logo'           => esc_url( wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) ),
            'name'           => sanitize_text_field( get_bloginfo( 'name' ) ),
            'address_line_1' => wp_kses_post( get_option( 'woocommerce_store_address' ) ),
            'address_line_2' => wp_kses_post( get_option( 'woocommerce_store_address_2' ) ),
            'postcode'       => esc_html( get_option( 'woocommerce_store_postcode' ) ),
            'city'           => esc_html( get_option( 'woocommerce_store_city' ) ),
            'country'        => esc_html( WC()->countries->countries[ get_option( 'woocommerce_default_country' ) ] ),
        );

        echo '<!DOCTYPE html><html><head>';
        // Incluir estilos diretamente
        echo '<style>
        @page { size: A4; margin: 0; }
        body { font-family: Arial, sans-serif; font-size: 9pt; margin: 5mm; padding: 0; box-sizing: border-box; }
        .etiqueta { page-break-inside: avoid; max-height: 48mm; height: 48mm; display: flex; justify-content: space-between; padding: 0; margin: 0; box-sizing: border-box; }
        .coluna { width: 105mm; display: flex; flex-direction: row; align-items: center; flex-wrap: nowrap; padding: 0 5mm; overflow: hidden; }
        .coluna:nth-child(2) { padding: 0 5mm 0 10mm; }
        .coluna img { max-width: 100px; margin-right: 5mm; }
        .linha-vazia { max-height: 48mm; height: 48mm; margin: 0; }
        .margin-top { height: 5mm; }
        </style>';

        // Incluir script diretamente
        echo '<script>window.print();</script>';

        echo '</head><body>';

        for ( $i = 1; $i < $start_line; $i++ ) {
            echo '<div class="linha-vazia"></div>';
        }

        $current_line    = $start_line;
        $labels_per_page = 6;

        foreach ( $order_ids as $order_id ) {
            if ( $current_line > $labels_per_page ) {
                echo '<div style="page-break-after: always;"></div>';
                echo '<div class="margin-top"></div>';
                $current_line = 1;
            }

            $order = wc_get_order( $order_id );

            $customer_info = array(
                'company'   => esc_html( $order->get_billing_company() ),
                'name'      => esc_html( $order->get_formatted_billing_full_name() ),
                'address_1' => esc_html( $order->get_billing_address_1() ),
                'address_2' => esc_html( $order->get_billing_address_2() ),
                'postcode'  => esc_html( $order->get_billing_postcode() ),
                'city'      => esc_html( $order->get_billing_city() ),
                'country'   => esc_html( WC()->countries->countries[ $order->get_billing_country() ] ),
            );

            echo '<div class="etiqueta">';
            echo '<div class="coluna"><div class="col-1">';
            if ( $store_info['logo'] ) {
                echo '<img src="' . esc_url( $store_info['logo'] ) . '" alt="'
                    . esc_attr__( 'Logo', 'imprimir-etiquetas-envio-encomendas' ) . '">';
            }
            echo '</div><div class="col-2">';
            echo '<p>' . esc_html( $store_info['name'] ) . '<br>';
            echo esc_html( $store_info['address_line_1'] ) . '<br>';
            echo esc_html( $store_info['address_line_2'] ) . '<br>';
            echo esc_html( $store_info['postcode'] ) . ' ' . esc_html( $store_info['city'] ) . '<br>';
            echo esc_html( $store_info['country'] ) . '</p></div></div>';

            echo '<div class="coluna"><p>';
            if ( ! empty( $customer_info['company'] ) ) {
                echo esc_html( $customer_info['company'] ) . '<br>';
            }
            echo esc_html( $customer_info['name'] ) . '<br>';
            echo esc_html( $customer_info['address_1'] ) . '<br>';
            if ( ! empty( $customer_info['address_2'] ) ) {
                echo esc_html( $customer_info['address_2'] ) . '<br>';
            }
            echo esc_html( $customer_info['postcode'] ) . ' ' . esc_html( $customer_info['city'] ) . '<br>';
            echo esc_html( $customer_info['country'] ) . '</p></div></div>';

            $current_line++;
        }

        echo '</body></html>';
        exit;
    }

}

new IEE_Imprimir_Etiquetas_Plugin();

endif;
