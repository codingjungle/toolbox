;(function($, _, undefined) {
    'use strict';
    ips.createModule('ips.toolbox.main', function() {
        var socket = null,
            init = function() {
              let x = 5,y = 7,obj = {};
              obj.one = 1;
              obj.two = 2;
              obj.three = 3;
              obj.four = ["bar",'k','h','u'];
              console.table(obj)
              getSocket().emit('join', ips.getSetting('cj_debug_key'));
            },
            sockets = function() {
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
