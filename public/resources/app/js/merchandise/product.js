 $(document).ready(function() {
     $('.product-listing').on('change', '.reward-group', function(){
         var that = $(this),
             rewardId = that.data('reward-id'),
             clientId = that.data('client-id'),
             rewardGroupId = that.val();

         if(rewardGroupId == "") {
             rewardGroupId = 0;
         }
         $.post(baseURL + 'merchandise/ajax/updateReward/' + rewardId, {'reward_group_id': that.val(), 'client_id': clientId}, function(response) {
             //zewa.paginator.refresh('product-listing');
             iim.notification.notify('Success!', response.message);
         },'json');
     });
 });
    (function($, doc, window, undefined){

        var keyupTimeout,
            quantityRating = $("#quantityRating"),
            leftPrice = $('#leftPrice'),
            rightPrice = $('#rightPrice'),
            leftPriceOperator = $("#leftPriceOperator"),
            rightPriceOperator = $("#rightPriceOperator");

        $(document).scroll(function() {
            $('.panel.rounded').css({top:$(this).scrollTop()});
        });

        function custom() {
            if (leftPriceOperator.val() == '>') {
                leftPrice.attr('name', 'priceMin');
                rightPrice.attr('name', 'priceMax');
            } else {
                leftPrice.attr('name', 'priceMax');
                rightPrice.attr('name', 'priceMin');
            }
        }

        function setEqual() {
            rightPrice.val((parseInt(leftPrice.val()) || 0));
            leftPriceOperator.find('[value="="]').prop('selected', true);
            rightPriceOperator.find('[value="="]').prop('selected', true);
            rightPrice.hide();
            rightPriceOperator.hide();
        }

        function unsetEqual() {
            rightPrice.show();
            rightPriceOperator.show();
            if (leftPriceOperator.val() == '<' && parseInt(leftPrice.val()) <= parseInt(rightPrice.val())) {
                rightPrice.val((parseInt(leftPrice.val())) || 0);
                leftPrice.val((parseInt(leftPrice.val()) || 0) + 1);
            } else if (leftPriceOperator.val() == '>' && parseInt(leftPrice.val()) >= parseInt(rightPrice.val())) {
                rightPrice.val((parseInt(leftPrice.val()) || 0) + 1);
                leftPrice.val((parseInt(leftPrice.val())) || 0);
            }
        }

        function runChecks(activeSelector, modifySelector) {
            custom();
            if (activeSelector !== undefined && modifySelector !== undefined) {
                switch(activeSelector.val()) {
                    case '<':
                        modifySelector.find('[value=">"]').prop('selected', true);
                        break;
                    case '>':
                        modifySelector.find('[value="<"]').prop('selected', true);
                        break;
                    default://equal to ("=" is the actual value)
                        setEqual();
                        break;
                }
            }
            if (leftPriceOperator.val() == '=' || rightPriceOperator.val() == '=') {
                setEqual();
            } else {
                unsetEqual();
            }
        }

        leftPrice.keydown(function(){clearTimeout(keyupTimeout);keyupTimeout = setTimeout(function() {runChecks()}, 400)});
        rightPrice.keydown(function(){clearTimeout(keyupTimeout);keyupTimeout = setTimeout(function() {runChecks()}, 400)});
        leftPriceOperator.change(function(){runChecks(leftPriceOperator, rightPriceOperator)});
        rightPriceOperator.change(function(){runChecks(rightPriceOperator, leftPriceOperator)});

        var quantityRatingSettings = {
            ticks: [0, 1, 2, 3],
            ticks_labels: ['None', 'Low', 'Medium', 'High'],
            ticks_snap_bounds: 1
        };

        quantityRating.slider(quantityRatingSettings);

        $(window).resize(function() {
            quantityRating.slider('destroy');
            quantityRating.slider(quantityRatingSettings);
        });

        // quantityRating.trigger('change');//Trigger paginator to update

        iim.confirmation.setItemCallback('edit-product', function() {
            zewa.paginator.refresh('product-listing');
        });
        iim.confirmation.setItemCallback('product-synchronization', function(response){
            if(response.page !== undefined) {
                synchronizeResults(response.page);
            }
            //zewa.paginator.refresh('product-listing');
        });

        var synchronizeResults = function(page){
            var url = baseURL + 'merchandise/ajax/synchronize?page=' + page;
            $.get(url, function (response) {
                //zewa.paginator.refresh('product-listing');
                if (response.page !== undefined) {
                    synchronizeResults(response.page);
                }
            }, 'json');
        }

    })(jQuery, document, window);

// });
