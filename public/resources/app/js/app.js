var Marketplace = function($){
    var body = $('body'),
        currentPage = 1;

    $( document ).ready(function(){
        renderSelect2();
    });

    $( document ).ajaxSuccess(function( event, xhr, settings ) {
        renderSelect2();
    });

    $('.modal').on('show.bs.modal', function() {
        renderSelect2(true);
    });

    var renderSelect2 = function(force) {
        $('select').each(function(key, value) {
            var that = $(this),
                closeOnSelect = !that.attr('multiple'),
                placeholder = " ";

            if(that.attr("data-ignore-select") || that.is(":disabled")) {
                return;
            }
            that.attr('id', 'select2__' + key);

            if(that.attr("data-placeholder")) {
                placeholder = that.attr("data-placeholder");
            }

            if(!that.attr('data-remote')) {
                that.select2({
                    theme: "bootstrap",
                    width: "100%",
                    placeholder: placeholder
                });
            }

            if(that.attr("data-remote") && (that.hasClass("select2-hidden-accessible") === false && force === undefined)) {
                var minCharacters = that.attr('data-min-characters') === undefined ? 3 : that.attr('data-min-characters'),
                    offset = that.attr('data-offset') === undefined ? 10 : parseInt(that.attr('data-offset')),
                    loader = $('<span><i class="fa fa-circle-o-notch fast-spin fa-spin"></i> Updating...</span>');

                that.select2({
                    theme: "bootstrap",
                    ajax: {
                        url: $(this).attr('data-remote'),//"https://api.github.com/search/repositories",
                        dataType: 'json',
                        delay: 300,
                        data: function(params, offset) {
                            var obj = Object.create(null),
                                attrs = that[0].attributes;

                            for (var i=0; i<attrs.length; i++) {
                                if (attrs[i].name.indexOf("data-filter") === 0) {
                                    obj[attrs[i].name.replace('data-filter-','')] = attrs[i].value;
                                }
                            }

                            obj.name = obj.title = params.term;
                            obj.page = currentPage;
                            obj.offset = offset;
                            return obj;
                        },
                        processResults: function(data, params) {

                            var results, count;

                            results = $.map(data, function (obj) {
                                if(!that.attr('data-name')) {
                                    obj.text = obj.name !== undefined ? obj.name : obj.title;
                                } else {
                                    obj.text = obj[that.attr('data-name')];
                                }
                                if(that.attr('data-unique-id') !== undefined) {
                                    //@TODO this can be refactored later...
                                    if(that.attr('data-unique-id') === "" || that.attr('data-unique-id') === "true"  && obj.unique_id !== undefined) {
                                        obj.id = obj.unique_id; // Unique ID will supersede ID.
                                    } else {
                                        obj.id = obj[that.attr('data-unique-id')];
                                    }
                                }

                                return obj;
                            });

                            count = results.length;
                            params.page = currentPage = params.page || 1;
                            return {
                                results: results,
                                pagination: {
                                    more: count >= offset
                                }
                            };
                        },
                        cache: true
                    },
                    escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                    minimumInputLength: minCharacters,
                    templateResult: function(response) {
                        if (response.loading) {
                            return loader;
                        }
                        return '<div>'+response.text+'</div>';
                    }, // omitted for brevity, see the source of this page
                    templateSelection: function(response) { return response.text; },
                    width: "100%",
                    closeOnSelect: closeOnSelect,
                    allowClear: true,
                    placeholder: " ",
                    language: {
                        errorLoading: function() {
                            return loader;
                        },
                        searching: function() {
                            return loader;
                        }
                    }
                });
            }
        });
    };

    body.on('click', '[data-target="#modal-dialog"]', function(e){
        var that = $(this),
            // color = that.data('action-type') === 'insert' ? 'modal-success' : 'modal-lilac',
            size = that.attr('data-size') === 'large' ? 'modal-lg' : 'modal-md',
            modal = $('#modal-dialog');

        // modal.removeClass('modal-success modal-lilac').addClass(color);
        modal.find('.modal-dialog').removeClass('modal-lg modal-md').addClass(size);
    });

    body.on('change', 'select[name=organization]', function(e){
        var that = $(this),
            requiresOrganizationFilter;

        //If it's in a modal, we only want to change that, this prevents filters from being impacted.
        if(($("#modal-dialog").data('bs.modal') || {}).isShown) {
            requiresOrganizationFilter = $('#modal-dialog').find('[data-filter-organization]');
        } else {
            requiresOrganizationFilter = $('[data-filter-organization]');
        }

        //@TODO check if it is in a modal, if it's in a modal, make sure the updated elements in modal.
        requiresOrganizationFilter.each(function(key, el) {
            $(el).val(null).trigger('change');
            if(that.val() === null || that.val() === "") {
                $(el).prop('disabled', true);
                return;
            }
            $(el).attr('data-filter-organization', that.val());
            $(el).removeAttr('disabled');
            renderSelect2();
        });
    });


    return {
        reloadSelect2: function(){
            renderSelect2();
        },
        // =========================================================================
        // CONSTRUCTOR APP
        // =========================================================================
        init: function () {
            Marketplace.handleAjaxCallbacks();
            Marketplace.handleIE();
            Marketplace.handleCheckCookie();
            Marketplace.handleBackToTop();
            Marketplace.handleSidebarNavigation();
            Marketplace.handleSidebarResponsive();
            Marketplace.handleFullscreen();
            Marketplace.handleTooltip();
            Marketplace.handlePopover();
            Marketplace.handlePanelToolAction();
            Marketplace.handleBoxModal();
            Marketplace.initCopyrightYear();
        },

        handleAjaxCallbacks: function() {
            $(document).ajaxComplete(function() {
                $('[data-toggle="tooltip"]').tooltip();
                $('[data-toggle=popover]').popover({ trigger: "hover", container: "body"});
            });
            //Set global ajax callback to check for redirects
            var origOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, requestedURL) {
                this.addEventListener('load', function() {
                    if(this.getResponseHeader('auth-redirect') === '1') {
                        window.location = baseURL;
                    }
                });
                origOpen.apply(this, arguments);
            };
        },

        // =========================================================================
        // IE SUPPORT
        // =========================================================================
        handleIE: function () {
            // IE mode
            var isIE8 = false;
            var isIE9 = false;
            var isIE10 = false;

            // initializes main settings for IE
            isIE8 = !! navigator.userAgent.match(/MSIE 8.0/);
            isIE9 = !! navigator.userAgent.match(/MSIE 9.0/);
            isIE10 = !! navigator.userAgent.match(/MSIE 10.0/);

            if (isIE10) {
                $('html').addClass('ie10'); // detect IE10 version
            }

            if (isIE10 || isIE9 || isIE8) {
                $('html').addClass('ie'); // detect IE8, IE9, IE10 version
            }

            // Fix input placeholder issue for IE8 and IE9
            if (isIE8 || isIE9) { // ie8 & ie9
                // this is html5 placeholder fix for inputs, inputs with placeholder-no-fix class will be skipped(e.g: we need this for password fields)
                $('input[placeholder]:not(.placeholder-no-fix), textarea[placeholder]:not(.placeholder-no-fix)').each(function () {
                    var input = $(this);

                    if (input.val() == '' && input.attr("placeholder") != '') {
                        input.addClass("placeholder").val(input.attr('placeholder'));
                    }

                    input.focus(function () {
                        if (input.val() == input.attr('placeholder')) {
                            input.val('');
                        }
                    });

                    input.blur(function () {
                        if (input.val() == '' || input.val() == input.attr('placeholder')) {
                            input.val(input.attr('placeholder'));
                        }
                    });
                });
            }
        },

        // =========================================================================
        // CHECK COOKIE
        // =========================================================================
        handleCheckCookie: function () {
            // Check (onLoad) if the cookie is there and set the class if it is
            // Set cookie sidebar minimize page
            if ($.cookie('page_sidebar_minimize') == 'active') {
                $('body').addClass('page-sidebar-minimize');
            }
        },
        // =========================================================================
        // BACK TOP
        // =========================================================================
        handleBackToTop: function () {
            $(window).scroll(function () {
                if ($(this).scrollTop() > 100) {
                    $('#back-top').removeClass('hide').addClass('show animated pulse');
                } else {
                    $('#back-top').addClass('hide').removeClass('show animated pulse');
                }
            });
            // scroll body to 0px on click
            $('#back-top').click(function () {
                $('body,html').animate({
                    scrollTop: 0
                }, 800);
                return false;
            });
        },

        // =========================================================================
        // SIDEBAR NAVIGATION
        // =========================================================================
        handleSidebarNavigation: function () {
            // Create trigger click for open menu sidebar
            $('.submenu > a').click(function() {
                var parentElement = $(this).parent('.submenu'),
                    nextElement = $(this).nextAll(),
                    arrowIcon = $(this).find('.arrow'),
                    plusIcon = $(this).find('.plus');

                if(parentElement.parent('ul').find('ul:visible')){
                    parentElement.parent('ul').find('ul:visible').slideUp('fast');
                    parentElement.parent('ul').find('.open').removeClass('open');
                }

                if(nextElement.is('ul:visible')) {
                    arrowIcon.removeClass('open');
                    plusIcon.removeClass('open');
                    nextElement.slideUp('fast');
                    arrowIcon.removeClass('fa-angle-double-down').addClass('fa-angle-double-right');
                }

                if(!nextElement.is('ul:visible')) {
                    arrowIcon.addClass('open');
                    plusIcon.addClass('open');
                    nextElement.slideDown('fast');
                    arrowIcon.removeClass('fa-angle-double-right').addClass('fa-angle-double-down');
                }

            });
        },

        // =========================================================================
        // SIDEBAR RESPONSIVE
        // =========================================================================
        handleSidebarResponsive: function () {
            // Optimalisation: Store the references outside the event handler:
            var $window = $(window);
            function checkWidth() {
                var windowsize = $window.width();
                // Check if view screen on greater then 720px and smaller then 1024px
                if (windowsize > 768 && windowsize <= 1024) {
                    $('body').addClass('page-sidebar-minimize-auto');
                }
                else if (windowsize <= 768) {
                    $('body').removeClass('page-sidebar-minimize');
                    $('body').removeClass('page-sidebar-minimize-auto');
                }
                else{
                    $('body').removeClass('page-sidebar-minimize-auto');
                }
            }
            // Execute on load
            checkWidth();
            // Bind event listener
            $(window).resize(checkWidth);

            // When the minimize trigger is clicked
            $('.navbar-minimize a').on('click',function(){

                // Check class sidebar right show
                if($('.page-sidebar-right-show').length){
                    $('body').removeClass('page-sidebar-right-show');
                }

                // Check class sidebar minimize auto
                if($('.page-sidebar-minimize-auto').length){
                    $('body').removeClass('page-sidebar-minimize-auto');
                }else{
                    // Toggle the class to the body
                    $('body').toggleClass('page-sidebar-minimize');
                }

                // Check the current cookie value
                // If the cookie is empty or set to not active, then add page_sidebar_minimize
                if ($.cookie('page_sidebar_minimize') == "undefined" || $.cookie('page_sidebar_minimize') == "not_active") {

                    // Set cookie value to active
                    $.cookie('page_sidebar_minimize','active', {expires: 1});
                }

                // If the cookie was already set to active then remove it
                else {

                    // Remove cookie with name page_sidebar_minimize
                    $.removeCookie('page_sidebar_minimize');

                    // Create cookie with value to not_active
                    $.cookie('page_sidebar_minimize','not_active',  {expires: 1});

                }

            });

            $('.navbar-setting a').on('click',function(){
                if($('.page-sidebar-minimize.page-sidebar-right-show').length){
                    $('body').toggleClass('page-sidebar-minimize page-sidebar-right-show');
                }
                else if($('.page-sidebar-minimize').length){
                    $('body').toggleClass('page-sidebar-right-show');
                }else{
                    $('body').toggleClass('page-sidebar-minimize page-sidebar-right-show');
                }
            });

            // This action available on mobile view
            $('.navbar-minimize-mobile.left').on('click',function(){
                // Add effect sound button click
                if($('body.page-sidebar-right-show').length){
                    $('body').removeClass('page-sidebar-right-show');
                    $('body').removeClass('page-sidebar-minimize');
                }
                $('body').toggleClass('page-sidebar-left-show');
            });
            $('.navbar-minimize-mobile.right').on('click',function(){
                // Add effect sound button click
                if($('body.page-sidebar-left-show').length){
                    $('body').removeClass('page-sidebar-left-show');
                    $('body').removeClass('page-sidebar-minimize');
                }
                $('body').toggleClass('page-sidebar-right-show');
            });
        },

        // =========================================================================
        // FULLSCREEN TRIGGER
        // =========================================================================
        handleFullscreen: function () {
            var state;
            $('#fullscreen').on('click', function() {
                state = !state;
                if (state) {
                    $(this).toggleClass('fg-theme');
                    $(this).attr('data-original-title','Exit Fullscreen');
                    var docElement, request;
                    docElement = document.documentElement;
                    request = docElement.requestFullScreen || docElement.webkitRequestFullScreen || docElement.mozRequestFullScreen || docElement.msRequestFullScreen;
                    if(typeof request!="undefined" && request){
                        request.call(docElement);
                    }
                } else {
                    // Trigger for exit fullscreen
                    // Add effect sound bell ring
                    $(this).removeClass('fg-theme');
                    $(this).attr('data-original-title','Fullscreen')
                    var docElement, request;
                    docElement = document;
                    request = docElement.cancelFullScreen|| docElement.webkitCancelFullScreen || docElement.mozCancelFullScreen || docElement.msCancelFullScreen || docElement.exitFullscreen;
                    if(typeof request!="undefined" && request){
                        request.call(docElement);
                    }
                }
            });
        },

        // =========================================================================
        // TOOLTIP
        // =========================================================================
        handleTooltip: function () {
            if($('[data-toggle=tooltip]').length){
                $('[data-toggle=tooltip]').tooltip({
                    animation: 'fade',
                    container: 'body'
                });
            }
        },

        // =========================================================================
        // POPOVER
        // =========================================================================
        handlePopover: function () {
            if($('[data-toggle=popover]').length){
                $('[data-toggle=popover]').popover({ trigger: "hover", container: "body"});
            }
        },

        // =========================================================================
        // PANEL TOOL ACTION
        // =========================================================================
        handlePanelToolAction: function () {
            // Collapse panel
            $('[data-action=collapse]').on('click', function(e){
                var targetCollapse = $(this).parents('.panel').find('.panel-body'),
                    targetCollapse2 = $(this).parents('.panel').find('.panel-sub-heading'),
                    targetCollapse3 = $(this).parents('.panel').find('.panel-footer')
                if((targetCollapse.is(':visible'))) {
                    $(this).find('i').removeClass('fa-angle-up').addClass('fa-angle-down');
                    targetCollapse.slideUp();
                    targetCollapse2.slideUp();
                    targetCollapse3.slideUp();
                }else{
                    $(this).find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
                    targetCollapse.slideDown();
                    targetCollapse2.slideDown();
                    targetCollapse3.slideDown();
                }
                e.stopImmediatePropagation();
            });

            // Remove panel
            $('[data-action=remove]').on('click', function(){
                $(this).parents('.panel').fadeOut();
                // Remove backdrop element panel full size
                if($('body').find('.panel-fullsize').length)
                {
                    $('body').find('.panel-fullsize-backdrop').remove();
                }
            });

            // Expand panel
            $('[data-action=expand]').on('click', function(){
                if($(this).parents(".panel").hasClass('panel-fullsize'))
                {
                    $('body').find('.panel-fullsize-backdrop').remove();
                    $(this).data('bs.tooltip').options.title = 'Expand';
                    $(this).find('i').removeClass('fa-compress').addClass('fa-expand');
                    $(this).parents(".panel").removeClass('panel-fullsize');
                }
                else
                {
                    $('body').append('<div class="panel-fullsize-backdrop"></div>');
                    $(this).data('bs.tooltip').options.title = 'Minimize';
                    $(this).find('i').removeClass('fa-expand').addClass('fa-compress');
                    $(this).parents(".panel").addClass('panel-fullsize');
                }
            });

            // Search panel
            $('[data-action=search]').on('click', function(){
                $(this).parents('.panel').find('.panel-search').toggle(100);
                return false;
            });

        },

        // =========================================================================
        // BOX MODAL
        // =========================================================================
        handleBoxModal: function () {

            $(document).on("hidden.bs.modal", ".modal:not(.local-modal, #editWebhookModal)", function (e) {
                $(this).removeData("bs.modal").find(".modal-content").empty();
            });

            $('.calendar').datepicker({
                format: 'yyyy-mm-dd'
            });

            $('.calendar').on('changeDate', function() {
                var that = $(this),
                    parent = that.parents('.date-calendar-range'),
                    input = that.parent().find('input[type=hidden]');

                input.val(that.datepicker('getFormattedDate'));
                if(input.attr('name') === 'start_date') {
                    parent.find('.calendar.end-date').datepicker('setStartDate', input.val()).focus();
                }
            });

            $('.date-pickers').datepicker({
                format: 'mm/dd/yyyy',
                todayBtn: 'linked'
            });

            $('.modal').on('loaded.bs.modal', function () {
                $('.date-pickers').datepicker({
                    format: 'mm/dd/yyyy',
                    todayBtn: 'linked'
                });
            });

            $('#lock-screen').on('click', function(){
                window.location = $('#lock-screen').data('url');
            });

            $('#logout').on('click', function(){
                window.location = $('#logout').data('url');
            });
        },

        // =========================================================================
        // COPYRIGHT YEAR
        // =========================================================================
        initCopyrightYear : function () {
            if($('#copyright-year').length){
                var today = new Date();
                $('#copyright-year').text(today.getFullYear());
            }
        }
    };
}(jQuery);

// Call main app init
Marketplace.init();
