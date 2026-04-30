<?php
if (!defined('ABSPATH')) {
    exit;
}

// registro la pagina settings sotto Impostazioni > Findomestic Simulator
add_action('admin_menu', 'aa_findo_sim_register_menu');
function aa_findo_sim_register_menu() {
    add_options_page(
        __('Findomestic Simulator', 'aa-findomestic-simulator'),
        __('Findomestic Simulator', 'aa-findomestic-simulator'),
        'manage_options',
        'aa-findomestic-simulator',
        'aa_findo_sim_render_settings_page'
    );
}

// registro le option con sanitization callback dedicato
add_action('admin_init', 'aa_findo_sim_register_settings');
function aa_findo_sim_register_settings() {
    register_setting(
        'aa_findomestic_simulator_group',
        AA_FINSIM_OPTION_KEY,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'aa_findo_sim_sanitize_settings',
            'default'           => array(),
        )
    );
}

function aa_findo_sim_sanitize_settings($input) {
    $clean = array();
    if (!is_array($input)) {
        $input = array();
    }
    $clean['enable_product_simulator']      = (isset($input['enable_product_simulator']) && $input['enable_product_simulator'] === 'yes') ? 'yes' : 'no';
    $clean['tvei']                          = isset($input['tvei']) ? sanitize_text_field($input['tvei']) : '';
    $clean['prf']                           = isset($input['prf']) ? sanitize_text_field($input['prf']) : '';
    $clean['product_simulator_button_label']= isset($input['product_simulator_button_label']) ? sanitize_text_field($input['product_simulator_button_label']) : '';

    // se l'utente abilita il simulatore senza personalizzare la label, scrivo il default così non vede un bottone vuoto
    if ($clean['enable_product_simulator'] === 'yes' && $clean['product_simulator_button_label'] === '') {
        $clean['product_simulator_button_label'] = __('Simula rate Findomestic', 'aa-findomestic-simulator');
    }

    $clean['findomestic_disclaimer']        = isset($input['findomestic_disclaimer']) ? wp_kses_post($input['findomestic_disclaimer']) : '';
    return $clean;
}

