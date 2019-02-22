<div class="player<% if(isMyAccount === true){ %> me<% } %><% if(isMyFriend === true){ %> friend<% } %>" data-nickname="<%=nickname%>" data-user-id="<%=userId%>">
    <div class="playerAvatar avatar" data-user-id="<%=userId%>" style="background-image: url(<%=avatarUrl%>);"></div>
    <div class="playerNickname"><%=nickname%></div>
</div>