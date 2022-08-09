;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxprettyprint', () => {
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
            if (!el.data('_loadedToyboxprettyprint')) {
                let mobject = _objectToyboxprettyprint(el, options);
                mobject.init();
                el.data('_loadedToyboxprettyprint', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedToyboxprettyprint') ){
                return $( elem ).data('_loadedToyboxprettyprint');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget(
            'toolboxtoyboxprettyprint',
            ips.ui.toolbox.toyboxprettyprint,
            ['depth']
        );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToyboxprettyprint = function(elem, defaults) {
        let options = {
                depth: 0
            },
            init = () => {
                options = $.extend(options,defaults);
            let ops = {
                hoverPreviewEnabled: true,
                hoverPreviewArrayCount: 100,
                hoverPreviewFieldCount: 5,
                theme: 'dark',
                animateOpen: true,
                animateClose: true,
                useToJSON: true,
                maxArrayItems: 100,
                exposePath: false,
                depth:1
            },
                data = JSON.parse(elem.html()),
                formatter = new JSONFormatter(data,2,ops);
            elem.html(formatter.render());
            formatter.openAtDepth(options.depth);

        };
        return {
            init: init
        }
    };
}(jQuery, _));
