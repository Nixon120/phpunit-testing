(function($) {
    'use strict';
    var body = $('body'),
        message = $('.message');

    $(document).ready(function(){
        $.validate({
            form : '#participantForm',
            errorElementClass: 'no-error'

        });
        $('.birthdate').datepicker({
            format: 'yyyy-mm-dd',
            todayBtn: 'linked'
        });
    });

    body.on('submit', 'form[name=participant]', function(e){
        e.preventDefault();
        var that = $(this),
            context = that.data('action-type');

        $('.localized-error').html('');
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

            },
            success: function(response) {

                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>' + that.data('success-update-message') + '</strong>';

                successHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                if(context === 'create') {
                    string = '<strong>' + that.data('success-create-message') + '</strong>';
                    setTimeout(function(){
                        window.location = '/participant/' + response.unique_id + '/view';
                    },1000)
                }
                successHtml.append(string);
                message.html(successHtml);

                if(that.find('input[name=password]').val().trim() !== "") {
                    that.find('input[name=password], input[name=password_confirm]').val('');
                }

            }
        });

    });

}(jQuery));