;(function($, _, undefined) {
    'use strict';
    ips.createModule('ips.toolbox.main', function() {
        var socket = null,
            init = function() {
                if(ips.getSetting('cj_debug_sockets')) {
                    getSocket().emit('join', ips.getSetting('cj_debug_key'));
                }
            },
            sockets = function() {
                if(ips.getSetting('cj_debug_sockets')) {
                    if (socket === null || !socket.connected) {
                        socket = io(
                            ips.getSetting('cj_debug_sockets_url'),
                            {
                                timeout: 20000,
                                reconnectionDelay: 2000,
                                reconnectionDelayMax: 20000,
                                reconnectionAttempts: 1,
                                cookie: false,
                            },
                        );
                    }
                }
            },
            getSocket = function() {
                if (socket === null) {
                    sockets();
                }

                return socket;
            };
        return {
            init: init,
            getSocket:getSocket
        };
    });
}(jQuery, _));
