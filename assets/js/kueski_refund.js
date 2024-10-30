jQuery().ready(function($){

    var itemIds = [];
    var quantities = {};

    function formatPrice(price){
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(price);
    }

    function updateRefundTotal(){
        var totalRefund = 0;
        itemIds = [];
        quantities = {};
        $('.kueski-refund-qty').each(function(){
            var quantity = $(this).val();
            var price = $(this).data('item-price');
            var itemId = $(this).data('item-id');
            var itemTotal = quantity * price;

            if(quantity > 0){
                itemIds.push(itemId);
                quantities[itemId] = quantity;
            }

            $('#kueski-item-total-'+itemId).text( formatPrice(itemTotal) );

            totalRefund += itemTotal;
        });

        if( $('#kueski_refund_shipping').is(':checked') ){
            var shippingRefunded = $('#kueski_refund_shipping').data('shipping-refunded');
            if( !shippingRefunded ){
                var shippingCost = $('#kueski_refund_shipping').data('shipping-cost');
                totalRefund += shippingCost;
            }
        }

        $('#kueski_refund_total').val(totalRefund);

        if( totalRefund > 0 ){
            $('#kueski_refund_button').prop('disabled', false);
        }else{
            $('#kueski_refund_button').prop('disabled', true);
        }
    }
    
    $('#kueski_refund_button').prop('disabled', true);

    $('.kueski-refund-qty').on('input', updateRefundTotal);
    $('#kueski_refund_shipping').on('change', updateRefundTotal);

    $('#kueski_refund_button').on('click', function(e) {
        e.preventDefault();

        var refundAmount = $('#kueski_refund_total').val();
        if(refundAmount <= 0){
            return;
        }
        $('#kueski-loading-refund').addClass('is-active');
        $('#kueski-refund-message').html('');

        var paymentId = $('#kueski_refund_payment_id').val();
        var refundShipping = $('#kueski_refund_shipping').is(':checked') ? 1 : 0;
        
        var data = {
            action: 'kueski_process_refund',
            refund_amount: refundAmount,
            payment_id: paymentId,
            kueski_refund_nonce: kueski_refund_vars.kueski_refund_nonce,
            order_id: $('input[name="post_ID"]').val(),
            item_ids: itemIds,
            quantities: quantities,
            refund_shipping: refundShipping
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST', 
            data: data,
            success: function(response) {
                $('#kueski-loading-refund').removeClass('is-active');
                if( response.success ){
                    $('#kueski-refund-message').html(
                        '<div class="notice notice-success"><p>'+response.data.message+'</p</div>'
                    );
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    $('#kueski-refund-message').html(
                        '<div class="notice notice-error"><p>'+response.data.message+'</p</div>'
                    );
                }
                
            },
            error: function(){
                $('#kueski-loading-refund').removeClass('is-active');
                $('#kueski-refund-message').html(
                    '<div class="notice notice-error"><p>Error inesperado</p</div>'
                );
            }
        });
    });
});