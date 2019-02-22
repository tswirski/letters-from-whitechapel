<div id="dashboardChatBox">
    <div id="dashboardChatMessagesBox"></div>
    <div id="dashboardChatInputBox">
        <?=Form::textarea('dashboardChatInput', null, [
            "spellcheck" => 'false' ,
            "id" => "dashboardChatInput" ,
            "placeholder" => __('what would you like to say?')
        ]);?>
    </div>
</div>