var Page = {
    load: function (html, postRenderCallback) {
        var $newContent = $(html).css('display', 'none');
        var $contentBox = $('#content');
        var $oldContent = $contentBox.children('.page');
        $contentBox.prepend($newContent);
        $oldContent.fadeOut(300, function(){
            $oldContent.remove();
            $newContent.fadeIn(300, function(){
                $contentBox.trigger('wsLoad');

                if($.isFunction(postRenderCallback)){
                    postRenderCallback();
                }
            });
        });
    },
    loadTemplate: function(template){
        Template.render(template,{}, function(html){
           Page.load(html);
        });
    },
    onLoad: function(callable){
        var $contentBox = $('#content');
        $contentBox.one('wsLoad', callable);
    }
};