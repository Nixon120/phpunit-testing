(function($) {
    'use strict';
    var body = $('body');

    body.on('change', '.ranged-pricing-toggle', function(e){
        var that = $(this),
            rangedPricingFields = $('.ranged-pricing');
        rangedPricingFields.toggleClass('hide');
        if(that.val() === '1') {
            rangedPricingFields.find('input').prop('required', true);
        } else {
            rangedPricingFields.find('input').removeAttr('required');
        }
    });

    body.on('submit', 'form[name=product]', function(e){
        e.preventDefault();
        var that = $(this);

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function(e) {
                //remove empty validation errors
                e.responseJSON = e.responseJSON.filter(function(v){return v!==''});

                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.errors').html(errorHtml);
                $("html, body").animate({ scrollTop: 0 }, "slow");
            },
            success: function(e, response) {
                window.location = that.attr('action');
            }
        });

    });

}(jQuery));