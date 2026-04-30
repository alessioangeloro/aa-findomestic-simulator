(function ($) {
    'use strict';

    function aaEscape(str) {
        if (typeof str !== 'string') {
            return '';
        }
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function aaBuildTable(rates) {
        var html = '';
        html += '<table class="aa-table-findo-simulation-sp">';
        html += '<thead><tr>';
        html += '<th>Durata</th><th>Rata mensile</th><th>TAN</th><th>TAEG</th><th>Importo richiesto</th><th>Totale dovuto</th>';
        html += '</tr></thead><tbody>';

        rates.sort(function (a, b) {
            return (parseInt(a.duration, 10) || 0) - (parseInt(b.duration, 10) || 0);
        });

        rates.forEach(function (r) {
            html += '<tr>';
            html += '<td>' + aaEscape(String(r.duration || '')) + ' rate</td>';
            html += '<td>' + aaEscape(String(r.paymentFee || '')) + ' € / mese</td>';
            html += '<td>' + aaEscape(String(r.tan || '')) + '%</td>';
            html += '<td>' + aaEscape(String(r.taeg || '')) + '%</td>';
            html += '<td>' + aaEscape(String(r.refunded || '')) + ' €</td>';
            html += '<td>' + aaEscape(String(r.totalRefunded || '')) + ' €</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }

    // restituisce un array con i nomi leggibili degli attributi non ancora selezionati
    // uso il <label for="..."> associato alla select; se non c'è, fallback sul name dell'attributo
    function aaGetMissingVariationLabels($form) {
        if (!$form || !$form.length) {
            return [];
        }
        var $selects = $form.find('select[name^="attribute_"]');
        if (!$selects.length) {
            return [];
        }

        var missing = [];
        $selects.each(function () {
            var $sel = $(this);
            var val = $sel.val();
            if (val) {
                return;
            }

            var label = '';
            var id = $sel.attr('id') || '';
            if (id) {
                var $lbl = $form.find('label[for="' + id + '"]');
                if ($lbl.length) {
                    label = $.trim($lbl.text());
                }
            }
            if (!label) {
                // fallback: dal name attribute_pa_colore -> "colore"
                var name = $sel.attr('name') || '';
                label = name.replace(/^attribute_(pa_)?/, '').replace(/[_-]+/g, ' ').trim();
            }

            if (label) {
                missing.push(label);
            }
        });

        return missing;
    }

    function aaShowInlineMessage($wrap, msg) {
        var $msg = $wrap.find('.aa-findomestic-inline-message');
        $msg.text(msg).show();
    }

    function aaClearInlineMessage($wrap) {
        var $msg = $wrap.find('.aa-findomestic-inline-message');
        $msg.text('').hide();
    }

    function aaOpenModal($wrap) {
        var $modal = $wrap.find('.aa-findomestic-modal');

        $modal.attr('aria-hidden', 'false');
        $modal.addClass('is-open');

        $('body').addClass('aa-findomestic-modal-open');
        $('body').css('overflow', 'hidden');
    }

    function aaCloseModal($wrap) {
        var $modal = $wrap.find('.aa-findomestic-modal');

        $modal.attr('aria-hidden', 'true');
        $modal.removeClass('is-open');

        $wrap.find('.aa-findomestic-modal__message').text('');
        $wrap.find('.aa-findomestic-modal__table').empty();

        $('body').removeClass('aa-findomestic-modal-open');
        $('body').css('overflow', '');
    }

    // Close modal click (bottone)
    $(document).on('click', '[data-aa-findo-close="1"]', function () {
        var $wrap = $(this).closest('.aa-findomestic-simulator-wrap');
        if ($wrap.length) {
            aaCloseModal($wrap);
        }
    });

    // Close modal click (overlay)
    $(document).on('click', '.aa-findomestic-modal__overlay', function () {
        var $wrap = $(this).closest('.aa-findomestic-simulator-wrap');
        if ($wrap.length) {
            aaCloseModal($wrap);
        }
    });

    // ESC close
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.aa-findomestic-modal.is-open').each(function () {
                var $wrap = $(this).closest('.aa-findomestic-simulator-wrap');
                aaCloseModal($wrap);
            });
        }
    });

    // found_variation: aggiorno amount e pulisco messaggi
    $(document).on('found_variation', 'form.variations_form', function (event, variation) {
        try {
            var $form = $(this);
            var $wrap = $form.closest('.product').find('.aa-findomestic-simulator-wrap');
            var $btn = $wrap.find('.aa-findomestic-simulate');

            if (!$btn.length || !variation) {
                return;
            }

            var price = 0;
            if (typeof variation.display_price !== 'undefined') {
                price = parseFloat(variation.display_price);
            } else if (typeof variation.display_regular_price !== 'undefined') {
                price = parseFloat(variation.display_regular_price);
            }

            if (!price || price <= 0) {
                $btn.attr('data-amount-api', '');
                $btn.attr('data-amount-cents', '');
                aaShowInlineMessage($wrap, 'Seleziona una variante');
                return;
            }

            var amountApi = price.toFixed(2).replace('.', ',');
            var amountCents = String(Math.round(price * 100));

            $btn.attr('data-amount-api', amountApi);
            $btn.attr('data-amount-cents', amountCents);

            aaClearInlineMessage($wrap);
        } catch (e) {}
    });

    // Regola aggressiva: appena cambia una select variante -> pulisco messaggi e chiudo modal
    $(document).on('woocommerce_variation_has_changed', 'form.variations_form', function () {
        var $form = $(this);
        var $wrap = $form.closest('.product').find('.aa-findomestic-simulator-wrap');
        if (!$wrap.length) {
            return;
        }

        aaClearInlineMessage($wrap);
        aaCloseModal($wrap);

        $wrap.find('.aa-findomestic-installments').hide().empty();
        $wrap.find('.aa-table-findo-simulation-sp').remove();
    });

    // reset_data: pulisco tutto
    $(document).on('reset_data', 'form.variations_form', function () {
        var $form = $(this);
        var $wrap = $form.closest('.product').find('.aa-findomestic-simulator-wrap');
        var $btn = $wrap.find('.aa-findomestic-simulate');

        if ($btn.length) {
            $btn.attr('data-amount-api', '');
            $btn.attr('data-amount-cents', '');
        }

        aaClearInlineMessage($wrap);
        aaCloseModal($wrap);
    });

    // click simulazione
    $(document).on('click', '.aa-findomestic-simulate', function (e) {
        e.preventDefault();

        if (typeof aa_findomestic_ajax === 'undefined' || !aa_findomestic_ajax.ajax_url) {
            return;
        }

        var $btn = $(this);
        var $wrap = $btn.closest('.aa-findomestic-simulator-wrap');

        aaClearInlineMessage($wrap);

        var isVariable = parseInt($btn.attr('data-is-variable') || '0', 10) === 1;
        if (isVariable) {
            var $form = $btn.closest('.product').find('form.variations_form');
            var missingLabels = aaGetMissingVariationLabels($form);

            if (missingLabels.length > 0) {
                if (missingLabels.length === 1) {
                    aaShowInlineMessage($wrap, 'Seleziona la variante ' + missingLabels[0]);
                } else if (missingLabels.length === $form.find('select[name^="attribute_"]').length) {
                    // se tutte le select varianti sono vuote
                    aaShowInlineMessage($wrap, 'Seleziona tutte le varianti');
                } else {
                    // ne mancano alcune ma non tutte: elenco i nomi separati da virgola
                    aaShowInlineMessage($wrap, 'Seleziona ' + missingLabels.join(', '));
                }
                return;
            }
        }

        var productId = $btn.data('product-id');
        var amountApi = $btn.attr('data-amount-api') || '';
        var amountCents = parseInt($btn.attr('data-amount-cents') || '0', 10);

        if (isVariable && (!amountApi || !amountCents)) {
            aaShowInlineMessage($wrap, 'Seleziona una variante');
            return;
        }

        if (!amountApi || !amountCents) {
            aaShowInlineMessage($wrap, 'Importo non valido.');
            return;
        }

        if (amountCents < 100000) {
            aaShowInlineMessage($wrap, 'Importo minimo per la simulazione: 1000,00€.');
            return;
        }

        var cartId = 'cart' + String(Math.floor(1000000 + Math.random() * 9000000));

        var payload = {
            action: 'aa_findomestic_calcolo_rate_ajax',
            security: aa_findomestic_ajax.security,
            product_id: productId,
            cartId: cartId,
            amount_api: amountApi
        };

        var $modalMsg = $wrap.find('.aa-findomestic-modal__message');
        var $modalTable = $wrap.find('.aa-findomestic-modal__table');

        $modalMsg.text('Caricamento offerte...');
        $modalTable.empty();
        aaOpenModal($wrap);

        $btn.prop('disabled', true);

        $.ajax({
            url: aa_findomestic_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: payload
        })
        .done(function (res) {
            if (!res || !res.success) {
                var msg = 'Nessuna rata trovata.';
                if (res && res.data && res.data.message) {
                    msg = res.data.message;
                }
                $modalMsg.text(msg);
                return;
            }

            var data = res.data || {};
            var rates = data.rates || [];

            if (!rates.length) {
                $modalMsg.text('Nessuna rata trovata.');
                return;
            }

            $modalMsg.text('');
            $modalTable.html(aaBuildTable(rates));
        })
        .fail(function () {
            $modalMsg.text('Errore nella richiesta. Riprova.');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

})(jQuery);