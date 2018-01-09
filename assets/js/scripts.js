jQuery(document).ready(function ($) {
    if ($('#wcgaid_gift_aid').is(':checked')) {
        $('.wcgaid-gift-aid.hide').show();
        $('#wcgaid_gift_aid_2').change();
    } else {
        $('.wcgaid-gift-aid.hide').hide();
        $('.wcgaid-gift-aid.fields').hide();
    }

    $('#wcgaid_gift_aid').change(function () {
        if (this.checked) {
            $('.wcgaid-gift-aid.hide').show();
            $('#wcgaid_gift_aid_2').change();
        } else {
            $('.wcgaid-gift-aid.hide').hide();
            $('.wcgaid-gift-aid.fields').hide();
        }
    });
    
    if ($('#wcgaid_gift_aid_2').is(':checked')) {
        $('.wcgaid-gift-aid.fields').show();
    } else {
        $('.wcgaid-gift-aid.fields').hide();
    }

    $('#wcgaid_gift_aid_2').change(function () {
        if (this.checked) {
            $('.wcgaid-gift-aid.fields').show();
        } else {
            $('.wcgaid-gift-aid.fields').hide();
        }
    });
});