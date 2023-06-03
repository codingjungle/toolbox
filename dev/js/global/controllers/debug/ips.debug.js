'use strict';
var _cjProfilerP = _cjProfilerP || {},
    ogLog = console.log,
    ogTable = console.table,
    ogAssert = console.assert,
    ogClear = console.clear,
    ogCount = console.count,
    ogError = console.error,
    ogGroup = console.group,
    ogGroupCollapsed = console.groupCollapsed,
    ogGroupEnd = console.groupEnd,
    ogInfo = console.info,
    ogTime = console.time,
    ogTimeEnd = console.timeEnd,
    ogTrace = console.trace,
    ogWarn = console.warn,
    _cjProfilerQue = null,
    _cjProfilerQueue = {},
    _cjProfilerTimers = {},
    _cjProfilerCount = 1,
    _cjProfilerPindex = 1,
    _cjProfilerQindex = 0,
    _cjProfilerMin = 5,
    _addToQueue = (html, count, type) => {
        _cjProfilerPindex++;
        _cjProfilerQueue[_cjProfilerPindex] = {
            count: count,
            html: html,
            type: type
        };
        _cjProfilerQindex = 0;
    },
    _process = (html, count = 1, type = 'log') => {
        var el = $('#elProfileConsoleLog'),
            container = $('#elProfileConsoleLog_list'),
            last = container.find('li:last-child').find('div.dtProfilerGroup'),
            countEl = el.find('.dtprofilerCount');
        if (el.length === 0) {
            _addToQueue(html, count, type);
            return;
        }
        if (last.length !== 0 && type !== 'groupEnd') {
            last.find('ul:first').append(html);
        } else {
            container.append(html);
        }
        var cc = Number(countEl.attr('data-count'));
        cc = Number(count) + cc;
        countEl.html(cc).attr('data-count', cc);
        el.addClass('dtprofilerFlash');
        let debugEnabled = Debug.isEnabled();
        Debug.setEnabled(false);
        container.trigger('contentChange', [container.parent()]);
        Debug.setEnabled(debugEnabled);
    },
    isEmpty = (obj) => {
        return Object.keys(obj).length === 0;
    },
    getStackTrace = (type = 'log', min = 5, linkify = true) => {
        let file,
            other,
            line = 0,
            path,
            url,
            matches,
            main = '',
            first = null,
            error = new Error(),
            stack = error.stack || '';
        matches = stack.match(/\bhttps?:\/\/\S+\)/gi);

        stack = stack.split('\n').map(function (line) {
            return line.trim();
        });
        // ogLog(stack);
        //
        // ogLog(matches);
        if (linkify && dtProfilerEditor) {
            main += '<div class="dtProfilerBacktrace">via: console.' + type + '()';
        } else {
            main += 'via: console.' + type + '()';
        }
        $.each(stack, (index, value) => {
            if (!value.includes('Error') &&
                !value.includes('ips.debug.js') &&
                !value.includes('debugger') &&
                !value.includes('Debug.js')
            ) {
                let srg = new RegExp(/\?v=(.*?):/g);
                let vf = value.replace(srg, ':');
                if (linkify && dtProfilerEditor) {
                    matches = value.match(/\bhttps?:\/\/\S+/gi);
                    let isNull = false
                    if (_.isNull(matches)) {
                        isNull = true;
                        matches = value.match(/\banonymous>\S+/gi);
                    }
                    url = matches[0].replace(')', '').split('/');
                    path = matches[0].replace(')', '').replace(dtProfilerBaseUrl, '');
                    file = url[url.length - 1];

                    if (isNull === true) {
                        path = path.split('>');
                        vf = file.replace('>', '');
                        vf = vf.replace('<', '');

                    } else {
                        path = path.split('?');
                    }
                    try {
                        other = path[1].split(':');
                        line = other[1] ?? 0;
                    } catch (error) {
                    }
                    path = path[0];
                    if (isNull === true) {
                        main += '<div>' + vf + '</div>';
                    } else {
                        let ds = '/', appPath = dtProfilerAppPath;
                        if(Boolean(useWsl) === true){
                            path = path.replaceAll('/','\\');
                            appPath = appPath.replaceAll('/','\\');
                            appPath = wslPath + appPath;

                            ds = '\\';
                        }
                        main += '<div><a href="' + dtProfilerEditor + '://open?file=' + appPath + ds + path + '&line=' + line + '">' + vf + '</a>'
                    }
                } else {
                    main += vf;
                }
            }
        });
        if (linkify && dtProfilerEditor) {
            main += '</div>';
        }

        return main;
        // stack = stack.splice(stack[0] === 'Error' ? min : 1);
        // main = stack[0];
        // //http://codingjungle.test/dev/applications/toolbox/dev/js/global/controllers/main/ips.toolbox.main.js?v=022c8961120a686efa330e667336b7cd1657607257:6:23)
        // matches = main.match(/\bhttps?:\/\/\S+/gi);
        // if (linkify && dtProfilerEditor) {
        //   url = matches[0].replace(')', '').split('/');
        //   file = url[url.length - 1];
        //
        //   path = matches[0].replace(')', '').replace(dtProfilerBaseUrl, '');
        //   path = path.split('?');
        //
        //   try {
        //     other = path[1].split(':');
        //     line = other[1] ?? 0;
        //   } catch (error) {
        //   }
        //   path = path[0];
        //
        //   return '<div><a href="' + dtProfilerEditor + '://open?file=' +
        //       dtProfilerAppPath + '/' + path + '&line=' + line + '">in ' + path +
        //       ':' + line + ' via console.' + type + '()</a></div>';
        // }
        //
        // return 'in ' + matches[0].replace(')', '') + ' via console.' + type +
        //     '()';
    };
