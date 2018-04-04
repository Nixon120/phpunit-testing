(function ($) {
    'use strict';
    var body = $('body');

    var renderImageData = function (image) {
        $('.preview-logo').attr('src', image);
        $('input[name=logo]').val(image);
    };

    var formatDepositAmount = function () {
        document.getElementById('deposit_amount').value =
            (document.getElementById('deposit_amount').value === '')
                ? 0
                : document.getElementById('deposit_amount').value;
    };


    $.validate({
        form: '#programForm',
        errorElementClass: 'no-error'

    });

    $.validate({
        form: '#programProductLayout',
        errorElementClass: 'no-error'
    });

    body.on('click', '.add-logo', function () {
        $('input[name=logo_dummy]').trigger('click');
    });

    body.on('click', '.del-logo', function () {
        $('input[name=logo]').val('');
        $('.preview-logo').attr('src', 'http://via.placeholder.com/350x150');
    });

    body.on('change', 'input[name=logo_dummy]', function (e) {
        var that = $(this);

        if (that[0].files && that[0].files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                renderImageData(e.target.result);
            };

            reader.readAsDataURL(that[0].files[0]);
        }

    });

    body.on('change', 'select[name=interval]', function (e) {
        $('.schedule-dropdown').toggleClass('hide');
    });

    body.on('change', '.auto-redemption-toggle', function (e) {
        $('.auto-redemption').toggleClass('hide');
    });

    body.on('change', '.sweepstake-toggle', function(e){
        $('.sweepstake').toggleClass('hide');
    });

    body.on('change', '.type-control select', function (e) {
        var that = $(this),
            card = that.closest('.card'),
            linkControl = card.find('.link-control'),
            imgControl = card.find('.image-control'),
            productControl = card.find('.product-control'),
            productRowControl = card.find('.product-row-control');

        linkControl.addClass('hide d-none');
        productControl.addClass('hide d-none');
        productRowControl.addClass('hide d-none');
        imgControl.removeClass('hide d-none');

        switch(that.val()) {
            case 'product':
                productControl.removeClass('hide d-none');
                break;
            case 'link':
                linkControl.removeClass('hide d-none');
                break;
            case 'image':
                imgControl.removeClass('hide d-none');
                break;
            case 'product_row':
                productRowControl.removeClass('hide d-none');
                imgControl.addClass('hide d-none');
                break;
        }
    });

    body.on('click', '.remove-row', function (e) {
        var that = $(this),
            message = $('.message');

        e.preventDefault();

        if(that.data('row-id') === undefined) {
            that.closest('.layout-row').remove();
            return;
        }

        $.ajax({
            type: 'GET',
            url: that.attr('href'),
            error: function (e) {
                var string = $('<div/>')
                    .addClass('alert alert-danger')
                    .html('<strong>Oops! Something went wrong.</strong>');

                string.append('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                message.html(string);
                window.scrollTo(0, 0);
            },
            success: function (e, response) {
                var string = $('<div/>')
                    .addClass('alert alert-success')
                    .html('<strong>' + that.data('success-delete-message') + '</strong>');

                string.append('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                message.html(string);
                that.closest('.layout-row').remove();
                window.scrollTo(0, 0);
            }
        });
    });

    body.on('submit', 'form[name=program]', function (e) {
        e.preventDefault();
        $('.errors').html();
        //set deposit to 0 if empty
        formatDepositAmount();
        var that = $(this);
        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function (e) {
                //remove empty validation errors
                e.responseJSON = e.responseJSON.filter(function (v) {
                    return v !== ''
                });

                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.errors').html(errorHtml);
                window.scrollTo(0, 0);
            },
            success: function (e, response) {
                window.location = "/program";
            }
        });

    });

    body.on('submit', 'form[name=programFeaturedProductForm]', function (e) {
        e.preventDefault();
        $('.errors').html();
        var that = $(this);
        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function (e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.errors').html(errorHtml);
                window.scrollTo(0, 0);
            },
            success: function (e, response) {
                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>' + that.data('success-update-message') + '</strong>';

                successHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                successHtml.append(string);
                $('.message').html(successHtml);
            }
        });

    });

    body.on('submit', 'form[name=programProductForm]', function (e) {
        e.preventDefault();
        $('.errors').html();
        var that = $(this);
        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function (e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.errors').html(errorHtml);
                window.scrollTo(0, 0);
            },
            success: function (e, response) {
                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>' + that.data('success-update-message') + '</strong>';

                successHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                successHtml.append(string);
                $('.message').html(successHtml);
                zewa.paginator.refresh('product-listing');
            }
        });

    });

    body.on('submit', 'form[name=programProductLayout]', function (e) {
        e.preventDefault();
        $('.errors').html();
        var that = $(this),
            message = $('.message');

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function (e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.errors').html(errorHtml);
                window.scrollTo(0, 0);
            },
            success: function (e, response) {
                var string = $('<div/>')
                    .addClass('alert alert-success')
                    .html('<strong>' + that.data('success-update-message') + '</strong>');

                string.append('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                message.html(string);
                window.scrollTo(0, 0);
                setTimeout(function(){
                    window.location.reload();
                }, 3000)
            }
        });
    });

    body.on('submit', 'form[name=sweepstake]', function(e){
        e.preventDefault();
        $('.message').html();
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
                $('.message').html(errorHtml);
                $("html, body").animate({ scrollTop: 0 }, "slow");
            },
            success: function(e, response) {
                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>' + that.data('success-update-message') + '</strong>';

                successHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                successHtml.append(string);
                $('.message').html(successHtml);
            }
        });

    });

    $(document).ready(function () {
        $('.standard-date-pickers').datepicker({
            format: 'yyyy-mm-dd',
            todayBtn: 'linked'
        });

        $("#addrow").on("click", function () {
            var newRow = $("<tr>"),
                cols = '<td><input type="text" class="form-control standard-date-pickers" name="draw_date[]"/></td>'
                + '<td><input type="number" class="form-control" name="draw_count[]"/></td>'
                + '<td><button class="delete-drawing-date btn btn-md btn-danger">Delete</button></td>';

            newRow.append(cols);
            $("table.draw-list").append(newRow);

            $('.standard-date-pickers').datepicker({
                format: 'yyyy-mm-dd',
                todayBtn: 'linked'
            });
        });

        $("table.draw-list").on("click", ".delete-drawing-date", function (event) {
            $(this).closest("tr").remove();
        });

    });

    body.on('change', 'select[name=row_columns]', function () {
        var that = $(this),
            activeRow = that.closest('.layout-row'),
            currentRow = activeRow.index(),
            currentColumnCount = activeRow.find('.cards > div').length,
            requestedColumnCount = parseInt(that.val()),
            columnSize = getColumnSize(requestedColumnCount);

        if (currentColumnCount > requestedColumnCount) {
            // Remove the last columns
            var removeCount = currentColumnCount - requestedColumnCount;
            for (i = 0; i < removeCount; i++) {
                activeRow.find('.cards > div:last-child').remove();
            }
        } else {
            for (var i = currentColumnCount; i < requestedColumnCount; i++) {
                var clone = $('.card-template').clone();
                clone.find('.type-control').find('select').attr('name', 'row[' + currentRow + '][card][' + i + '][type]');
                clone.find('.link-control').find('input').attr('name', 'row[' + currentRow + '][card][' + i + '][link]');
                clone.find('.card-size').attr('name', 'row[' + currentRow + '][card][' + i + '][size]');
                clone.find('.image-control').append('<input type="hidden" name="row[' + currentRow + '][card][' + i + '][image]" value />');
                clone.find('.product-control').find('select').attr('name', 'row[' + currentRow + '][card][' + i + '][product]');
                clone.find('.product-row-control').find('select').attr('name', 'row[' + currentRow + '][card][' + i + '][product_row][]');
                var div = $('<div/>').html(clone.html());
                activeRow.find('.cards').append(div);
            }
        }
        activeRow.find('.card-size').val(columnSize);
        activeRow.find('.cards > div').attr('class', 'col-xs-' + columnSize + ' col-' + columnSize);
        activeRow.find('select').prop('disabled', false);
        Marketplace.reloadSelect2();
    });

    var getColumnSize = function (columnCount) {
        var size = 12;
        switch (columnCount) {
            case 2:
                size = 6;
                break;
            case 3:
                size = 4;
                break;
            case 4:
                size = 3;
                break;
            case 5:
            case 6:
                size = 2;
        }

        return size;
    };

    body.on('click', '.update-card-image', function () {
        var that = $(this),
            block = that.closest('.card');

        block.find('input[type=file]').trigger('click');
    });

    body.on('click', '.add-layout-row', function () {
        $('.layout-container').append($('.layout-template').clone().html());
        var row = $('.layout-container').find('.layout-row:last-child');
        row.find('.card').each(function (key, value) {
            $(value).find('.type-control').find('select').attr('name', 'row[' + row.index() + '][card][' + key + '][type]');
            $(value).find('.link-control').find('input').attr('name', 'row[' + row.index() + '][card][' + key + '][link]');
            $(value).find('.card-size').attr('name', 'row[' + row.index() + '][card][' + key + '][size]');
            $(value).find('.product-control > select').attr('name', 'row[' + row.index() + '][card][' + key + '][product]');
            $(value).find('.product-row-control > select').attr('name', 'row[' + row.index() + '][card][' + key + '][product_row][]');
            $(value).find('.image-control > input[type=hidden]').attr('name', 'row[' + row.index() + '][card][' + key + '][image]');
        });

        row.find('.row-label').attr('name', 'row[' + row.index() + '][label]');
        row.find('select').prop('disabled', false);
        Marketplace.reloadSelect2();
    });

    body.on('change', 'input[name=dummy]', function (event) {
        var that = $(this),
            activeRow = that.closest('.layout-row'),
            card = that.closest('.card'),
            cardIndex = activeRow.find('.card').index(card);

        if (that[0].files && that[0].files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                card.find('.preview').attr('src', e.target.result);
                $('input[name="row[' + activeRow.index() + '][card][' + cardIndex + '][image]"]').val(e.target.result);
            };

            reader.readAsDataURL(that[0].files[0]);
        }

    });

    body.on('change', '.card-size', function (event) {
        var that = $(this),
            cardContainer = that.closest('.card').parent(),
            activeRow = that.closest('.layout-row'),
            allCardsFromActiveRow = activeRow.find('.cards > div'),
            columnCount = parseInt(activeRow.find('.control select[name=row_columns]').val()),
            cardSize = parseInt(that.val()),
            availableColumnSegments = 12 - parseInt(cardSize),
            remainingSegmentWidth = Math.floor(availableColumnSegments / (columnCount - 1)),
            availableForAllocation = (columnCount - 1) * remainingSegmentWidth;

        if ((columnCount + cardSize) > 13) {
            that.val(columnCount + 1);
            return;
        }

        that.closest('.card').parent().attr('class', 'col-xs-' + cardSize);


        //combine
        if (remainingSegmentWidth <= availableForAllocation) {
            allCardsFromActiveRow
                .not(cardContainer)
                .attr('class', 'col-xs-' + remainingSegmentWidth);
            allCardsFromActiveRow
                .not(cardContainer)
                .find('.card-size')
                .val(remainingSegmentWidth);
        }
    });

    $(document).on('click','.publish',function(e) {
        var that = $(this);
        var publish = that.prop("checked") === true ? 1 : 0;
        $('.message').html('').show();

        $.ajax({
            type: 'POST',
            url: that.attr('href') + '/' + publish,
            error: function(e) {
            },
            success: function(e, response) {
                var successHtml = $('<div class="alert alert-success">'),
                    string = '<strong>Program updated successfully</strong>';
                successHtml.append(string);
                $('.message').html(successHtml);

                setTimeout(function() {
                    $('.message').hide();
                }, 5000);
            }

        });

    });

}(jQuery));