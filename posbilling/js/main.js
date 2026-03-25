$(document).ready(function() {
    // Shared JavaScript functionalities for Pharmacy ERP

    // Smooth transitions for alerts
    setTimeout(function() {
        $('#msg-flash').fadeOut('slow');
    }, 5000);

    // Auto-calculate remaining payment on POS page
    $('input[name="amount_paid"]').on('input', function() {
        const grandTotalText = $('#grand_total').text().replace('₹', '');
        const grandTotal = parseFloat(grandTotalText) || 0;
        const paid = parseFloat($(this).val()) || 0;

        if(paid > grandTotal) {
            // Change amount logic if needed
        }
    });

    // Form confirmation
    $('.confirm-delete').on('click', function(e) {
        if(!confirm('Are you sure you want to perform this action?')) {
            e.preventDefault();
        }
    });

    // Initialize Select2
    if ($('.select2').length) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
