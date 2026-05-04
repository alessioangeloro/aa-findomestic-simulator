<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('aa_findomestic_get_gateway_settings')) {
    function aa_findomestic_get_gateway_settings() {
        $settings = get_option('aa_findomestic_simulator_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }
        return $settings;
    }
}

if (!function_exists('aa_findomestic_get_gateway_credentials')) {
    function aa_findomestic_get_gateway_credentials() {
        $settings = aa_findomestic_get_gateway_settings();

        $tvei = isset($settings['tvei']) ? sanitize_text_field($settings['tvei']) : '';
        $prf  = isset($settings['prf']) ? sanitize_text_field($settings['prf']) : '';

        return [
            'tvei' => $tvei,
            'prf'  => $prf,
        ];
    }
}

if (!function_exists('aa_findomestic_is_product_simulator_enabled')) {
    function aa_findomestic_is_product_simulator_enabled() {
        $settings = aa_findomestic_get_gateway_settings();
        return (isset($settings['enable_product_simulator']) && $settings['enable_product_simulator'] === 'yes');
    }
}

if (!function_exists('aa_findomestic_get_product_simulator_button_label')) {
    function aa_findomestic_get_product_simulator_button_label() {
        $settings = aa_findomestic_get_gateway_settings();
        $label = isset($settings['product_simulator_button_label']) ? sanitize_text_field($settings['product_simulator_button_label']) : '';
        if ($label === '') {
            $label = __('Simula rate Findomestic', 'aa-findomestic-simulator');
        }
        return $label;
    }
}

if (!function_exists('aa_findomestic_get_disclaimer_html')) {
    function aa_findomestic_get_disclaimer_html() {
        // placeholder: la vera implementazione è nel file principale, questa non dovrebbe mai girare
        return '';
    }
}

if (!function_exists('aa_findomestic_format_amount_for_api_from_product')) {
    function aa_findomestic_format_amount_for_api_from_product($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            return '';
        }

        $price_display = (float) wc_get_price_to_display($product);
        if ($price_display <= 0) {
            return '';
        }

        return number_format($price_display, 2, ',', '');
    }
}

if (!function_exists('aa_findomestic_format_amount_cents_from_product')) {
    function aa_findomestic_format_amount_cents_from_product($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            return 0;
        }

        $price_display = (float) wc_get_price_to_display($product);
        if ($price_display <= 0) {
            return 0;
        }

        return (int) round($price_display * 100);
    }
}

if (!function_exists('aa_add_findomestic_simulation_block_single_product')) {
    function aa_add_findomestic_simulation_block_single_product() {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        if (!aa_findomestic_is_product_simulator_enabled()) {
            return;
        }

        global $product;

        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        $creds = aa_findomestic_get_gateway_credentials();
        $tvei  = $creds['tvei'];
        $prf   = $creds['prf'];

        if ($tvei === '' || $prf === '') {
            return;
        }

        $product_id  = $product->get_id();
        $is_variable = $product->is_type('variable') ? 1 : 0;

        $btn_label = aa_findomestic_get_product_simulator_button_label();

        $amount_api = '';
        $amount_cents = 0;

        if ($is_variable === 0) {
            $amount_api   = aa_findomestic_format_amount_for_api_from_product($product);
            $amount_cents = aa_findomestic_format_amount_cents_from_product($product);

            if ($amount_cents < 100000) {
                return;
            }
        }

        $disclaimer_html = aa_findomestic_get_disclaimer_html();
        $logo_url = AA_FINSIM_URL . 'assets/images/findomestic_logo.png';

        echo '<div class="aa-findomestic-simulator-wrap">';

        // messaggio di errore inline mostrato sopra il bottone
        echo '<div class="aa-findomestic-inline-message" style="display:none;"></div>';

        echo '<button type="button" class="button aa-findomestic-simulate" data-product-id="' . esc_attr($product_id) . '" data-is-variable="' . esc_attr($is_variable) . '" data-amount-api="' . esc_attr($amount_api) . '" data-amount-cents="' . esc_attr($amount_cents) . '">' . esc_html($btn_label) . '</button>';

        echo '<div class="aa-findomestic-modal" aria-hidden="true">';
        echo '<div class="aa-findomestic-modal__overlay"></div>';

        echo '<div class="aa-findomestic-modal__content">';

        echo '<div class="aa-findomestic-modal__header">';
        echo '<button type="button" class="aa-findomestic-modal__close" data-aa-findo-close="1">Chiudi</button>';
        echo '<div class="aa-findomestic-modal__brand-top"><img src="' . esc_url($logo_url) . '" alt="Findomestic Logo"></div>';
        echo '</div>';

        echo '<div class="aa-findomestic-modal__message"></div>';
        echo '<div class="aa-findomestic-modal__table"></div>';

        if ($disclaimer_html !== '') {
            echo '<div class="aa-findomestic-modal__footer">';
            echo '<div class="aa-findomestic-modal__disclaimer">';
            echo wp_kses_post( $disclaimer_html );
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }
}

// wrapper per prodotti semplici: salta se variabile (gestito dall'altro hook)
if (!function_exists('aa_add_findomestic_simulation_block_simple')) {
    function aa_add_findomestic_simulation_block_simple() {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        global $product;
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
        if ($product->is_type('variable')) {
            return; // i prodotti variabili usano aa_add_findomestic_simulation_block_variable hook diverso
        }
        aa_add_findomestic_simulation_block_single_product();
    }
}

// wrapper per prodotti variabili: salta se non è variabile
if (!function_exists('aa_add_findomestic_simulation_block_variable')) {
    function aa_add_findomestic_simulation_block_variable() {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        global $product;
        if (!$product || !is_a($product, 'WC_Product') || !$product->is_type('variable')) {
            return;
        }
        aa_add_findomestic_simulation_block_single_product();
    }
}

// prodotto semplice: subito prima del bottone Aggiungi al carrello
add_action('woocommerce_after_add_to_cart_form', 'aa_add_findomestic_simulation_block_simple', 25);
// prodotto variabile: subito sotto la tabella delle select varianti, prima del prezzo della variazione selezionata
add_action('woocommerce_after_add_to_cart_form', 'aa_add_findomestic_simulation_block_variable', 5);