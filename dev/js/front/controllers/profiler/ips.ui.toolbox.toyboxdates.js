;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxdates', () => {
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
            if (!el.data('_loadedToyboxdates')) {
                let mobject = _objectToyboxdates(el, options);
                mobject.init();
                el.data('_loadedToyboxdates', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedToyboxdates') ){
                return $( elem ).data('_loadedToyboxdates');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'toolboxtoyboxdates', ips.ui.toolbox.toyboxdates, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToyboxdates = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange change','[data-input]',_process);
            },
            _process = (e) => {
                let target = $(e.currentTarget),
                    type = target.attr('data-input'),
                    number = target.val(),
                    url = ips.getSetting('baseURL')+'index.php?app=toolbox&module=bt&controller=bt&do=dates&type='+type+'&time='+number;
                ajax({
                    type: "GET",
                    url: url,
                    bypassRedirect: true,
                    success: function (data) {

                            elem.find('#unix').val(data.unix);
                            elem.find('#iso').val(data.iso);
                            elem.find('#date').val(data.date);
                    }
                });
            };
      return {
        init: init
      }
    };
}(jQuery, _));
