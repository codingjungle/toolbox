;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.toolbox.toyboxdiffs', () => {
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
            if (!el.data('_loadedToyboxdiffs')) {
                let mobject = _objectToyboxdiffs(el, options);
                mobject.init();
                el.data('_loadedToyboxdiffs', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedToyboxdiffs') ){
                return $( elem ).data('_loadedToyboxdiffs');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'toolboxtoyboxdiffs', ips.ui.toolbox.toyboxdiffs, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectToyboxdiffs = function(elem, options) {
        let init = () => {
                elem.on('keyup input propertychange','#source1',_process);
                elem.on('keyup input propertychange','#source2',_process);
                elem.on('click','[data-clear]',_clear);
            _process();
        },
            _clear = (e) => {
                let target = $(e.currentTarget),
                    source = $(target.attr('data-clear'));
                source.val('');
              _process();
            },
        _process = () => {
            let s1 = elem.find('#source1').val(),
                s2 = elem.find('#source2').val(),
                diffsArea = elem.find('#diffs'),
                changes = Diff.diffWordsWithSpace(s1,s2);
            diffsArea.html('<pre></pre>');
            changes.forEach((part) => {
                // green for additions, red for deletions
                // grey for common parts
                let val = part.value,
                    color = part.added ? 'dtProfilerAdded' :
                    part.removed ? 'dtProfilerRemoved' : 'dtProfilerUnchanged',
                    line = $('<span></span>');
                line.addClass(color).html(val);
                diffsArea.find('pre:first').append(line);
            });
        };
        return {
            init: init
        }
    };
}(jQuery, _));
