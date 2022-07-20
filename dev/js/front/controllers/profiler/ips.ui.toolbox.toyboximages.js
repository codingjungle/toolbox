;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.ui.toolbox.toyboximages', () => {
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
            if (!el.data('_loadedToyboximages')) {
                let mobject = _objectToyboximages(el, options);
                mobject.init();
                el.data('_loadedToyboximages', mobject);
            }
        }, /**
         * Retrieve the instance (if any) on the given element
         *
         * @param  {element}  elem    The element to check
         * @returns {mixed}  The instance or undefined
         */
        getObj = (elem) => {
            if ($(elem).data('_loadedToyboximages')) {
                return $(elem).data('_loadedToyboximages');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget(
            'toolboxtoyboximages',
            ips.ui.toolbox.toyboximages,
            []
        );

        // Expose public methods
        return {
            respond: respond, getObj: getObj,
        };
    });
    const _objectToyboximages = function (elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('click', '[data-convert]', _convert);
            },
            _convert = (e) => {
                e.preventDefault();
                let url = ips.getSetting('baseURL') +
                        'index.php?app=toolbox&module=bt&controller=bt&do=images',
                    url2 = ips.getSetting('baseURL') +
                        'index.php?app=toolbox&module=bt&controller=bt&do=download&path=';
                ajax({
                    type: 'POST',
                    data: elem.find('form').serialize(),
                    url: url,
                    bypassRedirect: true,
                    showLoading: true,
                    success: function (data) {
                        let container = elem.find('#dl');
                        container.find('a').attr('href', url2 + data.path);
                        container.find('img').attr('src', data.url);
                        container.show();
                        $('#elSelect_js_dtprofilerImagesConverter_to').val('png');
                        elem.find('[data-ipsUploader]').each(function (i, elem) {
                            ips.ui.uploader.refresh(elem);
                        });
                    },
                });
            };
        return {
            init: init,
        };
    };
}(jQuery, _));
