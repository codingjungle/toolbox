;( function( $, _, undefined ) {
    'use strict';
    ips.createModule( 'ips.dtprofiler.debug', function() {
        var respond = function( elem, options, e ) {
            var el = $( elem );
            if ( !el.data( '_debugObj' ) ) {
                var d = _debugObj( el );
                d.init( el.data( 'url' ), el );
                el.data( '_debugObj', d );
            }
            $( 'body' ).bind( 'beforeunload', function() {
                var obj = el.data( '_debugObj' );
                obj.abort();
            } );
        };
        ips.ui.registerWidget( 'dtprofilerdebug', ips.dtprofiler.debug );
        return {
            respond: respond,
        };
    } );
    var _debugObj = function() {
        var ajax = null,
            current = null,
            aurl,
            burl,
            el,
            socket= null,
            init = function( url, elem ) {
            burl = url;
            aurl = burl + '&do=debug';
            el = elem;
            ajax = ips.getAjax();
            _debug();
            elem.find( 'li.dtProfilerClear' ).on( 'click', function( e ) {
                let el = $( this );
                let parent = el.parent( 'ul' );
                let parentId = parent.attr( 'id' );
                let pid = parentId.substr( 0, parentId.length - 5 );
                _clear();
                $( '#' + pid ).find( '.dtprofilerCount' ).html( 0 ).attr( 'data-count', 0 );

                parent.find( 'li' ).not( '.dtProfilerClear' ).each( function() {
                    $( this ).remove();
                } );

                parent.removeClass( 'isOpen' ).
                    slideUp().
                    parent().
                    find( 'i.dtprofilearrow' ).
                    removeClass( 'fa-rotate-180' );
            } );
        },
            _clear = function() {
            ajax( {
                type: 'GET',
                url: burl + '&do=clearAjax',
                bypassRedirect: true,
            } );
        }, abort = function() {
            current.abort();
        },
        _sockets = function() {
            if (socket === null || !socket.connected) {
                socket = io(
                    ips.getSetting('cj_debug_sockets_url'),
                    {
                        timeout: 20000,
                        reconnectionDelay: 2000,
                        reconnectionDelayMax: 20000,
                        reconnectionAttempts: 10,
                        cookie: false,
                    },
                );
            }
        },
            getSocket = function() {
                if (socket === null) {
                    _sockets();
                }

                return socket;
            },
         _debug = () => {
            if(ips.getSetting('cj_debug_sockets')){
                getSocket().emit('join', ips.getSetting('cj_debug_key'));
                getSocket().on('debug', function(data) {
                    console.log(90909)
                    _process(data);
                });
            }
            else {
                // current = ajax({
                //     type: 'POST',
                //     data: 'last=' + $('#elProfiledebug', el).attr('data-last'),
                //     url: aurl,
                //     dataType: 'json',
                //     bypassRedirect: true,
                //     success: function(data) {
                //        _process(data);
                //     },
                //     complete: function(data) {
                //         _debug();
                //     },
                //     error: function(data) {
                //     },
                // });
            }
        },
        _process = (data)=>{
            var countEl = el.find('#elProfiledebug').
                find('.dtprofilerCount');

            if (!data.hasOwnProperty('error')) {
                $('#elProfiledebug_list', el).append(data.items);
                var count = Number(countEl.attr('data-count'));
                count = Number(data.count) + count;
                countEl.html(count).attr('data-count', count);
                countEl.parent().addClass('dtprofilerFlash');
                $('#elProfiledebug', el).
                    attr('data-last', data.last);
                if ($('#elProfiledebug', el).hasClass('ipsHide')) {
                    $('#elProfiledebug', el).removeClass('ipsHide');
                }
                countEl.parent().addClass('dtprofilerFlash');
            }
        };

        return {
            init: init,
            abort: abort,
        };
    };
}( jQuery, _ ) );
