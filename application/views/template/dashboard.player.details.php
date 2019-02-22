<div class="dashboardPlayerDetails">
    <div class="playerDetailsMoreButton">more</div>
    <div class="playerDetailsTextRow">Games Played
        <span class="playerDetailsTextRowValue"><%=gamesPlayed%></span>
    </div>
    <div class="playerDetailsTextRow">Games Won
        <span class="playerDetailsTextRowValue"><%=gamesWon%></span>
    </div>

    <% if(isMyAccount === false) {%>
    <div class="playerDetailsFriendAddButton">add friend</div>
    <div class="playerDetailsFriendRemoveButton">remove friend</div>
    <% } %>
</div>