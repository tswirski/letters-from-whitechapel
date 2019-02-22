var Envelope = (function() {

    /**
     * Remove Javascript from source (html).
     * Return object with separated (html) and (js).
     * @param {string}
     * @returns {object} as {html:, js:}
     */
    var parseScript = function(source) {
        var javaScript = [];

        // Strip out tags
        while (source.indexOf("<script") > -1) {

            if (source.indexOf("</script") === -1) {
                throw "[ENVELOPE] Missing script closing tag </script> in evaluated script.";
                break;
            }

            var s = source.indexOf("<script");
            var s_e = source.indexOf(">", s);
            var e = source.indexOf("</script", s);
            var e_e = source.indexOf(">", e);
            javaScript.push(source.substring(s_e + 1, e));
            source = source.substring(0, s) + source.substring(e_e + 1);
        }

        return {
            html: source,
            js: javaScript
        }
    };

    /**
     * @param {array} Javascript to evaluate.
     */
    var evalScript = function(js) {
        for (var i = 0; i < js.length; i++) {
            try {
                eval(js[i]);
            }
            catch (ex) {
                console.log('Can not evaluate javascript as: ' + ex);
            }
        }
    }

    /** @var {string} Javascript to evaluate before executing next request */
    var evalBeforeNext;

    return {
        /**
         * Dispatch Envelope
         * @param {object} Data should contain:
         *   [url]: if present redirection will be made.
         *   [objects]: array of objects where each object has:
         *   *   * [target] : jQuery selector of targeted element/s
         *   *   * [content] : html
         *   *   * [action] :  (inject (default)|replace|append|prepend|after|before)
         *   *          where:
         *   *              inject - the content will be placed inside the element.
         *   *              replace - the element will be replaced with content.
         *   *              append/prepend , after/before  - see the jQuery doc.
         *   *  [evalBefore] : javacript evaluated before processing current element
         *   *  [evalAfter] : javascript evaluated after processing current element
         *  [evalBefore] : javacript evaluated before processing first element
         *  [evalAfter] : javascript evaluated when all elements ware processed
         *  [evalBeforeNext] : javascript to evaluate before starting to process the paw-response that will occure in the future.
         * */
        dispatch: function(data) {
            if (data.url) {
                window.location = data.url;
                return 302;
            }

            if (evalBeforeNext) {
                eval(evalBeforeNext);
            }

            evalBeforeNext = data.evalBeforeNext;

            if (data.evalBefore) {
                eval(data.evalBefore);
            }

            if (data.objects) {
                for (var i = 0; i < data.objects.length; i++) {
                    var element = data.objects[i];
                    var $target = $(element.target);

                    if (element.evalBefore) {
                        eval(element.evalBefore);
                    }

                    var source = parseScript(element.content);

                    switch (element.action) {
                        case 'replace':
                            $target.replaceWith(source.html);
                            break;
                        case 'raw':
                            $target.text(source.html);
                            break;
                        case 'after':
                            $target.after(source.html);
                            break;
                        case 'before':
                            $target.before(source.html);
                            break;
                        case 'prepend':
                            $target.prepend(source.html);
                            break;
                        case 'append':
                            $target.append(source.html);
                            break;
                        case 'inject':
                        default:
                            $target.html(source.html);
                            break;
                    }

                    if (source.js) {
                        evalScript(source.js);
                    }

                    if (element.evalAfter) {
                        eval(element.evalAfter);
                    }
                }
            }

            if (data.evalAfter) {
                eval(data.evalAfter);
            }

        }
    }
}
)();