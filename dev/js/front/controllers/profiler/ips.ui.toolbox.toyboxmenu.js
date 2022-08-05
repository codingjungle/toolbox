;(function ($, _, undefined) {
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxmenu', () => {
        /**
         * Respond to a dialog trigger
         *
         * @param   {element}   elem        The element this widget is being created on
         * @param   {object}    options     The options passed
         * @param   {event}     e           if lazyload, event that is fire
         * @returns {void}
         */
        const respond = (elem, options, e) => {
                let el = $(elem);
                if (!el.data('_loadedToyboxmenu')) {
                    let mobject = _objectToyboxmenu(el, options);
                    mobject.init();
                    el.data('_loadedToyboxmenu', mobject);
                }
            },
            /**
             * Retrieve the instance (if any) on the given element
             *
             * @param    {element}    elem        The element to check
             * @returns {mixed}    The instance or undefined
             */
            getObj = (elem) => {
                if ($(elem).data('_loadedToyboxmenu')) {
                    return $(elem).data('_loadedToyboxmenu');
                }
                return undefined;
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget(
            'toolboxtoyboxmenu',
            ips.ui.toolbox.toyboxmenu,
            []
        );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToyboxmenu = function (elem, options) {
        let init = () => {
                elem.css({zIndex: ips.ui.zIndex()});
                elem.on('click', '[data-open]', _open);
                $(document).not(elem).on('click', function (e) {
                    let target = $(e.target);
                    if (target.parents('.dtProfilerOther').length === 0) {
                        $('#dtProfilerOther').find('.dtProfilerOtherSub').each(function () {
                            $(this).hide().removeData('isOpen');
                        });
                    }
                });
            },
            _open = (e) => {
                elem.find('.dtActive').each(function () {
                    $(this).removeClass('dtActive');
                });
                e.preventDefault();
                let target = $(e.currentTarget),
                    id = target.attr('id'),
                    parent = target.parent(),
                    child = $('#' + id + '_menu');
                if (child.data('isOpen') !== 1) {
                    let top = target.parent().outerHeight();
                    if (parent.hasClass('isParent')) {
                        elem.find('.ipsHide').each(function () {
                            if ($(this).attr('id') !== child.attr('id')) {
                                $(this).hide().removeData('isOpen');
                            }
                        });
                    } else {
                        elem.find('.dtProfilerOtherSubChild').each(function () {
                            if ($(this).attr('id') !== child.attr('id')) {
                                $(this).hide().removeData('isOpen');
                            }
                        });
                    }
                    child.data('isOpen', 1);
                    if (parent.hasClass('isParent')) {
                        child.css({
                            zIndex: ips.ui.zIndex(),
                        });
                    } else {
                        target.addClass('dtActive');
                        child.css({
                            zIndex: ips.ui.zIndex(),
                            right: Math.abs(target.outerWidth() + 5)
                        });
                    }

                    child.show();
                } else {
                    child.hide().removeData('isOpen');
                    target.removeClass('dtActive');
                }
            };
        return {
            init: init
        }
    };
}(jQuery, _));
