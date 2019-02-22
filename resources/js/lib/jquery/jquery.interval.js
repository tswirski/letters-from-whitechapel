var Interval = (function($){
    var _queue = {};
    var _processing = {};

    var isProcessingQueue = function(queueName){
      return _processing[queueName] ? true : false;
    };

    var setProcessingQueue = function(queueName, boolean){
      _processing[queueName] = boolean;
    };

    var run = function(queueName){
        var data = _queue[queueName].shift();
        data[0]();
        setTimeout(function(){
            if(_queue[queueName].length === 0){
                setProcessingQueue(queueName, false);
            } else {
                run(queueName);
            }
        }, data[1]);
    };

    var call = function(callable, ttl, queueName){
        if(queueName === undefined){
            queueName = 'default';
        }

        if(_queue[queueName] === undefined){
            _queue[queueName] = [];
        }

        _queue[queueName].push([callable, ttl]);

        if(isProcessingQueue(queueName)){
            return true;
        }

        setProcessingQueue(queueName, true);
        run(queueName);
    };

    return {
        call: call
    }
})(jQuery);

