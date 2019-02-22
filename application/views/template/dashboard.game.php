<div class="game" data-game-id="<%=gameId%>" data-using-password="<%=usingPassword%>">
    <span class="gameHost"><img src="<%=avatarUrl%>"/></span>
    <span class="gameId"><%=gameId%></span>
    <span class="gamePlayerCount"><%=playerCount%></span>
    <span class="gameHint"></span>
    <% if(randomJackHideout === true){ %> <span class="gameDice"></span> <% } %>
</div>