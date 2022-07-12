;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxbitwise', () => {
        /**
         * Respond to a dialog trigger
         *
         * @param   {element}   elem        The element this widget is being created on
         * @param   {object}    options     The options passed
         * @param   {event}     e           if lazyload, event that is fire
         * @returns {void}
         */
         var respond =  function(elem, options, e)  {
            let el = $(elem);
            if (!el.data('_loadedToyboxbitwise')) {
                let mobject =  new _objectToybox(el, options);
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
        ips.ui.registerWidget( 'toolboxtoyboxbitwise', ips.ui.toolbox.toyboxbitwise);

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToybox = function(elem, options) {
        let init = () => {
                console.log('foobjkl');
                elem.on('submit',_submit);
                elem.on('change','[name="position"]',_position);
        },
            _submit = e => {
                e.preventDefault();
            },
            _position = (e) => {
            e.preventDefault();
            let el = elem.find('[name="position"]'),
                value = el.val(),
                action = ips.getSetting('baseURL')+'?app=toolbox&module=bt&controller=bt&do=bitwiseValues&position='+value;
             ajax({
                type: "GET",
                url: action,
                bypassRedirect: true,
                success: function (data) {
                  $('#elBitWiseBox').replaceWith($(data));
                }
            });
        },
            ajax = ips.getAjax();
        return {
            init: init
        }
    };
}(jQuery, _));
