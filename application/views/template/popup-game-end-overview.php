<div class="popup" data-popup="game-end-overview">
    <h1><%=titleText%></h1>
    <h2><%=titleText%></h2>
    <% _.each(jackMovementTrack, function(dayMovementTrack, day){ %>
        <div class="dayOverview" data-day="<%= day %>">
            <div class="dayOverviewLeftRail">
                <div class="dayOverviewTitle"><?=__('Day')?> <%= day %></div>
                <div class="dayOverviewMurderScene"><%=dayMovementTrack[0]['hideoutId']%></div>
            </div>

            <div class="dayOverviewRightRail">

                <div class="dayOverviewMovementOrdination">
                    <?php for($i=1; $i<=20; $i++): ?>
                        <span class="dayOverviewMovementOrdinal<% if(<?=$i?> > jackMovementTrackLimit[day]){ print(' disabled'); } %>"><?=$i?></span>
                    <?php endfor; ?>
                </div>

                <div class="dayOverviewMovementHideoutIDs" data-day="<%= day %>">
                    <% _.each(dayMovementTrack, function(move, moveId){%>
                        <% if(moveId == 0){ return; } %>

                        <% if( ! _.isArray(move['hideoutId'])){
                            move['hideoutId'] = [move['hideoutId']];
                        } %>

                        <%_.each(move['hideoutId'], function(hideoutId){ %>
                        <span class="dayOverviewMovementHideoutId"><%= hideoutId %></span>
                        <% }); %>
                    <% }); %>
                </div>

                <div class="dayOverviewMovementMethods">
                    <% _.each(dayMovementTrack, function(move, moveId){%>
                        <% if(moveId == 0){ return; } %>
                        <span class="dayOverviewMovementMethod" data-method="<%= move['moveType'] %>"></span>
                    <% }); %>
                </div>

            </div>
        </div>
    <% }); %>
</div>

