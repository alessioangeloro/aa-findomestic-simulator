<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('aa_findomestic_parse_set_cookie_headers')) {
    function aa_findomestic_parse_set_cookie_headers($response, &$cookie_jar) {
        $headers = wp_remote_retrieve_headers($response);
        if (!$headers) {
            return;
        }

        $set_cookie = isset($headers['set-cookie']) ? $headers['set-cookie'] : null;
        if (empty($set_cookie)) {
            return;
        }

        $cookies = is_array($set_cookie) ? $set_cookie : array($set_cookie);

        foreach ($cookies as $cookie_line) {
            if (!is_string($cookie_line) || $cookie_line === '') {
                continue;
            }

            $parts = explode(';', $cookie_line);
            if (empty($parts[0])) {
                continue;
            }

            $name_value = trim($parts[0]);
            if (strpos($name_value, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $name_value, 2);
            $name = trim($name);
            $value = (string) $value;

            if ($name === '') {
                continue;
            }

            $cookie_jar[$name] = $value;
        }
    }
}

if (!function_exists('aa_findomestic_build_cookie_header')) {
    function aa_findomestic_build_cookie_header($cookie_jar) {
        if (!is_array($cookie_jar) || empty($cookie_jar)) {
            return '';
        }

        $pairs = array();
        foreach ($cookie_jar as $name => $value) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $pairs[] = $name . '=' . (string) $value;
        }

        return implode('; ', $pairs);
    }
}

if (!function_exists('aa_findomestic_remote_json_request')) {
    function aa_findomestic_remote_json_request($method, $url, $body, &$cookie_jar, $extra_headers = array()) {
        $request_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : (string) wp_rand(100000, 999999);

        $headers = array(
            'User-Agent'                          => 'Mozilla/5.0',
            'Accept'                              => 'application/json, text/plain, */*',
            'Accept-Language'                     => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Content-Type'                        => 'application/json',
            'Origin'                              => 'https://secure.findomestic.it',
            'Referer'                             => 'https://secure.findomestic.it/clienti/webapp/ecommerce/',
            'Cache-control'                       => 'no-cache',
            'Pragma'                              => 'no-cache',
            'X-TechnicalDataCall-RequestId'        => $request_id,
            'X-TechnicalDataCall-CallerReference' => 'app-ecommerce',
        );

        $cookie_header = aa_findomestic_build_cookie_header($cookie_jar);
        if ($cookie_header !== '') {
            $headers['Cookie'] = $cookie_header;
        }

        if (!empty($extra_headers) && is_array($extra_headers)) {
            $headers = array_merge($headers, $extra_headers);
        }

        $args = array(
            'method'      => strtoupper((string) $method),
            'headers'     => $headers,
            'timeout'     => 25,
            'redirection' => 3,
        );

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'ok'     => false,
                'status' => 0,
                'error'  => $response->get_error_message(),
                'json'   => null,
                'raw'    => null,
            );
        }

        aa_findomestic_parse_set_cookie_headers($response, $cookie_jar);

        $status = (int) wp_remote_retrieve_response_code($response);
        $raw    = (string) wp_remote_retrieve_body($response);

        $json = null;
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }

        return array(
            'ok'     => ($status >= 200 && $status < 300),
            'status' => $status,
            'error'  => '',
            'json'   => $json,
            'raw'    => $raw,
        );
    }
}

