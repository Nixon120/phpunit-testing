(function($) {
    'use strict';
    var body = $('body');

    var renderImageData = function(image) {
        $('.preview-attachment').attr('src', image);
        $('input[name=attachment]').val(image);
    };

    body.on('click', '.add-attachment', function() {
        $('input[name=attachment_dummy]').trigger('click');
    });

    body.on('change', 'input[name=attachment_dummy]', function(e){
        var that = $(this);

        if (that[0].files && that[0].files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                renderImageData(e.target.result);
            };

            reader.readAsDataURL(that[0].files[0]);
        }

    });

    // body.on('change', 'select[name=organization_id]', function(e){
    //     var domainSelectField = $('[data-filter-organization_id]');
    //     domainSelectField.attr('data-filter-organization_id', $(this).val());
    //     domainSelectField.val(null).trigger('change');
    // });

    body.on('submit', 'form[name=group]', function(e){
        e.preventDefault();
        var that = $(this);

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function(e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.modal-content .modal-body').prepend(errorHtml);
                // console.log(string.params);
                // console.log(string.params.join(', '));
            },
            success: function(e, response) {
                // console.log(e);
                $('.modal').modal('hide');
                zewa.paginator.refresh('group-listing');
            }
            // dataType: "json",
            // contentType: "application/json"
        });

    });

}(jQuery));