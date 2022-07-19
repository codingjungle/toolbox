var _cjProfilerP = _cjProfilerP || null,
    _toolbox = _toolbox || {};
if(!_.isNull(_cjProfilerP)){
  _cjProfilerMin = 6;
}
;( function($, _, undefined){
  "use strict";
    _toolbox = function(){
      var adapters,
          write = function(type,message,other=null, trace=false){
            if (parseInt(ips.getSetting('cj_debug')) === 1) {
              adapters.write(type, message, other,trace);
            }

            return _toolbox;
          },
          l = function(){
            let args = Array.from(arguments);
              return write('log',args,null,true);
          },
          t = function(){
            let args = Array.from(arguments),
                msg = args[0],
                headers = args[1] ?? ['Index','Values'];
              return write('table', msg, headers,true);

          },
          a = function(assertion,msg){
              return write('a', true, msg, assertion);

          },
          c = function(){
              return write('c');
          },
          cc = function(label){
              return write('cc',  label,null,true);
          },
          e = function(msg){
              return write('e', msg,null,true);
          },
          g = function(label){
              return write('g',label);
          },
          gc = function(label){
              return write('gc',label);
          },
          ge = function(){
              return write('ge');
          },
          i = function(){
            let args = Array.from(arguments);
              return write('i',args,null,true);

              },
          time = function(label){
                return write('time',label);
          },
          timeEnd = function(label){

              return write('timeEnd', label, null,true);
          },
          trace = function(){
              return write('trace');
          },
          w = function(){
            let args = Array.from(arguments);
              return write('w',args,null,true);
          },
          addAdapter = function(adapter){
            adapters = adapter;
            return _toolbox;
          }
      return {
        l:  l,
        log:  l,
        t:t,
        table:t,
        a:a,
        assert:a,
        c:c,
        clear:c,
        cc:cc,
        count:cc,
        e:e,
        error:e,
        g:g,
        group:g,
        gc:gc,
        groupCollapsed:gc,
        ge:ge,
        groupEnd:ge,
        i:i,
        info:i,
        time:time,
        timeEnd:timeEnd,
        trace:trace,
        warn:w,
        w:w,
        addAdapter:addAdapter,
      }
    }();
    var Consoletoolbox = function() {};

    Consoletoolbox.prototype.write = function ( type, msg, other,trace ){
      if(!_.isNull(_cjProfilerP)){
        _cjProfilerP.minNumb(8);
      }
      if( window.console ){
        switch( type ){
          case 'l':
          case 'log':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.log(...msg);
            }
            else {
              console.log(...msg);
            }
            break;
          case 't':
          case 'table':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.table(msg,other);
            }
            else {
              console.table(msg, other);
            }
            break;
          case 'a':
          case 'assert':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.assert(other,msg);
            }
            else {
              console.assert(other, msg);
            }
            break;
          case 'c':
          case 'clear':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.clear();
            }
            else {
              console.clear();
            }
            break;
          case 'cc':
          case 'count':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.count(msg);
            }
            else {
              console.count(msg);
            }
            break;
          case 'e':
          case 'error':
            console.error(msg);
            break;
          case 'g':
          case 'group':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.group(msg);
            }
            else {
              console.group(msg);
            }
            break;
          case 'gc':
          case 'groupCollapsed':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.groupCollapsed(msg);
            }
            else {
              console.groupCollapsed(msg);
            }
            break;
          case 'ge':
          case 'groupend':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.groupEnd();
            }
            else {
              console.groupEnd();
            }
            break;
          case 'i':
          case 'info':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.info(...msg);
            }
            else {
              console.info(...msg);
            }
            break;
          case 'time':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.time(msg);
            }
            else {
              console.time(msg);
            }
            break;
          case 'timeEnd':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.timeEnd(msg);
            }
            else {
              console.timeEnd(msg);
            }
            break;
          case 'trace':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.trace();
            }
            else {
              console.trace();
            }
            break;
          case 'w':
          case 'warn':
            if(!_.isNull(_cjProfilerP)){
              _cjProfilerP.warn(msg);
            }
            else {
              console.warn(msg);
            }
            break;
        }
        if(trace === true && _.isNull(_cjProfilerP)){
            console.trace();
        }
      }
      if(!_.isNull(_cjProfilerP)){
        _cjProfilerP.minNumb(5);
      }
    };
    _toolbox.addAdapter( new Consoletoolbox );


}(jQuery, _));
