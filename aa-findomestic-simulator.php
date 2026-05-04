<?php
/**
 * Plugin Name: AA - Findomestic Simulator
 * Plugin URI: https://alessioangeloro.it/aa-findomestic-simulator
 * Description: Simulatore Rate Findomestic per WooCommerce. Mostra in pagina prodotto un pulsante che apre un modale con la simulazione del finanziamento Findomestic per quell'importo.
 * Version: 1.0.3
 * Author: Alessio Angeloro
 * Author URI: https://alessioangeloro.it
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aa-findomestic-simulator
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 *
 * @package AA_Findomestic_Simulator
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AA_FINSIM_VERSION', '1.0.3');
define('AA_FINSIM_FILE', __FILE__);
define('AA_FINSIM_DIR', plugin_dir_path(__FILE__));
define('AA_FINSIM_URL', plugin_dir_url(__FILE__));
define('AA_FINSIM_BASENAME', plugin_basename(__FILE__));
define('AA_FINSIM_OPTION_KEY', 'aa_findomestic_simulator_settings');
define('AA_FINSIM_PRO_BASENAME', 'aa-findomestic-for-woocommerce/aa-findomestic-for-woocommerce.php');

// blocco l'attivazione se PHP < 7.4 e precompilo il disclaimer di default alla prima attivazione
register_activation_hook(__FILE__, 'aa_findo_sim_check_php_on_activate');
function aa_findo_sim_check_php_on_activate() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                // translators: %s is the PHP version number currently installed on the server.
                esc_html__('AA - Findomestic Simulator richiede PHP 7.4 o superiore. La versione installata è %s.', 'aa-findomestic-simulator'),
                esc_html(PHP_VERSION)
            ),
            esc_html__('Versione PHP non supportata', 'aa-findomestic-simulator'),
            array('back_link' => true)
        );
    }

    // precompilo il disclaimer Findomestic con il messaggio promozionale standard
    // l'utente può poi modificarlo dalle settings; se il campo è già valorizzato non lo tocco
    $settings = get_option(AA_FINSIM_OPTION_KEY, array());
    if (!is_array($settings)) {
        $settings = array();
    }
    if (empty($settings['findomestic_disclaimer'])) {
        $settings['findomestic_disclaimer'] = "Messaggio pubblicitario con finalità promozionale. Offerta di credito finalizzato va dal xx/xx/xxxx al xx/xx/xxxx come da esempio rappresentativo: Prezzo del bene € 2000, Tan fisso 9,58% Taeg 10,01%, in 24 rate da € 91,9 costi accessori dell'offerta azzerati. Importo totale del credito € 2000. Importo totale dovuto dal Consumatore € 2205,6. Al fine di gestire le tue spese in modo responsabile e di conoscere eventuali altre offerte disponibili, Findomestic ti ricorda, prima di sottoscrivere il contratto, di prendere visione di tutte le condizioni economiche e contrattuali, facendo riferimento alle Informazioni Europee di Base sul Credito ai Consumatori (IEBCC) presso il punto vendita. Salvo approvazione di Findomestic Banca S.p.A.. SPIRITGREEN DI GIANFRANCO FABIO opera quale intermediario del credito per Findomestic Banca S.p.A., non in esclusiva.";
        update_option(AA_FINSIM_OPTION_KEY, $settings);
    }
}

// WP 4.6+ le carica da solo, non serve più chiamare load_plugin_textdomain()

// se WooCommerce non è attivo, AA - Findomestic Simulator non può funzionare e mostra un avviso
add_action('admin_notices', 'aa_findo_sim_check_woocommerce');
function aa_findo_sim_check_woocommerce() {
    if (class_exists('WooCommerce')) {
        return;
    }
    ?>
    <div class="notice notice-error">
        <p>
            <strong>AA - Findomestic Simulator</strong>
            <?php esc_html_e('richiede WooCommerce attivo per funzionare. Installa e attiva WooCommerce per usare il simulatore rate.', 'aa-findomestic-simulator'); ?>
        </p>
    </div>
    <?php
}

// se la versione Pro è attiva, AA - Findomestic Simulator si auto-disattiva
// la Pro include il simulatore quindi tenere entrambi i plugin attivi causa duplicazione di hook e ajax
add_action('admin_init', 'aa_findo_sim_check_pro_active', 0);
function aa_findo_sim_check_pro_active() {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (is_plugin_active(AA_FINSIM_PRO_BASENAME)) {
        deactivate_plugins(AA_FINSIM_BASENAME);
        add_action('admin_notices', 'aa_findo_sim_render_pro_active_notice');
        // rimuovo il classico messaggio "Plugin activated" del WP che potrebbe apparire
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- controlliamo solo l'esistenza della chiave, non usiamo il valore
        if (isset($_GET['activate'])) {
            unset($_GET['activate']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
    }
}
function aa_findo_sim_render_pro_active_notice() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>AA - Findomestic Simulator</strong>
            <?php esc_html_e("è stato disattivato perché AA - Findomestic for WooCommerce (Pro) è attivo e include già il simulatore rate.", 'aa-findomestic-simulator'); ?>
        </p>
    </div>
    <?php
}

// definisco gli helper prima di includere i file del Pro
// così le loro guard `if (!function_exists(...))` saltano le versioni interne
// e usano queste che leggono dall'option della Lite

// helper: legge le settings della Lite
if (!function_exists('aa_findomestic_get_gateway_settings')) {
    function aa_findomestic_get_gateway_settings() {
        $settings = get_option(AA_FINSIM_OPTION_KEY, array());
        if (!is_array($settings)) {
            $settings = array();
        }
        return $settings;
    }
}

// helper: estrae le credenziali Findomestic
if (!function_exists('aa_findomestic_get_gateway_credentials')) {
    function aa_findomestic_get_gateway_credentials() {
        $settings = aa_findomestic_get_gateway_settings();
        $tvei = isset($settings['tvei']) ? sanitize_text_field($settings['tvei']) : '';
        $prf  = isset($settings['prf']) ? sanitize_text_field($settings['prf']) : '';
        return array(
            'tvei' => $tvei,
            'prf'  => $prf,
        );
    }
}

// helper: il simulatore è abilitato?
if (!function_exists('aa_findomestic_is_product_simulator_enabled')) {
    function aa_findomestic_is_product_simulator_enabled() {
        $settings = aa_findomestic_get_gateway_settings();
        return (isset($settings['enable_product_simulator']) && $settings['enable_product_simulator'] === 'yes');
    }
}

// helper: label custom del bottone simulatore (con default)
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

// helper: disclaimer Findomestic mostrato nel modale
if (!function_exists('aa_findomestic_get_disclaimer_html')) {
    function aa_findomestic_get_disclaimer_html() {
        $settings = aa_findomestic_get_gateway_settings();
        $opt = isset($settings['findomestic_disclaimer']) ? $settings['findomestic_disclaimer'] : '';
        $opt = is_string($opt) ? trim($opt) : '';
        if ($opt === '') {
            return '';
        }
        return wp_kses_post(wpautop($opt));
    }
}

// carico i moduli del simulatore
require_once AA_FINSIM_DIR . 'includes/aa-findomestic-rate-helpers.php';
require_once AA_FINSIM_DIR . 'includes/aa-findomestic-calcolo-rate.php';
require_once AA_FINSIM_DIR . 'includes/aa-findomestic-calcolo-rate-ajax.php';

// settings page in admin
require_once AA_FINSIM_DIR . 'admin/settings-page.php';

// enqueue frontend per il modale simulatore
add_action('wp_enqueue_scripts', 'aa_findo_sim_enqueue_frontend');
function aa_findo_sim_enqueue_frontend() {
    if (!aa_findomestic_is_product_simulator_enabled()) {
        return;
    }
    wp_enqueue_style(
        'aa-findomestic-simulator',
        AA_FINSIM_URL . 'assets/css/aa-findomestic-frontend.css',
        array(),
        AA_FINSIM_VERSION
    );
    wp_enqueue_script(
        'aa-findomestic-simulator',
        AA_FINSIM_URL . 'assets/js/aa-findomestic-calcolo-rate_v11.js',
        array('jquery'),
        AA_FINSIM_VERSION,
        true
    );
    wp_localize_script(
        'aa-findomestic-simulator',
        'aa_findomestic_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('aa_findomestic_rate_nonce'),
        )
    );
}

// link rapidi nella riga del plugin nella lista plugin
add_filter('plugin_action_links_' . AA_FINSIM_BASENAME, 'aa_findo_sim_action_links');
function aa_findo_sim_action_links($links) {
    $settings_url = admin_url('options-general.php?page=aa-findomestic-simulator');
    $custom = array(
        '<a href="' . esc_url($settings_url) . '">' . esc_html__('Impostazioni', 'aa-findomestic-simulator') . '</a>',
        '<a href="https://alessioangeloro.it/integrazione-di-findomestic-in-woocommerce" target="_blank" rel="noopener noreferrer" style="color:#d63638;font-weight:600;">' . esc_html__('Passa a Pro', 'aa-findomestic-simulator') . '</a>',
    );
    return array_merge($custom, $links);
}
