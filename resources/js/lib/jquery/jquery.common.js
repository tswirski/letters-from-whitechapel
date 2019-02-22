$.fn.extend({
    toggleAttr: function(attr, val1, val2){
        return this.each(function(){
           var $that = $(this);
            if($that.attr(attr) === val1){
                $that.attr(attr, val2);
            } else {
                $that.attr(attr, val1);
            }
        });
    },

    verticalAlignMiddle: function() {
        if ($(this).length > 1) {
            $(this).each(function() {
                $(this).verticalAlignMiddle();
            });
            return false;
        }
        var $this = $(this).clone();
        var $table = $('<div style="width: 100%; height: 100%; display: table; table-layout: fixed;">');
        var $cell = $('<div style="display: table-cell; vertical-align: middle;">');
        $cell.append($this);
        $table.append($cell);
        $(this).replaceWith($table);
        return this;
    },
    isHtml: function(input) {
        var entity = $.parseHTML(input);
        /* Invalid HTML - unparseable */
        if (entity.length === 0) {
            return false;
        }

        /* HTML with multiple nodes */
        if (entity.length > 1) {
            return true;
        }

        /* Tell if root HTML node or plain text */
        return (entity[0].outerHTML !== undefined);
    },
    getFormData: function() {
        if (!$(this).is('form')) {
            return false;
        }
        var $form = $(this);
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};

        $.map(unindexed_array, function (n, i) {
            indexed_array[n['name']] = n['value'];
        });

        return indexed_array;
    }
});


$.fn.extend({
    filterAttr: function(attrArrArr){
        var attrSelector = "";
        $.each(attrArrArr, function(index, attrArr) {
            attrSelector += "[" + attrArr[0] + '="' + attrArr[1] + '"]';
        });
        return $(this).filter(attrSelector);
    }
});


$.isJQueryObject = function($object){
    if (typeof $object !== 'object'){
        return false;
    }
    return ($object instanceof jQuery);
}