// rendering pagina settings
function aa_findo_sim_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $settings = aa_findomestic_get_gateway_settings();
    $enabled  = isset($settings['enable_product_simulator']) ? $settings['enable_product_simulator'] : 'no';
    $tvei     = isset($settings['tvei']) ? $settings['tvei'] : '';
    $prf      = isset($settings['prf']) ? $settings['prf'] : '';
    $label    = isset($settings['product_simulator_button_label']) ? $settings['product_simulator_button_label'] : '';
    $disc     = isset($settings['findomestic_disclaimer']) ? $settings['findomestic_disclaimer'] : '';
    ?>
    <div class="wrap aa-findo-sim-wrap">
        <h1><?php esc_html_e('AA - Findomestic Simulator', 'aa-findomestic-simulator'); ?></h1>
        <p class="description">
            <?php esc_html_e("AA - Findomestic Simulator mostra in pagina prodotto un pulsante che apre un modale con la simulazione rate Findomestic per l'importo del prodotto.", 'aa-findomestic-simulator'); ?>
        </p>

        <div class="aa-findo-sim-layout">
            <div class="aa-findo-sim-main">
                <form method="post" action="options.php">
                    <?php settings_fields('aa_findomestic_simulator_group'); ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Abilita simulatore rate', 'aa-findomestic-simulator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="aa-findo-sim-enable" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[enable_product_simulator]" value="yes" <?php checked($enabled, 'yes'); ?> />
                                        <?php esc_html_e('Mostra il pulsante simulatore rate nella pagina prodotto', 'aa-findomestic-simulator'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="aa-findo-sim-tvei"><?php esc_html_e('TVEI', 'aa-findomestic-simulator'); ?></label></th>
                                <td>
                                    <input type="text" id="aa-findo-sim-tvei" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[tvei]" value="<?php echo esc_attr($tvei); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e("Codice TVEI fornito da Findomestic per identificare l'esercente.", 'aa-findomestic-simulator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="aa-findo-sim-prf"><?php esc_html_e('PRF', 'aa-findomestic-simulator'); ?></label></th>
                                <td>
                                    <input type="text" id="aa-findo-sim-prf" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[prf]" value="<?php echo esc_attr($prf); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e("Codice PRF fornito da Findomestic per identificare il profilo finanziario.", 'aa-findomestic-simulator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="aa-findo-sim-label"><?php esc_html_e('Label pulsante simulatore', 'aa-findomestic-simulator'); ?></label></th>
                                <td>
                                    <input type="text" id="aa-findo-sim-label" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[product_simulator_button_label]" value="<?php echo esc_attr($label); ?>" class="regular-text" placeholder="<?php esc_attr_e('Simula rate Findomestic', 'aa-findomestic-simulator'); ?>" />
                                    <p class="description"><?php esc_html_e("Testo del pulsante mostrato in pagina prodotto. Se lasciato vuoto verrà usato il valore di default.", 'aa-findomestic-simulator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="aa-findo-sim-disc"><?php esc_html_e('Findomestic Disclaimer', 'aa-findomestic-simulator'); ?></label></th>
                                <td>
                                    <textarea id="aa-findo-sim-disc" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[findomestic_disclaimer]" rows="8" class="large-text"><?php echo esc_textarea($disc); ?></textarea>
                                    <p class="description">
                                        <?php esc_html_e("Messaggio promozionale obbligatorio mostrato in fondo al modale simulatore. Necessario per legge italiana sui finanziamenti (TAEG/TAN richiedono il messaggio pubblicitario completo).", 'aa-findomestic-simulator'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

            <aside class="aa-findo-sim-sidebar">
                <div class="aa-findo-sim-pro-card">
                    <h3><?php esc_html_e('Vuoi anche il gateway al checkout?', 'aa-findomestic-simulator'); ?></h3>
                    <p><?php esc_html_e("AA - Findomestic for WooCommerce (Pro) integra Findomestic come metodo di pagamento al checkout, gestisce automaticamente il flusso di rateizzazione, lo stato dell'ordine e la callback con esito Findomestic.", 'aa-findomestic-simulator'); ?></p>
                    <ul class="aa-findo-sim-pro-features">
                        <li><?php esc_html_e('Simulatore rate Findomestic (singolo prodotto, shop, categoria), posizionamento personalizzabile', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Badge rate tipo: da 35€/mese con Findomestic sotto il prezzo (singolo prodotto, shop, categoria)', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Importo minimo/massimo configurabile', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Disabilita per categoria/prodotto specifico', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Sticky bar mobile con CTA "Finanzia con Findomestic"', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Email post-acquisto con riepilogo finanziamento', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Dashboard merchant con statistiche', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Tracking eventi Google Analytics 4 / Meta Pixel', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Webhook in uscita verso n8n / Zapier / Make', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Policy automatica IVASS / informativa precontrattuale', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Logging dettagliato chiamate API + viewer admin', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Modalità sandbox/test toggle (utile in staging)', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Rate alternative: se Findomestic rifiuta la pratica il plugin può mostrare nell\'email/pagina di esito le alternative di pagamento (Klarna, Scalapay, PayPal in 3 rate) con link diretti ai gateway presenti sul tuo shop', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Compatibilità HPOS (High-Performance Order Storage)', 'aa-findomestic-simulator'); ?></li>
                    </ul>
                    <a href="https://alessioangeloro.it/integrazione-di-findomestic-in-woocommerce" target="_blank" rel="noopener noreferrer" class="button button-primary aa-findo-sim-pro-cta">
                        <?php esc_html_e('Scopri la versione Pro', 'aa-findomestic-simulator'); ?>
                    </a>
                </div>

                <div class="aa-findo-sim-info-card">
                    <h3><?php esc_html_e('Documentazione e FAQ', 'aa-findomestic-simulator'); ?></h3>
                    <p><?php esc_html_e('Trovi la documentazione completa e le risposte alle domande frequenti su AA - Findomestic Simulator e sulla versione Pro nella scheda prodotto.', 'aa-findomestic-simulator'); ?></p>
                    <a href="https://wp.alessioangeloro.it/prodotto/findomestic-per-woocommerce/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Apri docs e FAQ', 'aa-findomestic-simulator'); ?></a>
                </div>
            </aside>
        </div>
    </div>

    <style>
        .aa-findo-sim-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            margin-top: 20px;
        }
        @media (max-width: 1100px) {
            .aa-findo-sim-layout { grid-template-columns: 1fr; }
        }
        .aa-findo-sim-main {
            background: #fff;
            padding: 4px 24px 24px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
        }
        .aa-findo-sim-pro-card,
        .aa-findo-sim-info-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .aa-findo-sim-pro-card {
            background: linear-gradient(135deg, #fff 0%, #fefce8 100%);
            border-color: #f7be38;
        }
        .aa-findo-sim-pro-card h3 {
            margin-top: 0;
            color: #1a1a1a;
            font-size: 16px;
        }
        .aa-findo-sim-pro-features {
            margin: 12px 0 16px;
            padding-left: 0;
            list-style: none;
        }
        .aa-findo-sim-pro-features li {
            padding: 6px 0 6px 22px;
            position: relative;
            font-size: 13px;
            color: #50575e;
        }
        .aa-findo-sim-pro-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #00a32a;
            font-weight: 700;
        }
        .aa-findo-sim-pro-cta {
            display: block;
            text-align: center;
            background: #f7be38 !important;
            border-color: #f7be38 !important;
            color: #1a1a1a !important;
            font-weight: 600 !important;
            padding: 6px 16px !important;
            text-decoration: none !important;
        }
        .aa-findo-sim-pro-cta:hover {
            background: #e3a91e !important;
            border-color: #e3a91e !important;
        }
        .aa-findo-sim-info-card h3 {
            margin-top: 0;
            font-size: 14px;
        }
        .aa-findo-sim-info-card p {
            font-size: 13px;
            color: #50575e;
        }
    </style>
    <?php
}
