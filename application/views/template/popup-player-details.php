<div class="popup" data-popup="player-details" data-nickname="<%=userName%>" data-user-id="<%=userId%>">

    <% if(isMyAccount === false) {%>
    <div class="friendButtonBox" data-is-friend="<%= isFriend ? 'true' : 'false' %>">
        <div class="addToFriends">add friend</div>
        <div class="removeFromFriends">remove friend</div>
    </div>
    <% } %>

    <img src="<%=avatarUrl%>" class="avatar" data-user-id="<%=userId%>"/>
    <div class="memberName"><%=userName%></div>

    <div class="memberDetailsTextRow">Games Played
        <span class="memberDetailsTextRowValue"><%=gamesPlayed%></span>
    </div>

    <div class="memberDetailsTextRow">Games Won
        <span class="memberDetailsTextRowValue"><%=gamesWon%></span>
    </div>

    <div class="memberDetailsTextRow">Games Played As Jack
        <span class="memberDetailsTextRowValue"><%=asJack%></span>
    </div>

    <div class="memberDetailsTextRow">Games Won As Jack
        <span class="memberDetailsTextRowValue"><%=wonAsJack%></span>
    </div>

    <div class="memberDetailsTextRow">Games Played As Police Inspector
        <span class="memberDetailsTextRowValue"><%=asPoliceman%></span>
    </div>

    <div class="memberDetailsTextRow">Games Won As Police Inspector
        <span class="memberDetailsTextRowValue"><%=wonAsPoliceman%></span>
    </div>

    <div class="memberDetailsTextRow">Latest Game
        <span class="memberDetailsTextRowValue"><%=latestGameDateTime%></span>
    </div>

</div>
