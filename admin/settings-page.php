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
    $logo_url = AA_FINSIM_URL . 'assets/images/findomestic_logo.png';
    ?>
    <div class="wrap aa-tw">
        <div class="aa-tw-header">
            <div class="aa-tw-header-brand">
                <img src="<?php echo esc_url($logo_url); ?>" alt="Findomestic" class="aa-tw-logo" />
                <div class="aa-tw-header-text">
                    <h1 class="aa-tw-h1"><?php esc_html_e('Findomestic Simulator', 'aa-findomestic-simulator'); ?></h1>
                    <p class="aa-tw-subtitle"><?php esc_html_e('Mostra in pagina prodotto un pulsante che apre il modale con la simulazione rate Findomestic.', 'aa-findomestic-simulator'); ?></p>
                </div>
            </div>
            <div class="aa-tw-header-version">v<?php echo esc_html(AA_FINSIM_VERSION); ?></div>
        </div>

        <div class="aa-tw-grid">
            <div class="aa-tw-main">
                <form method="post" action="options.php" class="aa-tw-card">
                    <?php settings_fields('aa_findomestic_simulator_group'); ?>

                    <div class="aa-tw-card-header">
                        <h2 class="aa-tw-h2"><?php esc_html_e('Configurazione', 'aa-findomestic-simulator'); ?></h2>
                        <p class="aa-tw-card-subtitle"><?php esc_html_e('Inserisci le credenziali fornite da Findomestic e abilita il simulatore in pagina prodotto.', 'aa-findomestic-simulator'); ?></p>
                    </div>

                    <div class="aa-tw-card-body">
                        <div class="aa-tw-toggle-row">
                            <label class="aa-tw-toggle">
                                <input type="checkbox" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[enable_product_simulator]" value="yes" <?php checked($enabled, 'yes'); ?> />
                                <span class="aa-tw-toggle-track"><span class="aa-tw-toggle-thumb"></span></span>
                            </label>
                            <div class="aa-tw-toggle-text">
                                <strong><?php esc_html_e('Abilita simulatore rate', 'aa-findomestic-simulator'); ?></strong>
                                <span><?php esc_html_e('Mostra il pulsante simulatore in ogni pagina prodotto.', 'aa-findomestic-simulator'); ?></span>
                            </div>
                        </div>

                        <div class="aa-tw-divider"></div>

                        <div class="aa-tw-field">
                            <label class="aa-tw-label" for="aa-findo-sim-tvei">TVEI</label>
                            <input type="text" id="aa-findo-sim-tvei" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[tvei]" value="<?php echo esc_attr($tvei); ?>" class="aa-tw-input" />
                            <p class="aa-tw-help"><?php esc_html_e("Codice TVEI fornito da Findomestic per identificare l'esercente.", 'aa-findomestic-simulator'); ?></p>
                        </div>

                        <div class="aa-tw-field">
                            <label class="aa-tw-label" for="aa-findo-sim-prf">PRF</label>
                            <input type="text" id="aa-findo-sim-prf" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[prf]" value="<?php echo esc_attr($prf); ?>" class="aa-tw-input" />
                            <p class="aa-tw-help"><?php esc_html_e("Codice PRF fornito da Findomestic per identificare il profilo finanziario.", 'aa-findomestic-simulator'); ?></p>
                        </div>

                        <div class="aa-tw-field">
                            <label class="aa-tw-label" for="aa-findo-sim-label"><?php esc_html_e('Label pulsante simulatore', 'aa-findomestic-simulator'); ?></label>
                            <input type="text" id="aa-findo-sim-label" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[product_simulator_button_label]" value="<?php echo esc_attr($label); ?>" class="aa-tw-input" placeholder="<?php esc_attr_e('Simula rate Findomestic', 'aa-findomestic-simulator'); ?>" />
                            <p class="aa-tw-help"><?php esc_html_e('Testo del pulsante in pagina prodotto. Se vuoto, viene usato il valore di default.', 'aa-findomestic-simulator'); ?></p>
                        </div>

                        <div class="aa-tw-field">
                            <label class="aa-tw-label" for="aa-findo-sim-disc"><?php esc_html_e('Findomestic Disclaimer', 'aa-findomestic-simulator'); ?></label>
                            <textarea id="aa-findo-sim-disc" name="<?php echo esc_attr(AA_FINSIM_OPTION_KEY); ?>[findomestic_disclaimer]" rows="8" class="aa-tw-textarea"><?php echo esc_textarea($disc); ?></textarea>
                            <p class="aa-tw-help"><?php esc_html_e('Messaggio promozionale obbligatorio mostrato in fondo al modale simulatore. Necessario per legge italiana sui finanziamenti (TAEG/TAN richiedono il messaggio pubblicitario completo).', 'aa-findomestic-simulator'); ?></p>
                        </div>
                    </div>

                    <div class="aa-tw-card-footer">
                        <button type="submit" class="aa-tw-btn aa-tw-btn-primary">
                            <?php esc_html_e('Salva impostazioni', 'aa-findomestic-simulator'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <aside class="aa-tw-sidebar">
                <div class="aa-tw-card aa-tw-card-pro">
                    <div class="aa-tw-card-header">
                        <span class="aa-tw-badge"><?php esc_html_e('Versione Pro', 'aa-findomestic-simulator'); ?></span>
                        <h3 class="aa-tw-h3"><?php esc_html_e('Vuoi anche il gateway al checkout?', 'aa-findomestic-simulator'); ?></h3>
                        <p class="aa-tw-card-subtitle"><?php esc_html_e('AA - Findomestic for WooCommerce (Pro) integra Findomestic come metodo di pagamento al checkout, gestisce automaticamente il flusso di rateizzazione, lo stato dell\'ordine e la callback con esito.', 'aa-findomestic-simulator'); ?></p>
                    </div>
                    <ul class="aa-tw-features">
                        <li><?php esc_html_e('Simulatore rate (singolo prodotto, shop, categoria) con posizionamento personalizzabile', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Badge rate "da 35€/mese con Findomestic" sotto il prezzo', 'aa-findomestic-simulator'); ?></li>
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
                        <li><?php esc_html_e('Rate alternative quando Findomestic rifiuta (Klarna, Scalapay, PayPal)', 'aa-findomestic-simulator'); ?></li>
                        <li><?php esc_html_e('Compatibilità HPOS (High-Performance Order Storage)', 'aa-findomestic-simulator'); ?></li>
                    </ul>
                    <a href="https://alessioangeloro.it/integrazione-di-findomestic-in-woocommerce" target="_blank" rel="noopener noreferrer" class="aa-tw-btn aa-tw-btn-cta">
                        <?php esc_html_e('Scopri la versione Pro', 'aa-findomestic-simulator'); ?>
                        <span class="aa-tw-arrow">→</span>
                    </a>
                </div>

                <div class="aa-tw-card aa-tw-card-info">
                    <h3 class="aa-tw-h3"><?php esc_html_e('Documentazione e FAQ', 'aa-findomestic-simulator'); ?></h3>
                    <p><?php esc_html_e('Trovi la documentazione completa e le risposte alle domande frequenti su Findomestic Simulator e sulla versione Pro nella scheda prodotto.', 'aa-findomestic-simulator'); ?></p>
                    <a href="https://wp.alessioangeloro.it/prodotto/findomestic-per-woocommerce/" target="_blank" rel="noopener noreferrer" class="aa-tw-link"><?php esc_html_e('Apri docs e FAQ', 'aa-findomestic-simulator'); ?> →</a>
                </div>
            </aside>
        </div>
    </div>

    <style>
        /* CSS custom in stile Tailwind, niente dipendenze esterne */
        .aa-tw {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            margin: 20px 20px 20px 0;
            max-width: 1280px;
        }
        .aa-tw * { box-sizing: border-box; }

        /* Header */
        .aa-tw-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .aa-tw-header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .aa-tw-logo {
            height: 48px;
            width: auto;
            display: block;
            background: #ffffff;
            padding: 4px 8px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .aa-tw-header-text { display: flex; flex-direction: column; gap: 4px; }
        .aa-tw-h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .aa-tw-subtitle {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }
        .aa-tw-header-version {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, monospace;
        }

        /* Grid layout */
        .aa-tw-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
        }
        @media (max-width: 1100px) {
            .aa-tw-grid { grid-template-columns: 1fr; }
        }

        /* Card */
        .aa-tw-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .aa-tw-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .aa-tw-card-body { padding: 24px; }
        .aa-tw-card-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .aa-tw-h2 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .aa-tw-h3 {
            margin: 0 0 8px;
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
        }
        .aa-tw-card-subtitle {
            margin: 0;
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        /* Toggle */
        .aa-tw-toggle-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .aa-tw-toggle {
            position: relative;
            display: inline-block;
            flex-shrink: 0;
            cursor: pointer;
        }
        .aa-tw-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .aa-tw-toggle-track {
            display: block;
            width: 44px;
            height: 24px;
            background: #cbd5e1;
            border-radius: 999px;
            transition: background 0.2s;
            position: relative;
        }
        .aa-tw-toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .aa-tw-toggle input:checked + .aa-tw-toggle-track {
            background: #007a3d;
        }
        .aa-tw-toggle input:checked + .aa-tw-toggle-track .aa-tw-toggle-thumb {
            transform: translateX(20px);
        }
        .aa-tw-toggle-text { display: flex; flex-direction: column; gap: 2px; }
        .aa-tw-toggle-text strong { font-size: 14px; color: #0f172a; }
        .aa-tw-toggle-text span { font-size: 13px; color: #64748b; }

        /* Divider */
        .aa-tw-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 24px 0;
        }

        /* Field */
        .aa-tw-field { margin-bottom: 20px; }
        .aa-tw-field:last-child { margin-bottom: 0; }
        .aa-tw-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .aa-tw-input,
        .aa-tw-textarea {
            display: block;
            width: 100%;
            max-width: 540px;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #0f172a;
            background: #fff;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .aa-tw-textarea {
            max-width: 100%;
            resize: vertical;
            min-height: 140px;
            line-height: 1.5;
        }
        .aa-tw-input:focus,
        .aa-tw-textarea:focus {
            outline: none;
            border-color: #007a3d;
            box-shadow: 0 0 0 3px rgba(0,122,61,0.15);
        }
        .aa-tw-help {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }

        /* Buttons */
        .aa-tw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background 0.15s, border-color 0.15s, transform 0.05s;
        }
        .aa-tw-btn:active { transform: translateY(1px); }
        .aa-tw-btn-primary {
            background: #007a3d;
            color: #ffffff;
            border-color: #007a3d;
        }
        .aa-tw-btn-primary:hover {
            background: #00632f;
            color: #ffffff;
            border-color: #00632f;
        }
        .aa-tw-btn-cta {
            background: #f7be38;
            color: #1f2937;
            border-color: #f7be38;
            width: 100%;
            padding: 11px 18px;
            margin-top: 16px;
        }
        .aa-tw-btn-cta:hover {
            background: #e3a91e;
            border-color: #e3a91e;
            color: #1f2937;
        }
        .aa-tw-arrow { transition: transform 0.15s; }
        .aa-tw-btn-cta:hover .aa-tw-arrow { transform: translateX(2px); }

        /* Pro card */
        .aa-tw-card-pro {
            border: 1px solid #fcd34d;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
        }
        .aa-tw-card-pro .aa-tw-card-header {
            border-bottom-color: #fcd34d;
            padding-bottom: 16px;
        }
        .aa-tw-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #f7be38;
            color: #1f2937;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 999px;
            margin-bottom: 10px;
        }

        /* Features list */
        .aa-tw-features {
            list-style: none;
            padding: 16px 24px 0;
            margin: 0;
        }
        .aa-tw-features li {
            position: relative;
            padding: 8px 0 8px 26px;
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
            border-bottom: 1px solid #fef3c7;
        }
        .aa-tw-features li:last-child { border-bottom: 0; }
        .aa-tw-features li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12px;
            width: 16px;
            height: 16px;
            background: #007a3d;
            mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'><path fill='black' d='M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0l-3.5-3.5a1 1 0 011.4-1.4l2.8 2.8 6.8-6.8a1 1 0 011.4 0z'/></svg>") no-repeat center / contain;
            -webkit-mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'><path fill='black' d='M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0l-3.5-3.5a1 1 0 011.4-1.4l2.8 2.8 6.8-6.8a1 1 0 011.4 0z'/></svg>") no-repeat center / contain;
        }
        .aa-tw-card-pro .aa-tw-card-pro,
        .aa-tw-card-pro form { padding: 0 24px 24px; }
        .aa-tw-card-pro > a { margin: 0 24px 24px; display: flex; }

        /* Info card */
        .aa-tw-card-info { padding: 20px 24px; }
        .aa-tw-card-info h3 { margin-top: 0; }
        .aa-tw-card-info p {
            margin: 0 0 12px;
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }
        .aa-tw-link {
            color: #007a3d;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .aa-tw-link:hover { color: #00632f; }

        /* Reset stili WP ereditati */
        .aa-tw .form-table { display: none; }
        .aa-tw input[type="checkbox"]:focus { box-shadow: none; }
    </style>
    <?php
}
