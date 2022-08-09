;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.dtprofiler.dtprofiler', function () {
        // Functions that become public methods
        var dialogId = null,
            respond = function (elements) {
                let elem = $(elements);
                if (!elem.data('_respond')) {
                    elem.css({zIndex:ips.ui.zIndex()});
                    let h1 = $('#dtProfilerOther').outerHeight(),
                        pl = $('<div></div>'),
                        h = elem.parent().outerHeight();
                    // pl.attr('id','dtProfilerPlaceHolderOther');
                    // pl.css({height:h1});
                    // $('body').prepend(pl);
                    // $('.dtProfilerPlaceHolderOther').css('height', h);
                    // $('#dtProfilerOther').css({height:h1});
                    // $('#dtProfilerOther').css({bottom:h});
                    if ($('#elHideMenu').length !== 0) {
                        $('#elHideMenu').css({bottom: h});
                    }
                    if ($('#elReorderAppMenu').length !== 0) {
                        $('#elReorderAppMenu').css({bottom: h});
                    }
                    $(window).on('resize', function () {
                        let h = elem.parent().outerHeight();
                        $('.dtProfilerPlaceHolder').css('height', h);
                        if ($('#elHideMenu').length !== 0) {
                            $('#elHideMenu').css({bottom: h});
                        }
                        if ($('#elReorderAppMenu').length !== 0) {
                            $('#elReorderAppMenu').css({bottom: h});
                        }
                    });
                    let foo = {};
                    foo.one = 1;
                    foo.two = {t:1,v:2,q: {a:4,b:23,c:85,d:{e:1,ds:2},asdf:5555}};
                    foo.three = 3;
                    foo.four = 4;
                    _toolbox.t(foo);
                    _toolbox.l(foo);
                    _toolbox.t(foo);

                    $(document).on('click', function (e) {
                        let el = $(e.target);
                        let parent = el.parents('div#dtProfilerBarContainer');
                        if (parent.length === 0) {
                            elem.find('ul.isOpen').removeClass('isOpen').slideUp().parent().find('i.dtprofilearrow').removeClass('fa-rotate-180');
                        }
                    });

                    elem.find('[data-clear]').on('click', function () {
                        let $this = $(this),
                            parent = $this.closest('ul.ipsList_reset');
                        parent.find('li:not(.notme)').each(function () {
                            $(this).remove();
                        });
                        parent.prev().find('.dtprofilerCount').html(0);
                    });
                    elem.find('> li.isParent').on('click', function () {
                        closeDialog();
                        let el = $(this);
                        if (el.is('i')) {
                            el = el.parent('li');
                        }
                        el.removeClass('dtprofilerFlash');
                        let bottom = $('#dtProfilerBarContainer').outerHeight(),
                            id = el.attr('id') + '_list',
                            child = $('#' + id), left = el.position().left;

                        if (!child.hasClass('isOpen')) {
                            child.show();
                            $(document).trigger('contentChange',[el]);
                            if (child.hasClass('dtProfilerMaxWidth')) {
                                left = 0;
                            } else {
                                let cWidth = child.outerWidth();
                                let cPos = left + cWidth;
                                let windowWidth = $(window).width();
                                if (cPos > windowWidth) {
                                    left = left - (cPos - windowWidth);
                                }
                            }
                            child.hide();
                            elem.find('ul.isOpen').removeClass('isOpen').slideUp().parent().find('i.dtprofilearrow').removeClass('fa-rotate-180');
                            child.css('left', left).css('bottom', bottom);
                            child.addClass('isOpen').slideDown().promise().done(function () {
                                if(el.attr('id') === 'elProfileConsoleLog'){
                                    child.animate({scrollTop: child.find('li:last').position().top - 10 },500);
                                }
                                // child.addClass('dtProfileMinHeight');
                            });
                            el.find('i.dtprofilearrow').addClass('fa-rotate-180');
                        } else {
                            child.removeClass('isOpen').removeClass('dtProfileMinHeight');
                            child.slideUp();
                            el.find('i.dtprofilearrow').removeClass('fa-rotate-180');
                        }
                    });
                    elem.data('_respond', 1);
                }
            },
            closeDialog = function () {
                if (dialogId !== null) {
                    $(document).trigger('closeDialog', {dialogID: dialogId});
                }
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('dtprofiler', ips.dtprofiler.dtprofiler);

        // Expose public methods
        return {
            respond: respond,
        };
    });
}(jQuery, _));

