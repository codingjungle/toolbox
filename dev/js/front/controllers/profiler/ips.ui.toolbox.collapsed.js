;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.collapsed', () => {
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
            if (!el.data('_loadedCollapsed')) {
                let mobject = _objectCollapsed(el, options);
                mobject.init();
                el.data('_loadedCollapsed', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedCollapsed') ){
                return $( elem ).data('_loadedCollapsed');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'toolboxcollapsed', ips.ui.toolbox.collapsed, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectCollapsed = function(elem, options) {
        let init = () => {
            elem.on('click', function(){
                let d = elem.next();
                if(d.hasClass('closed')){
                    elem.addClass('fa-rotate-90');
                    d.show().removeClass('closed');
                }
                else{
                    elem.removeClass('fa-rotate-90');
                    d.hide().addClass('closed');
                }
            })
        };
        return {
            init: init
        }
    };
}(jQuery, _));
