(function($) {
    'use strict';
    var body = $('body'),
        message = $('.message');

    $.validate({
        form : '#userImportAuditForm'
    });


    body.on('submit', 'form[name=user]', function(e){
        e.preventDefault();
        var that = $(this),
            context = that.data('action-type');

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function(e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                message.html(errorHtml);
            },
            success: function(response) {
                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>' + that.data('success-update-message') + '</strong>';

                successHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                if(context === 'create') {
                    string = '<strong>' + that.data('success-create-message') + '</strong>';
                    setTimeout(function(){
                        window.location = '/user/view/' + response.id;
                    },1000)
                }
                successHtml.append(string);
                message.html(successHtml);

                if(that.find('input[name=password]').length > 0) {
                    that.find('input[name=password]').val('');
                }
            }
        });

    });

}(jQuery));