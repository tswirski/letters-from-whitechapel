/** AUTOMATYCZNA OBSŁUGA FORMULARZY AJAX PAW **/

/**
 * <form method="" [action=""]>
 * Parametry wymagane.  Wynik przekazywany do htmlOverAjax();
 */
$(document).on('submit', 'form', function (event) {
    var that = $(this);
    if (that.hasClass('ajax')) {
        event.preventDefault();

        var method = that.attr('method');
        if (!method) {
            method = 'POST';
        }

        var url = that.attr('action');
        if (!url) {
            console.log('"AJAX-FORM" Atrybut "action" jest wymagany');
            return;
        }

        Ajax.paw({
            url: url,
            method: method,
            data: that.serialize(),
        });
    }

});



Object.defineProperty(Object.prototype, "toQueryString", {
    value: function () {
        var out = new Array();

        for (key in this) {
            if (typeof this[key] !== 'function') {
                out.push(key + '=' + encodeURIComponent(this[key]));
            }
        }

        if (out.length) {
            return '?' + out.join('&');
        } else {
            return '';
        }
    },
    enumerable: false
});


/**
 * smoothScroll wymaga biblioteki jquery.mousewheel i służy do płynnego scrollowania zawartości ramki.
 * Ponadto jeśli ramka jest mniejsze niż okno przeglądarki to przewijanie ramki nie powoduje przewijania całej strony.
 * (Uzywane np. przy okienkach modalnych DIKI */
jQuery.fn.extend({
    smoothScroll: function () {
        var that = $(this);
        that.on('mousewheel', function (e) {
            var currentScrollPosition = $(this).scrollTop();
            var newPosition = parseInt(currentScrollPosition + e.deltaY * e.deltaFactor * -0.2);
            var steps = Math.abs(newPosition - currentScrollPosition);
            var i = 0;

            var scrollInterval = setInterval(function () {
                if (i === (steps - 1)) {
                    clearInterval(scrollInterval);
                    return false;
                }
                that.scrollTop(that.scrollTop() - e.deltaY);
                i++;
            }, 2);

            setTimeout(function () {
                clearInterval(scrollInterval);
            }, 1000);

            e.preventDefault();
            if ($(window).height() > that.height()) {
                e.stopPropagation();
            }
        });
    }
});


/*global window, document*/

/*
 * Get the average color of an image by painting it to a canvas element
 * and sampling (some of) the pixel color values.
 *
 * A jQuery-wrapped, easier to re-use version of this StackOverflow answer:
 * http://stackoverflow.com/questions/2541481/get-average-color-of-image-via-javascript
 */
(function ($) {

    $.fn.averageColor = function () {
        var blockSize = 5, // only sample every 5 pixels
                defaultRGB = {r: 0, g: 0, b: 0}, // for non-supporting environments
        canvas = document.createElement('canvas'),
                context = canvas.getContext && canvas.getContext('2d'),
                data, width, height,
                i = -4,
                length,
                rgb = {r: 0, g: 0, b: 0},
        count = 0;

        if (!context) {
            console.log("Canvas not supported");
            return defaultRGB;
        }

        height = canvas.height = $(this).naturalHeight();
        width = canvas.width = $(this).naturalWidth();

        context.drawImage(this[0], 0, 0);

        try {
            data = context.getImageData(0, 0, width, height);
        } catch (e) {
            // security error, the image was served from a different domain
            return defaultRGB;
        }

        length = data.data.length;

        while ((i += blockSize * 4) < length) {
            count += 1;
            rgb.r += data.data[i];
            rgb.g += data.data[i + 1];
            rgb.b += data.data[i + 2];
        }

        // ~~ used to floor values
        rgb.r = ~~(rgb.r / count);
        rgb.g = ~~(rgb.g / count);
        rgb.b = ~~(rgb.b / count);

        return rgb;
    };

    $.fn.averageColorAsString = function () {
        var rgb = this.averageColor();
        return 'rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')';
    };

    $.fn.averageColorAsHex = function () {
        var rgb = this.averageColor();
        var hex = rgb.b | (rgb.g << 8) | (rgb.r << 16);
        return '#' + (0x1000000 + hex).toString(16).slice(1);
    };

}(window.jQuery || window.Zepto));