const isJson = (data) => {
    try {
        const testIfJson = JSON.parse(data);
        if (typeof testIfJson === "object") {
            return true;
        } else {
            return false;
        }
    } catch {
        return false;
    }
};
_cjProfilerQue = setInterval(() => {
    _cjProfilerQindex++;
    if (!isEmpty(_cjProfilerQueue)) {
        $.each(_cjProfilerQueue, function (index, obj) {
            if (obj.hasOwnProperty('count')) {
                _process(obj.html, obj.count, obj.type);
                delete _cjProfilerQueue[index];
            }
        });
    }
    if (_cjProfilerQindex > 100 && Object.keys(_cjProfilerQueue).length === 0) {
        clearInterval(_cjProfilerQue);
    }
}, 10);
_cjProfilerP = function () {
    var adapters,
        _minNum = 5,
        _buildTable = (table, headers = ['Index', 'Values']) => {
            let tables = '<table class="ipsTable">';
            if (headers) {
                tables += '<tr>';
                $.each(headers, function (index, name) {
                    tables += '<th>' + name + '</th>';
                });
                tables += '<tr>';
            }
            $.each(table, function (index, value) {
                if (_.isObject(value) || _.isArray(value)) {
                    let obj = $('<div></div>');
                    obj.addClass('dark-mode');
                    obj.attr('data-ipstoolboxtoyboxprettyprint', 1);
                    tables += '<tr><td>' + index + '</td><td>' + obj.html(JSON.stringify(value)).prop("outerHTML") + '</td></tr>';
                } else {
                    tables += '<tr><td>' + index + '</td><td>' + value +
                        '</td></tr>';
                }
            });
            tables += '</table>';

            return tables;
        },
        _send = (data, type) => {
            let li = $('<li></li>'),
                container = $('<div></div>');
            li.addClass('ipsPad_half dtProfilerSearch dtProfilerType' + type);
            li.append(data);
            if (type !== 'groupEnd') {
                li.append(getStackTrace(type, getMinNum()));
            } else {
                li.removeClass('ipsPad_half dtProfilerSearch');
            }
            // ogLog(data);
            // container.html(li);
            _process(li, 1, type);
        },
        newLog = (u, type = 'log', classes = null) => {
            let nv = '',
                includeIndex = u.length > 1;
            $.each(u, (index, value) => {
                if (_.isObject(value) ) {
                    if(value instanceof jQuery){
                        ogLog(value);

                        value = "this is a jquery.fn.init object, please check the console";
                    }
                    else {
                        let v = value,
                            obj = $('<div></div>');
                        obj.addClass('dark-mode');
                        obj.attr('data-ipstoolboxtoyboxprettyprint', 1);
                        value = obj.html(JSON.stringify(v));
                    }
                }
                nv += '<div';
                if (!_.isNull(classes)) {
                    nv += ' class="' + classes + '"';
                }
                nv += '>';
                if (includeIndex) {
                    nv += index + ': ';
                }
                nv += value + '</div>';
            });
            _send(nv, type);
        },
        newTimeEnd = (label) => {
            let args = [],
                time = null;
            if (_cjProfilerTimers.hasOwnProperty(label)) {
                time = Date.now() - _cjProfilerTimers[label];
                args.push(label + ': ' + time + ' ms');
                newLog(args, 'timeEnd');
            } else {
                args.push('There are no timers for ' + label);
                newLog(args, 'timeEnd', 'warning');
            }
        },
        newGroupEnd = () => {
            _send(' ', 'groupEnd');
        },
        newGroup = (label, collapsed = false) => {
            let group = '<div class="dtProfilerGroup">';

            if (label) {
                group += '<h3>' + label + '</h3>';
            }
            if (collapsed) {
                group += '<i class="fa fa-chevron-circle-right" data-ipstoolboxcollapsed></i>';
            } else {
                group += '<i class="fa fa-chevron-circle-right fa-rotate-90" data-ipstoolboxcollapsed></i>';
            }
            group += '<ul class="ipsList_reset';
            if (collapsed) {
                group += ' closed ipsHide';
            }
            group += '"></ul></div>';
            _send(group, collapsed ? 'groupCollapsed' : 'group');
        },
        newTable = (obj, headers, type = 'table') => {
            let tables = _buildTable(obj, headers);
            _send(tables, type);
        },
        newCount = (label = 'default') => {
            let list = label + ': ' + _cjProfilerCount;
            _send(list, 'count');
            _cjProfilerCount++;
        },
        newClear = () => {
            let $this = $('#elProfileConsoleLog_list'),
                parent = $this.closest('ul.ipsList_reset');
            parent.find('li:not(.notme)').each(function () {
                $(this).remove();
            });
            parent.prev().find('.dtprofilerCount').html(0);
        },
        write = function (type, message, other = null, trace = false) {
            if (parseInt(dtProfilerDebug) === 1) {
                adapters.write(type, message, other, trace);
            }

            return _cjProfilerP;
        },
        l = function () {
            let args = Array.from(arguments);
            if (dtProfilerUseConsole) {
                newLog(args);
                return _cjProfilerP;
            } else {
                return write('log', args, null, true);
            }
        },
        t = function () {
            let args = Array.from(arguments),
                msg = args[0],
                headers = args[1] ?? ['Index', 'Values'];

            if (dtProfilerUseConsole) {
                if (_.isObject(msg) || _.isArray(msg)) {
                    newTable(msg, headers);
                } else {
                    newLog(args, 'table');
                }
                return _cjProfilerP;
            } else {
                return write('table', msg, headers, true);
            }
        },
        a = function (assertion, msg) {
            if (dtProfilerUseConsole) {
                if (assertion) {
                    let args = [];
                    args.push(msg);
                    newLog(args, 'assert');
                }
                return _cjProfilerP;
            } else {
                return write('a', true, msg, assertion);
            }
        },
        c = function () {
            if (dtProfilerUseConsole) {
                newClear();
                return _cjProfilerP;
            } else {
                return write('c');
            }
        },
        cc = function (label) {
            if (dtProfilerUseConsole) {
                newCount(label);
                return _cjProfilerP;

            } else {
                return write('cc', label, null, true);
            }

        },
        e = function (msg) {
            if (dtProfilerUseConsole) {
                let args = [];
                args.push(msg);
                newLog(args, 'error');
                return _cjProfilerP;
            } else {
                return write('e', msg, null, true);
            }
        },
        g = function (label) {
            if (dtProfilerUseConsole) {
                let args = [];
                args.push(label);
                newGroup(args);
                return _cjProfilerP;
            } else {
                return write('g', label);
            }
        },
        gc = function (label) {
            if (dtProfilerUseConsole) {
                let args = [];
                args.push(label);
                newGroup(args, true);
                return _cjProfilerP;
            } else {
                return write('gc', label);
            }
        },
        ge = function () {
            if (dtProfilerUseConsole) {
                newGroupEnd();
                return _cjProfilerP;
            } else {
                return write('ge');
            }
        },
        i = function () {
            let args = Array.from(arguments);
            if (dtProfilerUseConsole) {
                newLog(args, 'info');
                return _cjProfilerP;
            } else {
                return write('i', args, null, true);
            }
        },
        time = function (label) {
            if (dtProfilerUseConsole) {
                if (_.isUndefined(label) || _.isNull(label) ||
                    _.isEmpty(label)) {
                    label = 'default;';
                }
                _cjProfilerTimers[label] = Date.now();
            } else {
                return write('time', label);
            }
        },
        timeEnd = function (label) {
            if (dtProfilerUseConsole) {
                minNumb(6);
                if (_.isUndefined(label) || _.isNull(label) ||
                    _.isEmpty(label)) {
                    label = 'default;';
                }
                newTimeEnd(label);
                minNumb(5);
            } else {
                return write('timeEnd', label, null, true);
            }
        },
        trace = function () {
            if (dtProfilerUseConsole) {
                let args = ['Trace'];
                newLog(args, 'trace');
            } else {
                return write('trace');
            }
        },
        w = function () {
            let args = Array.from(arguments);
            if (dtProfilerUseConsole) {
                newLog(args, 'warn');
                return _cjProfilerP;
            } else {
                return write('w', args, null, true);
            }
        },
        addAdapter = function (adapter) {
            adapters = adapter;
            return _cjProfilerP;
        },
        minNumb = function (min) {
            _minNum = min;
        },
        getMinNum = () => {
            return _minNum;
        };
    return {
        minNumb: minNumb,
        getMinNum: getMinNum,
        l: l,
        log: l,
        t: t,
        table: t,
        a: a,
        assert: a,
        c: c,
        clear: c,
        cc: cc,
        count: cc,
        e: e,
        error: e,
        g: g,
        group: g,
        gc: gc,
        groupCollapsed: gc,
        ge: ge,
        groupEnd: ge,
        i: i,
        info: i,
        time: time,
        timeEnd: timeEnd,
        trace: trace,
        warn: w,
        w: w,
        addAdapter: addAdapter,
    };
}();
var ConsoleCjProfiler = function () {
};
ConsoleCjProfiler.prototype.write = function (type, msg, other, trace) {
    if (window.console) {
        switch (type) {
            case 'l':
            case 'log':
                ogLog(...msg);
                break;
            case 't':
            case 'table':
                ogTable(msg, other);
                break;
            case 'a':
            case 'assert':
                ogAssert(other, msg);
                break;
            case 'c':
            case 'clear':
                ogClear();
                break;
            case 'cc':
            case 'count':
                ogCount();
                break;
            case 'e':
            case 'error':
                ogError(msg);
                break;
            case 'g':
            case 'group':
                ogGroup(msg);
                break;
            case 'gc':
            case 'groupCollapsed':
                ogGroupCollapsed(msg);
                break;
            case 'ge':
            case 'groupend':
                ogGroupEnd();
                break;
            case 'i':
            case 'info':
                ogInfo(...msg);
                break;
            case 'time':
                ogTime(msg);
                break;
            case 'timeEnd':
                ogTimeEnd(msg);
                break;
            case 'trace':
                ogTrace();
                break;
            case 'w':
            case 'warn':
                ogWarn(msg);
                break;
        }
        if (trace === true) {
            ogLog(getStackTrace(type, _cjProfilerP.getMinNum() + 1, false));
        }
    }
};
_cjProfilerP.addAdapter(new ConsoleCjProfiler);
if (dtProfilerReplaceConsole) {
    if (dtProfilerReplacements.hasOwnProperty('log')) {
        console.log = _cjProfilerP.l;
    }

    if (dtProfilerReplacements.hasOwnProperty('table')) {
        console.table = _cjProfilerP.t;
    }

    if (dtProfilerReplacements.hasOwnProperty('assert')) {
        console.assert = _cjProfilerP.a;
    }

    if (dtProfilerReplacements.hasOwnProperty('clear')) {
        console.clear = _cjProfilerP.c;
    }

    if (dtProfilerReplacements.hasOwnProperty('count')) {
        console.count = _cjProfilerP.cc;
    }

    if (dtProfilerReplacements.hasOwnProperty('error')) {
        console.error = _cjProfilerP.e;
    }

    if (dtProfilerReplacements.hasOwnProperty('group')) {
        console.group = _cjProfilerP.g;
    }

    if (dtProfilerReplacements.hasOwnProperty('groupCollapsed')) {
        console.groupCollapsed = _cjProfilerP.gc;
    }

    if (dtProfilerReplacements.hasOwnProperty('groupEnd')) {
        console.groupEnd = _cjProfilerP.ge;
    }

    if (dtProfilerReplacements.hasOwnProperty('info')) {
        console.info = _cjProfilerP.i;
    }

    if (dtProfilerReplacements.hasOwnProperty('time')) {
        console.time = _cjProfilerP.time;
    }

    if (dtProfilerReplacements.hasOwnProperty('timeEnd')) {
        console.timeEnd = _cjProfilerP.timeEnd;
    }

    if (dtProfilerReplacements.hasOwnProperty('trace')) {
        console.trace = _cjProfilerP.trace;
    }

    if (dtProfilerReplacements.hasOwnProperty('warn')) {
        console.warn = _cjProfilerP.w;
    }
}