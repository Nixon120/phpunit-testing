(function($) {
    'use strict';
    var body = $('body');

    body.on('click', '.add-domain', function(e){
        var domainClone = $('.domain-input-clone').clone(),
            domainCount = $('.domain-input-wrapper').length;
        domainClone.find('input').prop('disabled', false);
        domainClone.find('.localized-error').addClass(domainCount.toString());
        domainClone.removeClass('domain-input-clone hide').addClass('domain-input-wrapper');
        $('.domain-wrapper').append(domainClone);
    });

    body.on('click', '.remove-domain', function(e){
        var that = $(this);
        if(that.attr('data-domain-id') === undefined) {
            that.closest('.domain-input-wrapper').remove();
            return;
        }
        $.ajax({
            type: 'DELETE',
            url: that.attr('data-action-url'),
            success: function(e, response) {
                var string = '<strong>Success!</strong><br/>',
                    responseMessage = $('<div class="alert alert-success">');

                responseMessage.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                responseMessage.append(string);
                $('.modal-content .modal-body').prepend(responseMessage);
                that.closest('.domain-input-wrapper').remove();
            }
        });
    });

    $('input, select, textarea').keypress(function(){
        var that = $(this),
            parent = that.parent(),
            errorElement = parent.find('.localized-error');

        errorElement.html('');
    });

    body.on('submit', 'form[name=organization]', function(e){
        e.preventDefault();
        var that = $(this);

        $('.localized-error, .form-warning').html('');
        $('input, select, textarea').css({'outline': '1px solid transparent'});

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function(e) {
                var errors = e.responseJSON.errors;
                $('input, select, textarea').css({'outline': '1px solid transparent'});
                $.each(errors, function(field, messages){
                    for (var key in messages) {
                        if (messages.hasOwnProperty(key)) {
                            var errorContainer = $('.error-' + field),
                                parent = errorContainer.parent();

                            errorContainer
                                .css({"margin-left": "0", "margin-top": "10px"})
                                .html('<span class="alert alert-danger">' + messages[key] + '</span>');

                            parent.find('input, select, textarea').css({'outline': '1px solid red'});
                        }
                    }
                });
                $('.form-warning').html('<p class="alert alert-warning text-left">There were problems with your request. Please fix the errors and try again.');
            },
            success: function(e, response) {
                window.location = "/organization";
            }
        });
    });

}(jQuery));