/** Obiekt pozwalający na wykonywanie funkcji "callbacków" gdy dokument jest załadowany.
 * (+obsługa kolejki)
 * @type Function|_L4.Anonym$0
 */
window.ready = (function () {
    var _onReady = [];
    var _isReady = false;

    /** Załadowanie okna powoduje automatyczne wykonanie callbacków */
    $(window).load(function () {
        _isReady = true;
        for (var i = 0; i < _onReady.length; i++) {
            _onReady[i]();
        }
        _onReady = null;
    });

    return {
        addCallback: function (onReadyCallback) {
            if (!$.isFunction(onReadyCallback)) {
                return false;
            }

            if (_isReady) {
                onReadyCallback();
            } else {
                _onReady.push(onReadyCallback);
            }
            return true;
        }
    }
})();

/**
 * Skrót pozwalający na zakolejkowanie elementów które wykonają się wraz oraz po odpaleniu eventu window.load
 * @todo dodać sprawdzenie czy obiektem wywoływanym jest [window] jeśli nie to zgłosić (console log) i zwrócić false.
 **/
$.fn.extend({
    loaded: function (callback) {
        window.ready.addCallback(callback);
    }
});


/**
 * Dodaje event-handler na początku kolejki event-handlerów danego obiektu jQuery
 * @param {object} object jQuery reprezentujący POJEDYNCZY element w drzewie DOM
 * @param {string} event (np. 'click')
 * @param {callabke} callback
 * @returns {undefined}
 */
$._prependEventToObject = function (object, event, callback) {
    var eventQueue = eval('$._data(object.get(0),"events").' + event);
    var handlerCopy = new Array();

    if (eventQueue !== undefined) {
        for (var i = 0; i < eventQueue.length; i++) {
            handlerCopy.push(eventQueue[i].handler);
        }
        object.off(event);
    }
    object.on(event, callback);
    for (var i = 0; i < handlerCopy.length; i++) {
        object.on(event, handlerCopy[i]);
    }
};

/**
 * Prependuje event po selektorze jQuery
 * @param {string} event
 * @param {callable} callback
 * @returns {undefined}
 */

$.fn.extend({leadEvent: function (event, callback) {
        for (var i = 0; i < $(this).length; i++) {
            $._prependEventToObject($(this).eq(i), event, callback);
        }
    }
});


/** jQuery naturalWidth */
(function ($) {
    function img(url) {
        var i = new Image;
        i.src = url;
        return i;
    }

    if ('naturalWidth' in (new Image)) {
        $.fn.naturalWidth = function () {
            return this[0].naturalWidth;
        };
        $.fn.naturalHeight = function () {
            return this[0].naturalHeight;
        };
        return;
    }
    $.fn.naturalWidth = function () {
        return img(this.src).width;
    };
    $.fn.naturalHeight = function () {
        return img(this.src).height;
    };
})(jQuery);

//Randomizacja elementów potomnych wewnątrz kontenera jQuery
$.fn.shuffle = function (selector) {
    $(this).each(function () {
        var $children = selector ? $(this).children(selector) : $(this).children();
        $children.sort(function () {
            return Math.round(Math.random()) - 0.5;
        }).detach().appendTo(this);
    });
    return this;
};

/* uniwersalny link js */
$(document).on('click', '.js-link', function (e) {
    if (e.which === 1
            && $(this).attr('href')
            && !e.ctrlKey) {
        window.location.href = $(this).attr('href');
    }
});

/** Blokowanie Backspacea gdy nie jesteśmy wewnątrz aktywnego INPUTa ani TEXTAREA */
$(document).on('keydown', function (e) {
    if (e.which === 8) {
        return;
        if ($(e.target).is('input, textarea')) {
            return true;
        }
        return false;
    }
});