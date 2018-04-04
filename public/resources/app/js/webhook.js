(function ($) {
    'use strict';
    var body = $('body'),
        webhook_data = $("#webhook"),
        pageIdCollection = [""],
        page = 0;


    // List Webhooks
    body.on('submit', 'form[name=webhook]', function (e) {
        e.preventDefault();
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
                $("html, body").animate({scrollTop: 0}, "slow");
            },
            success: function (e, response) {
                window.location.reload();
            }
        });

    });

    // View Webhook (logs)
    $('select[name=status_code]').on('change', function () {
        fetchWebhookLogs()
    });

    $(document).on('click', '.delete-webhook',function(e) {
        e.preventDefault();

        var that = $(this);
        var url = that.closest('a').attr('href');

        $.confirm({
            title: 'You are deleting this Webhook',
            content: 'Are you sure?',
            buttons: {
                confirm: function () {
                    $.post(url, function(resp){
                        var response = JSON.parse(resp);
                        if (response.success) {
                            that.closest('tr').remove();
                        }
                    });
                },
                cancel: function () {
                    return true;
                }
            }
        });
    });

    $(document).on('click', '.edit-modal',function(e) {
        e.preventDefault();

        var that = $(this);
        var url = that.closest('a').attr('href');
        $.get(url, function(resp){
            var data = JSON.parse(resp),
                href = '/organization/'+data.organization.unique_id+'/webhooks/'+data.webhook.id+'/modify',
                form = $('form[name="webhook-edit"]');
            form.find("input[name=organization_id]").val(data.organization.id);
            form.find("input[name=title]").val(data.webhook.title);
            form.find("input[name=url]").val(data.webhook.url);
            form.find("input[name=username]").val(data.webhook.username);
            form.find("input[name=password]").val(data.webhook.password);
            form.find("input[name=active]").prop('checked', data.webhook.active);
            form.attr('action', href);
            $('#editWebhookModal').modal("show");
        });
    });

    body.on('submit', 'form[name=webhook-edit]', function (e) {
        e.preventDefault();

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
                $("html, body").animate({scrollTop: 0}, "slow");
            },
            success: function (e, response) {
                window.location.reload();
            }
        });
    });

    $('.paginate-previous').click(function(){
        page--;
        $('input[name=last_id]').val(pageIdCollection[page]);
        fetchWebhookLogs()
    });


    $('.paginate-next').click(function(){
        var lastId = $('table').find('.webhook_log:last-child').attr('data-webhook_id');
        pageIdCollection.push(lastId);
        page++;
        // currentPageId = lastId;
        $('input[name=last_id]').val(lastId);
        fetchWebhookLogs()
    });

    if (webhook_data.length) {
        fetchWebhookLogs();
    }

    function setHeader(xhr) {
        xhr.setRequestHeader('accept', 'application/json');
    }

    function fetchWebhookLogs() {
        var organization = webhook_data.data('organization'),
            webhook_id = webhook_data.data('webhook_id'),
            url = "/organization/" + organization + "/webhooks/" + webhook_id,
            status_code = $('select[name=status_code]').val(),
            lastId = $('input[name=last_id]').val();

        $.ajax({
            url: url,
            type: 'GET',
            data: {'status_code': status_code, 'last_id': lastId},
            dataType: 'json',
            success: function (data) {
                populateWebhookLogTable(data, organization, webhook_id);
            },
            error: function () {
                alert('Failed to load webhook logs.');
            },
            beforeSend: setHeader
        });
    }

    function populateWebhookLogTable(data, organization, webhook_id) {

        $('#webhook_logs > tbody').empty();

        var items = [];

         if(!data){
             return;
         }
        $.each(data.webhookLogs, function (key, val) {
            items.push(
                "<tr class='webhook_log' data-webhook_id='" + val._id.$oid + "'>" +
                "<td>" + val.request_time.date + " " + val.request_time.timezone + "</td>" +
                "<td class='http_status'>" + getHttpStatusField(val.http_status) + "</td>" +
                "<td>" +
                "<a href='/organization/" + organization + "/webhooks/" + webhook_id + "/log/" + val._id.$oid + "'>" +
                "<i class='fa fa-eye'></i>" +
                "</a>" +
                "</td>" +
                "</tr>"
            );
        });

        $('#webhook_logs > tbody:last-child').append(items);

        initiateLogSelectors();
    }

    function initiateLogSelectors() {
        $('.webhook_log').each(function (key, obj) {
            var webhook_row = $(this);
            var webhook_log_id = webhook_row.data('webhook_id');
            var replay_button = webhook_row.find('.replay');
            var organization = $('#webhook').data('organization');
            var webhook_id = $('#webhook').data('webhook_id');

            replay_button.on('click', function () {
                replay_button.addClass('fa-spin');
                $.ajax({
                    url: "/organization/" + organization + "/webhooks/" + webhook_id + "/log/" + webhook_log_id + "/replay",
                    type: 'POST',
                    dataType: 'json',
                    success: function (data) {
                        var http_status_field = webhook_row.find('.http_status');
                        http_status_field.html(getHttpStatusField(data.http_status_code));
                    },
                    error: function () {
                        alert('Failed to replay webhook log.');
                    },
                    complete: function () {
                        replay_button.removeClass('fa-spin');
                    },
                    beforeSend: setHeader
                });
            });
        });
    }

    function getHttpStatusField(http_status) {
        reload = '<i class="fa fa-refresh replay" title="re-process"></i>';

        if (http_status == "201" || http_status == "200") {
            var reload = '';
        }

        return http_status + " " + reload;
    }

    // View webhook log.
    var replay_button = $("#replay_webhook_log");
    if (replay_button.length) {
        replay_button.on('click', function () {
            replay_button.addClass('fa-spin');
            $.ajax({
                url: "/organization/" + replay_button.data('organization') + "/webhooks/" + replay_button.data('webhook_id') + "/log/" + replay_button.data('webhook_log_id') + "/replay",
                type: 'POST',
                dataType: 'json',
                error: function () {
                    alert('Failed to replay webhook log.');
                },
                complete: function () {
                    location.reload();
                },
                beforeSend: setHeader
            });
        });
    }
}(jQuery));