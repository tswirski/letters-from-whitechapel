/**
 * Created by lama on 2016-03-08.
 */
var MovementManager = function($that){
    var $parent = $that.parent();
    /** Flag to controll click-event flow */
    var abortClickEvent = false;
    /** mousemove event used in click blocking sequence */
    var EVENT_MOUSEMOVE = 'mousemove.movementManager';
    /** Mouse-move distance - cancelling click event */
    var pixelTriggerDistance = 20;

    /**
     * Click event support
     */
    var setAbortClickEventTrue = function(){
      abortClickEvent = true;
    };

    var setAbortClickEventFalse = function(){
      abortClickEvent = false;
    };

    var isAbortedClickEvent = function(){
        return abortClickEvent;
    };

    /**
     * Distance required to cancel click event
     */
    var getPixelTriggerDistance = function(){
      return pixelTriggerDistance;
    };

    var setPixelTriggerDistance = function(pixels){
      pixelTriggerDistance = pixels;
    };

    /**
     * Coordinates and sizes
     */

    var getOffsetLeft = function(){
        return $that.offset().left;
    }

    var getOffsetTop = function(){
        return $that.offset().top;
    }

    var getWidth = function(){
        return $that[0].getBoundingClientRect().width;
    };

    var getHeight = function(){
        return $that[0].getBoundingClientRect().height;
    };

    /**
     * UPDATE CSS SECTION
     */
    var getMatrixPrefixData = function(){
        return /^matrix\((.*)\)$/.exec($that.css('transform'))[1].split(',').splice(0,4).join(',');
    };

    var getTranslateX = function(){
        return parseFloat(/^matrix\((.*)\)$/.exec($that.css('transform'))[1].split(',')[4]);
    };

    var getTranslateY = function(){
        return parseFloat(/^matrix\((.*)\)$/.exec($that.css('transform'))[1].split(',')[5]);
    };

    var WEB_CSS_PREFIXES = ['', '-ms-', '-o-', '-moz-', '-webkit-'];

    var _getTransformMatrix = function(translateX, translateY){
        var transform2d = "matrix(" + [getMatrixPrefixData(), translateX, translateY].join(',') + ")";
        return transform2d;
    };

    var updateTranslateCss = function(translateX, translateY){
        var transform2d = _getTransformMatrix(translateX, translateY);
        _.each(WEB_CSS_PREFIXES, function(prefix){
            $that.css(prefix + 'transform', transform2d);
        });
    };

    var _deltaX = function(deltaX){
        var maxTranslateX = $parent.width() - getWidth();
        maxTranslateX = maxTranslateX > 0 ? maxTranslateX / 2 : 0;

        /** left */
        if(getOffsetLeft() + deltaX > maxTranslateX && deltaX > 0){
           return (getOffsetLeft() > maxTranslateX) ? 0 : Math.round(maxTranslateX - getOffsetLeft());
        }

        /** right */
        var minTranslateX =  $parent.width() - getWidth() - maxTranslateX;
        if(getOffsetLeft() + deltaX < minTranslateX && deltaX < 0){
            return (getOffsetLeft() < minTranslateX) ? 0 : Math.round(minTranslateX - getOffsetLeft());
        }

        return deltaX;
    };

    var _deltaY = function(deltaY){
        var maxTranslateY = $parent.height() - getHeight();
        maxTranslateY = maxTranslateY > 0 ? maxTranslateY / 2 : 0;

        /** top */
        if(getOffsetTop() + deltaY > maxTranslateY && deltaY > 0){
            return (getOffsetTop() > maxTranslateY) ? 0 : Math.round(maxTranslateY - getOffsetTop());
        }

        /** bottom */
        var minTranslateY = $parent.height() - getHeight() - maxTranslateY;
        if(getOffsetTop() + deltaY < minTranslateY && deltaY < 0){
            return (getOffsetTop() < minTranslateY) ? 0 : Math.round(minTranslateY - getOffsetTop());
        }

        return deltaY;
    };

    /** USER API */
    /**
     * Move element by X, Y pixels in any direction.
     * @param deltaX
     * @param deltaY
     * @param onMove
     * @returns {boolean}
     */
    var moveBy = function(deltaX, deltaY, onMove){
        updateTranslateCss(getTranslateX() + _deltaX(deltaX), getTranslateY() + _deltaY(deltaY));
        executeOnMove(onMove);
        return true;
    };

    /**
     * Move element to keep point given by X, Y in the center of $parent element.
     * (if possible)
     * @param x
     * @param y
     * @param onMove
     */
    var centerTo = function(x, y, onMove){
        if($parent.width() >= getWidth()) {
            x = - ($parent.width() - getWidth()) / 2;
        } else if( x <= $parent.width() / 2 ) {
            x = 0;
        } else if( x >= getWidth() - $parent.width() / 2){
            x = getWidth() - $parent.width();
        } else {
            x -= $parent.width() / 2;
        }

        if($parent.height() >= getHeight()){
            y = - ($parent.height() - getHeight()) /2;
        } else if( y <= $parent.height() / 2 ) {
            y = 0;
        } else if ( y >= getHeight() - $parent.height() / 2) {
            y = getHeight() - $parent.height();
        } else {
            y -= $parent.height() / 2;
        }

        var targetOffsetLeft = - getOffsetLeft() - x + getTranslateX();
        var targetOffsetTop = - getOffsetTop() - y + getTranslateY();
        moveTo(targetOffsetLeft, targetOffsetTop, onMove);
    };

    /**
     * Move element to X, Y (according to left-top corner)
     * @param x
     * @param y
     * @param onMove
     * @returns {boolean}
     */
    var moveTo = function(x, y, onMove){
        updateTranslateCss(x, y);
        executeOnMove(onMove);
        return true;
    };

    /** call onMove handler */
    var executeOnMove = function(onMove){
        if(!$.isFunction(onMove)){
            return false;
        }
        onMove(getOffsetLeft(), getOffsetTop(), getWidth(), getHeight(), $parent.width(), $parent.height());
    };

    /**
     * Initialization
     */
    var init = function(){
        $that.on('mousedown', function(event_mousedown){
            $that.on(EVENT_MOUSEMOVE, function(event_mousemove){
                if( Math.abs(event_mousedown.pageX - event_mousemove.pageX) > pixelTriggerDistance
                    || Math.abs(event_mousedown.pageY - event_mousemove.pageY) > pixelTriggerDistance){
                    abortClickEvent = true;
                }
            });
        });

        $that.on('mouseup', function(){
            $that.off(EVENT_MOUSEMOVE);
        });

        $that.get(0).addEventListener('click', function(event_capturing_click){
            if(abortClickEvent === true){
                event_capturing_click.stopPropagation();
                abortClickEvent = false;
                return false;
            }
        },true);
    };

    $(document).ready(function(){
        init();
    });

    return {
        moveTo: moveTo,
        moveBy: moveBy,
        centerTo: centerTo
    };
};





