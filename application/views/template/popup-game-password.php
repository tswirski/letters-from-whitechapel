<div class="popup" data-popup="game-password" data-game-id="<%=gameId%>">
    <h2>To join game ...</h2>
    <form>
        <?=Form::password('password', null, [
            "placeholder" => "Type password here",
            "autocomplete" => "off"
        ]);?>
    </form>
</div>