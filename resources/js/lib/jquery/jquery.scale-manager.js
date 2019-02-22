var ScaleManager = function($that){
	var $parent = $that.parent();
	var minScale = 0.2;
	var maxScale = 4;
	var step = 0.2;

	var setScaleRange = function(_minScale, _maxScale){
		minScale = _minScale;
		maxScale = _maxScale;
	}

	var setStep = function(_step){
		step = _step;
	}

	var getMinScale = function(){
		return minScale;
	}

	var getMaxScale = function(){
		return maxScale;
	}

	var getStep = function(){
		return step;
	}

	/**
	 * IMAGE GETTERS SECTION
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

	var getBaseHeight = function(){
		return $that[0].offsetHeight;
	}

	var getBaseWidth = function(){
		return $that[0].offsetWidth;
	}

	var getScale = function(){
		return getWidth() / getBaseWidth();
	};

	var getTransformOriginX = function(){
		return parseFloat($that.css('transform-origin').split(' ')[0]);
	}

	var getTransformOriginY = function(){
		return parseFloat($that.css('transform-origin').split(' ')[1]);
	}

	var getMouseX = function(e){
		return e.pageX - getOffsetLeft();
	};

	var getBaseMouseX = function(e){
		return getMouseX(e) / getScale();
	};

	var getMouseY = function(e){
		return e.pageY - getOffsetTop();
	};

	var getBaseMouseY = function(e){
		return getMouseY(e) / getScale();
	};

	var getTranslateX = function(){
		return parseFloat(/^matrix\((.*)\)$/.exec($that.css('transform'))[1].split(',')[4]);
	};

	var getTranslateY = function(){
		return parseFloat(/^matrix\((.*)\)$/.exec($that.css('transform'))[1].split(',')[5]);
	};

	/**
	 * UPDATE CSS SECTION
	 */

	var WEB_CSS_PREFIXES = ['', '-ms-', '-o-', '-moz-', '-webkit-'];

	var _getTransformMatrix = function(scale, translateX, translateY){
		var transform2d = "matrix(" + [scale, 0, 0, scale, translateX, translateY].join(',') + ")";
		return transform2d;
	};

	var _updateOriginCss = function(e){
		var origin = getBaseMouseX(e) + "px " + getBaseMouseY(e) + "px";

		_.each(WEB_CSS_PREFIXES, function(prefix){
			$that.css(prefix + 'transform-origin', origin);
		});
	};

	/** Fix HammerJS event */
	var fixHammerEvent = function(event){
		event.pageX = event.center.x;
		event.pageY = event.center.y;
	};

	var getFakeEvent = function(){
		var event = $.Event('click');
		event.pageX = $parent.position().left + $parent.width() / 2;
		event.pageY = $parent.position().top + $parent.height() / 2;
		return event;
	};

	var updateTransformCss = function(scale, e){
		/** Zoom to center of visible part */
		if(e === undefined) {
			e = getFakeEvent();
		}

		if(e.scale){
			fixHammerEvent(e);
		}

		_updateOriginCss(e);

		translateX =  getTranslateX() + (getBaseMouseX(e) - getTransformOriginX()) * getScale();
		translateY =  getTranslateY() + (getBaseMouseY(e) - getTransformOriginY()) * getScale();

		var transform2d = _getTransformMatrix(scale, translateX, translateY);
		_.each(WEB_CSS_PREFIXES, function(prefix){
			$that.css(prefix + 'transform', transform2d);
		});
	};

	/** call onZoom handler */
	var executeOnZoom = function(onZoom){
		if(!$.isFunction(onZoom)){
			return false;
		}
		onZoom(getOffsetLeft(), getOffsetTop(), getWidth(), getHeight(), $parent.width(), $parent.height());
	};

	var zoomIn = function(e, onZoom){
		var newScale = e.scale || (getScale() + getStep());
		newScale = newScale >= getMaxScale() ? getMaxScale() : newScale;
		updateTransformCss(newScale, e);
		executeOnZoom(onZoom);
	};

	var zoomOut = function(e, onZoom){
		var newScale = e.scale || (getScale() - getStep());
		newScale = newScale <= getMinScale() ? getMinScale() : newScale;
		updateTransformCss(newScale, e);
		executeOnZoom(onZoom);
	};

	/** INITIALIZATION */
	$(document).ready(function(){
		_.each(WEB_CSS_PREFIXES, function(prefix){
			$that.css(prefix + 'transform-origin', '0 0 0');
		});

		$that.css('transform', 'translate(0)');
	});

	return {
		getScale: getScale,
		getHeight: getHeight,
	 	getWidth: getWidth,
		getBaseHeight: getBaseHeight,
		getBaseWidth: getBaseWidth,
		// getMouseX: getMouseX,
		// getMouseY: getMouseY,
		// getOffsetLeft: getOffsetLeft,
		// getOffsetTop: getOffsetTop,
		// getTransformOriginX: getTransformOriginX,
		// getTransformOriginY: getTransformOriginY,
		// getBaseMouseX: getBaseMouseX,
		// getBaseMouseY: getBaseMouseY,
		// getTranslateX :getTranslateX,
		// getTranslateY: getTranslateY,
		// transform: updateTransformCss,

		zoomIn: zoomIn,
		zoomOut: zoomOut
	}
};
