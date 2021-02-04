/*
 * Copyright (c) 2018, Ryo Currency Project
*/
function haven_showNotification(message, type='success') {
    var toast = jQuery('<div class="' + type + '"><span>' + message + '</span></div>');
    jQuery('#haven_toast').append(toast);
    toast.animate({ "right": "12px" }, "fast");
    setInterval(function() {
        toast.animate({ "right": "-400px" }, "fast", function() {
            toast.remove();
        });
    }, 2500)
}
function haven_showQR(show=true) {
    jQuery('#haven_qr_code_container').toggle(show);
}
function haven_fetchDetails() {
    var data = {
        '_': jQuery.now(),
        'order_id': haven_details.order_id
    };
    jQuery.get(haven_ajax_url, data, function(response) {
        if (typeof response.error !== 'undefined') {
            console.log(response.error);
        } else {
            haven_details = response;
            haven_updateDetails();
        }
    });
}

function haven_updateDetails() {

    var details = haven_details;

    jQuery('#haven_payment_messages').children().hide();
    switch(details.status) {
        case 'unpaid':
            jQuery('.haven_payment_unpaid').show();
            jQuery('.haven_payment_expire_time').html(details.order_expires);
            break;
        case 'partial':
            jQuery('.haven_payment_partial').show();
            jQuery('.haven_payment_expire_time').html(details.order_expires);
            break;
        case 'paid':
            jQuery('.haven_payment_paid').show();
            jQuery('.haven_confirm_time').html(details.time_to_confirm);
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'confirmed':
            jQuery('.haven_payment_confirmed').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired':
            jQuery('.haven_payment_expired').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired_partial':
            jQuery('.haven_payment_expired_partial').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
    }

    jQuery('#haven_total_amount').html(details.amount_total_formatted);
    jQuery('#haven_total_paid').html(details.amount_paid_formatted);
    jQuery('#haven_total_due').html(details.amount_due_formatted);

    jQuery('#haven_integrated_address').html(details.integrated_address);

    if(haven_show_qr) {
        var qr = jQuery('#haven_qr_code').html('');
        new QRCode(qr.get(0), details.qrcode_uri);
    }

    if(details.txs.length) {
        jQuery('#haven_tx_table').show();
        jQuery('#haven_tx_none').hide();
        jQuery('#haven_tx_table tbody').html('');
        for(var i=0; i < details.txs.length; i++) {
            var tx = details.txs[i];
            var height = tx.height == 0 ? 'N/A' : tx.height;
            var row = ''+
                '<tr>'+
                '<td style="word-break: break-all">'+
                '<a href="'+haven_explorer_url+'/tx/'+tx.txid+'" target="_blank">'+tx.txid+'</a>'+
                '</td>'+
                '<td>'+height+'</td>'+
                '<td>'+tx.amount_formatted+' '+tx.currency+'</td>'+
                '</tr>';

            jQuery('#haven_tx_table tbody').append(row);
        }
    } else {
        jQuery('#haven_tx_table').hide();
        jQuery('#haven_tx_none').show();
    }

    // Show state change notifications
    var new_txs = details.txs;
    var old_txs = haven_order_state.txs;
    if(new_txs.length != old_txs.length) {
        for(var i = 0; i < new_txs.length; i++) {
            var is_new_tx = true;
            for(var j = 0; j < old_txs.length; j++) {
                if(new_txs[i].txid == old_txs[j].txid && new_txs[i].amount == old_txs[j].amount) {
                    is_new_tx = false;
                    break;
                }
            }
            if(is_new_tx) {
                haven_showNotification('Transaction received for '+new_txs[i].amount_formatted+' Haven');
            }
        }
    }

    if(details.status != haven_order_state.status) {
        switch(details.status) {
            case 'paid':
                haven_showNotification('Your order has been paid in full');
                break;
            case 'confirmed':
                haven_showNotification('Your order has been confirmed');
                break;
            case 'expired':
            case 'expired_partial':
                haven_showNotification('Your order has expired', 'error');
                break;
        }
    }

    haven_order_state = {
        status: haven_details.status,
        txs: haven_details.txs
    };

}
jQuery(document).ready(function($) {
    if (typeof haven_details !== 'undefined') {
        haven_order_state = {
            status: haven_details.status,
            txs: haven_details.txs
        };
        setInterval(haven_fetchDetails, 30000);
        haven_updateDetails();
        new ClipboardJS('.clipboard').on('success', function(e) {
            e.clearSelection();
            if(e.trigger.disabled) return;
            switch(e.trigger.getAttribute('data-clipboard-target')) {
                case '#haven_integrated_address':
                    haven_showNotification('Copied destination address!');
                    break;
                case '#haven_total_due':
                    haven_showNotification('Copied total amount due!');
                    break;
            }
            e.clearSelection();
        });
    }
});
