;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxuuid', () => {
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
            if (!el.data('_loadedToyboxuuid')) {
                let mobject = _objectToyboxuuid(el, options);
                mobject.init();
                el.data('_loadedToyboxuuid', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedToyboxuuid') ){
                return $( elem ).data('_loadedToyboxuuid');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'toolboxtoyboxuuid', ips.ui.toolbox.toyboxuuid, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToyboxuuid = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
            elem.on('click','#clearuuid',_clear);
            elem.on('click','#generate',_generate);
            },
            _generate = e => {
                let form = elem.find('form:first'),
                    values = form.serialize(),
                    action = ips.getSetting('baseURL')+'?app=toolbox&module=bt&controller=bt&do=uuid';
                ajax({
                    type: "POST",
                    url: action,
                    data:values,
                    bypassRedirect: true,
                    success: function (data) {
                        $('#elUuidContainer').append($(data));
                    }
                });
            },
        _clear = e => {
            let target = $(e.currentTarget);
            $('#elUuidContainer').html('');
        };
        return {
            init: init
        }
    };
}(jQuery, _));
