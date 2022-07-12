;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxbitwise', ()=>{
        /**
         * Respond to a dialog trigger
         *
         * @param   {element}   elem        The element this widget is being created on
         * @param   {object}    options     The options passed
         * @param   {event}     e           if lazyload, event that is fire
         * @returns {void}
         */
         var respond =  (elem, options, e) => {
            let el = $(elem);
            if (!el.data('_loadedToyboxbitwise')) {
                var mobject = new _objectToyboxbitwise(el, options);
                mobject.init();
                el.data('_loadedToyboxbitwise', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedToyboxbitwise') ){
                return $( elem ).data('_loadedToyboxbitwise');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'toolboxtoyboxbitwise', ips.ui.toolbox.toyboxbitwise, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    var _objectToyboxbitwise = (elem, options) => {
        var init = () => {
            elem.on('submit',_submit);
        },
        _submit = () => {

        };
        return {
            init: init
        }
    };
}(jQuery, _));
