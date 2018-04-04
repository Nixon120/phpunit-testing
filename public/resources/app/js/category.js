$(function () {
    'use strict';
    var body = $('body');

    //@TODO: update sitewide to use registry clientId...
    var activeCategory;
    var defaultAssignment = 1;

    $('.marketplace-categories').on('click', '.category-listing', function() {
        var that = $(this),
            categoryId = that.data('category-id');
            
        activeCategory = categoryId;
        
        if(that.hasClass('btn-danger')) {
            alert('You must activate the category first.');
            return;
        }
        
        zewa.paginator.refresh('feed-categories', function(){
            $('.category-assignment-save').addClass('hide');
            $('.category-listing').not('.btn-danger').removeClass('btn-warning').addClass('btn-inverse');
            that.toggleClass('btn-inverse btn-warning');
        });
        
    });

    $('.category-assignment-save').click(function(){
        var string = '';
        $('[data-actioned="add"], [data-actioned="remove"]').each(function(key, item){
            if($(item).data('updated-linked-category-id') == activeCategory) {
                string += '&add_categories[]=' + $.trim($(item).data('category-id'));
            } else if($(item).data('actioned') == 'remove') {
                string += '&remove_categories[]=' + $.trim($(item).data('category-id'));
            }
        });
        
        $.post(baseURL + '/category/assignment/' + activeCategory, string, function(response) {
            zewa.paginator.refresh('feed-categories');
            $('.category-assignment-save').addClass('hide');
        },'json');

    });

    $('.feed-categories').on('click', '.feed-category-listing', function() {
        var that = $(this),
            categoryId = that.data('updated-linked-category-id');
            
        that.toggleClass('btn-success btn-inverse');
        
        if(!that.hasClass('btn-success') && that.data('origin-linked-category-id') != categoryId) {
            that.data('actioned', false);
            that.attr('data-actioned', false);
            return;
        }
        
        if(that.hasClass('btn-success')) {
            that.data('updated-linked-category-id', activeCategory)
            that.attr('data-updated-linked-category-id', activeCategory);
            that.data('actioned', 'add');
            that.attr('data-actioned', 'add');
        } else {
            that.data('updated-linked-category-id', defaultAssignment)
            that.attr('data-updated-linked-category-id', defaultAssignment);
            that.data('actioned', 'remove');
            that.attr('data-actioned', 'remove');
        }
        
        $('.category-assignment-save').removeClass('hide');
    });

    body.on('submit', 'form[name=category]', function(e){
        e.preventDefault();
        var that = $(this);

        $.ajax({
            type: 'POST',
            url: that.attr('action'),
            data: that.serialize(),
            error: function(e) {
                var string = '<strong>Oops! Something went wrong.</strong><br/>' + e.responseJSON.params.join(', '),
                    errorHtml = $('<div class="alert alert-danger">');

                errorHtml.html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
                errorHtml.append(string);
                $('.modal-content .modal-body').prepend(errorHtml);
            },
            success: function(e, response) {
                // console.log(e);
                $('.modal').modal('hide');
                zewa.paginator.refresh('marketplace-categories');
                zewa.paginator.refresh('feed-categories');
            }
        });
    });
});