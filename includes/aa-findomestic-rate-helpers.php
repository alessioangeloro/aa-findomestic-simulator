<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('aa_findomestic_generate_uuid4')) {
    function aa_findomestic_generate_uuid4() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('aa_findomestic_get_gateway_settings')) {
    function aa_findomestic_get_gateway_settings() {
        $settings = get_option('aa_findomestic_simulator_settings', []);

        $tvei = isset($settings['tvei']) ? sanitize_text_field($settings['tvei']) : '';
        $prf  = isset($settings['prf']) ? sanitize_text_field($settings['prf']) : '';

        $url_redirect   = isset($settings['urlRedirect']) ? esc_url_raw($settings['urlRedirect']) : '';
        $label_redirect = isset($settings['labelRedirect']) ? sanitize_text_field($settings['labelRedirect']) : '';
        $callback_url   = isset($settings['callBackUrl']) ? esc_url_raw($settings['callBackUrl']) : '';

        return [
            'tvei'          => $tvei,
            'prf'           => $prf,
            'urlRedirect'   => $url_redirect,
            'labelRedirect' => $label_redirect,
            'callBackUrl'   => $callback_url,
        ];
    }
}

if (!function_exists('aa_findomestic_get_dealer_id_from_tvei')) {
    function aa_findomestic_get_dealer_id_from_tvei($tvei) {
        $tvei = preg_replace('/\D/', '', (string) $tvei);
        if (strlen($tvei) > 3 && strpos($tvei, '100') === 0) {
            return substr($tvei, 3);
        }
        return $tvei;
    }
}

if (!function_exists('aa_findomestic_get_product_importo_int')) {
    function aa_findomestic_get_product_importo_int($product_id) {
        $product = wc_get_product($product_id);
        if (!$product || !is_a($product, 'WC_Product')) {
            return 0;
        }

        $price_display = (float) wc_get_price_to_display($product);
        $importo = number_format($price_display, 2, '', '');
        $importo = preg_replace('/\D/', '', $importo);

        if ($importo === '' || !ctype_digit($importo)) {
            return 0;
        }

        return (int) $importo;
    }
}

if (!function_exists('aa_findomestic_format_amount_for_create')) {
    function aa_findomestic_format_amount_for_create($importo_int) {
        $amount = ((int) $importo_int) / 100;
        return number_format($amount, 2, ',', '');
    }
}

if (!function_exists('aa_findomestic_http_collect_cookies')) {
    function aa_findomestic_http_collect_cookies($existing, $response) {
        $existing = is_array($existing) ? $existing : [];
        if (!is_array($response) || !isset($response['cookies']) || !is_array($response['cookies'])) {
            return $existing;
        }

        foreach ($response['cookies'] as $cookie_obj) {
            if ($cookie_obj instanceof WP_Http_Cookie) {
                $existing[] = $cookie_obj;
            }
        }

        return $existing;
    }
}

if (!function_exists('aa_findomestic_http_request')) {
    function aa_findomestic_http_request($method, $url, $args = []) {
        $defaults = [
            'timeout'     => 25,
            'redirection' => 5,
            'sslverify'   => true,
        ];

        $args = wp_parse_args($args, $defaults);

        if ($method === 'GET') {
            return wp_remote_get($url, $args);
        }

        if ($method === 'POST') {
            return wp_remote_post($url, $args);
        }

        $args['method'] = $method;
        return wp_remote_request($url, $args);
    }
}

if (!function_exists('aa_findomestic_ecm_seed_session')) {
    function aa_findomestic_ecm_seed_session($cookies) {
        $url = 'https://secure.findomestic.it/clienti/webapp/ecommerce/';

        return aa_findomestic_http_request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ],
            'cookies' => $cookies,
        ]);
    }
}

if (!function_exists('aa_findomestic_ecm_create_order')) {
    function aa_findomestic_ecm_create_order($payload, $cookies) {
        $url = 'https://secure.findomestic.it/b2c/ecm/v1/order/create';

        return aa_findomestic_http_request('POST', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                'Content-Type' => 'application/json',
                'Origin' => 'https://secure.findomestic.it',
                'Referer' => 'https://secure.findomestic.it/clienti/webapp/ecommerce/',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'X-TechnicalDataCall-RequestId' => aa_findomestic_generate_uuid4(),
                'X-TechnicalDataCall-CallerReference' => 'app-ecommerce',
            ],
            'body' => wp_json_encode($payload),
            'cookies' => $cookies,
        ]);
    }
}

if (!function_exists('aa_findomestic_ecm_bootstrap_token')) {
    function aa_findomestic_ecm_bootstrap_token($token_id, $cookies) {
        $url = 'https://secure.findomestic.it/b2c/ecm/v1/order?token=' . rawurlencode($token_id);

        return aa_findomestic_http_request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://secure.findomestic.it/clienti/webapp/ecommerce/',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'X-TechnicalDataCall-RequestId' => aa_findomestic_generate_uuid4(),
                'X-TechnicalDataCall-CallerReference' => 'app-ecommerce',
            ],
            'cookies' => $cookies,
        ]);
    }
}

if (!function_exists('aa_findomestic_ecm_get_offer')) {
    function aa_findomestic_ecm_get_offer($order_id, $cookies) {
        $url = 'https://secure.findomestic.it/b2c/ecm/v1/order/' . rawurlencode($order_id) . '/offer';

        return aa_findomestic_http_request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://secure.findomestic.it/clienti/webapp/ecommerce/',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'X-TechnicalDataCall-RequestId' => aa_findomestic_generate_uuid4(),
                'X-TechnicalDataCall-CallerReference' => 'app-ecommerce',
            ],
            'cookies' => $cookies,
        ]);
    }
}