if (!function_exists('aa_findomestic_remote_get_html')) {
    function aa_findomestic_remote_get_html($url, &$cookie_jar) {
        $headers = array(
            'User-Agent'      => 'Mozilla/5.0',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-control'   => 'no-cache',
            'Pragma'          => 'no-cache',
        );

        $cookie_header = aa_findomestic_build_cookie_header($cookie_jar);
        if ($cookie_header !== '') {
            $headers['Cookie'] = $cookie_header;
        }

        $response = wp_remote_get($url, array(
            'headers'     => $headers,
            'timeout'     => 20,
            'redirection' => 3,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        aa_findomestic_parse_set_cookie_headers($response, $cookie_jar);

        $status = (int) wp_remote_retrieve_response_code($response);
        return ($status >= 200 && $status < 400);
    }
}

if (!function_exists('aa_findomestic_get_dealer_id_from_tvei')) {
    function aa_findomestic_get_dealer_id_from_tvei($tvei) {
        $tvei = preg_replace('/\D+/', '', (string) $tvei);

        if (strpos($tvei, '100') === 0 && strlen($tvei) > 3) {
            return substr($tvei, 3);
        }

        return $tvei;
    }
}

if (!function_exists('aa_findomestic_normalize_amount_api')) {
    function aa_findomestic_normalize_amount_api($amount_api_raw) {
        $amount_api_raw = (string) $amount_api_raw;
        $amount_api_raw = trim($amount_api_raw);

        if ($amount_api_raw === '') {
            return array('ok' => false, 'amount_float' => 0.0, 'amount_api' => '', 'amount_cents' => '');
        }

        // Teniamo numeri, punti e virgole
        $clean = preg_replace('/[^0-9\.,]/', '', $amount_api_raw);

        // Se ci sono sia . che , assumiamo: . migliaia, , decimali
        if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (strpos($clean, ',') !== false) {
            // solo virgola => decimale
            $clean = str_replace(',', '.', $clean);
        }

        $amount_float = (float) $clean;
        if ($amount_float <= 0) {
            return array('ok' => false, 'amount_float' => 0.0, 'amount_api' => '', 'amount_cents' => '');
        }

        $amount_api = number_format($amount_float, 2, ',', ''); // "4500,00"
        $amount_cents = number_format($amount_float, 2, '', ''); // "450000"

        return array('ok' => true, 'amount_float' => $amount_float, 'amount_api' => $amount_api, 'amount_cents' => $amount_cents);
    }
}

if (!function_exists('aa_findomestic_calcolo_rate_ajax')) {
    function aa_findomestic_calcolo_rate_ajax() {
        if (!isset($_POST['security'])) {
            wp_send_json_error(array('message' => 'Nonce mancante.', 'signature' => 'missing-nonce'));
        }

        check_ajax_referer('aa_findomestic_rate_nonce', 'security');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if ($product_id <= 0) {
            wp_send_json_error(array('message' => 'Prodotto non valido.', 'signature' => 'bad-product'));
        }

        if (!function_exists('aa_findomestic_get_gateway_credentials')) {
            wp_send_json_error(array('message' => 'Credenziali gateway non disponibili.', 'signature' => 'missing-helpers'));
        }

        $creds = aa_findomestic_get_gateway_credentials();
        if (empty($creds['tvei']) || empty($creds['prf'])) {
            wp_send_json_error(array('message' => 'Configurazione Findomestic incompleta (tvei/prf).', 'signature' => 'missing-config'));
        }

        $cart_id = isset($_POST['cartId']) ? sanitize_text_field( wp_unslash( (string) $_POST['cartId'] ) ) : '';
        if ($cart_id === '') {
            $cart_id = 'cart' . (string) wp_rand(1000000, 9999999);
        }

        // IMPORTO: arriva dal frontend (quello reale mostrato/selezionato)
        $amount_api_raw = isset($_POST['amount_api']) ? sanitize_text_field( wp_unslash( (string) $_POST['amount_api'] ) ) : '';
        $amount_norm = aa_findomestic_normalize_amount_api($amount_api_raw);

        if (!$amount_norm['ok']) {
            wp_send_json_error(array('message' => 'Importo non valido.', 'signature' => 'bad-amount'));
        }

        if ((int) $amount_norm['amount_cents'] < 100000) {
            wp_send_json_error(array('message' => 'Importo minimo per la simulazione: 1000,00€.', 'signature' => 'min-amount'));
        }

        $dealer_id = aa_findomestic_get_dealer_id_from_tvei($creds['tvei']);

        $cookie_jar = array();

        aa_findomestic_remote_get_html('https://secure.findomestic.it/clienti/webapp/ecommerce/', $cookie_jar);

        $callbacks = array();

        if (!empty($creds['callBackUrl'])) {
            $callbacks[] = array(
                'use'               => 'ok',
                'action'            => 'redirect',
                'url'               => $creds['callBackUrl'],
                'appendQueryParams' => true,
            );
        }

        if (!empty($creds['urlRedirect'])) {
            $cb = array(
                'use'               => 'ok',
                'action'            => 'manual',
                'url'               => $creds['urlRedirect'],
                'appendQueryParams' => true,
            );

            if (!empty($creds['labelRedirect'])) {
                $cb['label'] = $creds['labelRedirect'];
            }

            $callbacks[] = $cb;
        }

        $payload_create = array(
            'dossierPayload' => array(
                'order' => array(
                    'orderId' => $cart_id,
                ),
                'dossier' => array(
                    'channel'  => 'B2C',
                    'dealerId' => $dealer_id,
                ),
            ),
            'orderLLFPayload' => array(
                'order' => array(
                    'linkone'            => true,
                    'partnerOrderNumber' => $cart_id,
                    'totalAmount'        => $amount_norm['amount_api'], // es: "4500,00"
                ),
                'orderItems' => array(
                    array(
                        'orderItem' => array(
                            'partnerItemId' => $cart_id,
                            'type'          => 'INSTALLMENT',
                        ),
                        'orderItemCreditData' => array(
                            'amount'     => $amount_norm['amount_api'],
                            'prf'        => (string) $creds['prf'],
                            'materialId' => '273',
                        ),
                    ),
                ),
                'callbacks' => $callbacks,
            ),
        );

        $res_create = aa_findomestic_remote_json_request(
            'POST',
            'https://secure.findomestic.it/b2c/ecm/v1/order/create',
            $payload_create,
            $cookie_jar
        );

        if (!$res_create['ok'] || empty($res_create['json']['data']['tokenId'])) {
            wp_send_json_error(array(
                'message'   => 'Impossibile creare order/token (HTTP ' . (int) $res_create['status'] . ').',
                'signature' => 'create-order',
            ));
        }

        $token_id = (string) $res_create['json']['data']['tokenId'];

        $res_order = aa_findomestic_remote_json_request(
            'GET',
            'https://secure.findomestic.it/b2c/ecm/v1/order?token=' . rawurlencode($token_id),
            null,
            $cookie_jar,
            array('Content-Type' => 'application/json')
        );

        if (!$res_order['ok'] || empty($res_order['json']['data']['order']['order']['orderId'])) {
            wp_send_json_error(array(
                'message'   => 'Impossibile ottenere orderId interno.',
                'signature' => 'no-order-id',
            ));
        }

        $internal_order_id = (string) $res_order['json']['data']['order']['order']['orderId'];

        $res_offer = aa_findomestic_remote_json_request(
            'GET',
            'https://secure.findomestic.it/b2c/ecm/v1/order/' . rawurlencode($internal_order_id) . '/offer',
            null,
            $cookie_jar,
            array('Content-Type' => 'application/json')
        );

        if (!$res_offer['ok']) {
            wp_send_json_error(array(
                'message'   => 'Offer non riuscita (HTTP ' . (int) $res_offer['status'] . ')',
                'signature' => 'offer-http',
            ));
        }

        $offer_json = $res_offer['json'];
        $rates = array();

        if (!empty($offer_json['data']['orderItems'][0]['offering']) && is_array($offer_json['data']['orderItems'][0]['offering'])) {
            foreach ($offer_json['data']['orderItems'][0]['offering'] as $row) {
                $duration = isset($row['duration']) ? (int) $row['duration'] : 0;
                if ($duration <= 0) {
                    continue;
                }

                $rates[] = array(
                    'duration'      => $duration,
                    'paymentFee'    => isset($row['paymentFee']) ? (string) $row['paymentFee'] : '',
                    'tan'           => isset($row['tan']) ? (string) $row['tan'] : '',
                    'taeg'          => isset($row['taeg']) ? (string) $row['taeg'] : '',
                    'refunded'      => isset($row['refunded']) ? (string) $row['refunded'] : '',
                    'totalRefunded' => isset($row['totalRefunded']) ? (string) $row['totalRefunded'] : '',
                );
            }
        }

        if (empty($rates)) {
            wp_send_json_error(array(
                'message'   => 'Nessuna rata trovata.',
                'signature' => 'no-rates',
            ));
        }

        wp_send_json_success(array(
            'rates'       => $rates,
            'amount_api'  => $amount_norm['amount_api'],
            'amount_cents'=> $amount_norm['amount_cents'],
            'order_id'    => $internal_order_id,
            'token_id'    => $token_id,
        ));
    }
}

add_action('wp_ajax_aa_findomestic_calcolo_rate_ajax', 'aa_findomestic_calcolo_rate_ajax');
add_action('wp_ajax_nopriv_aa_findomestic_calcolo_rate_ajax', 'aa_findomestic_calcolo_rate_ajax');