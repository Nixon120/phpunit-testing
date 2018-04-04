(function($) {
    'use strict';
    var body = $('body');

    $(document).ready(function(){
        $('select[name=report]').trigger('change');


        $.validate({
            form : '#reportForm'
        });

    });


    body.on('change', 'select[name=report]', function(event) {

        if($(this).val() =='')
            return;
        var that = $(this),
            report = that.val(),
            reportElements = $('.report-criteria, .report-fields'),
            activeReportCriteriaElements = $('.report-fields.'  + report),
            activeReportFieldElements = $('.report-criteria.' + report),
            allActiveReportElements = $.merge(activeReportCriteriaElements, activeReportFieldElements),
            requiredFields = $.merge($('select[name=organization]'), $('select[name="fields[]"]'));

        reportElements.addClass('hide');
        reportElements.find('input, select').prop('disabled', true);
        reportElements.find('select').prop('data-validation', '');

        allActiveReportElements.find('input, select').prop('disabled', false);
        requiredFields.prop('data-validation', 'required');
        allActiveReportElements.removeClass('hide');
    });
}(jQuery));
