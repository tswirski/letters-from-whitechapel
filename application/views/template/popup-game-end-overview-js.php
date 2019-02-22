Template.render('popup-game-end-overview', {"jackMovementTrack":{"1":{"0":{"hideoutId":66},"1":{"moveType":"carriage","hideoutId":["84",100]},"3":{"moveType":"walk","hideoutId":83},"4":{"moveType":"carriage","hideoutId":["63",64]},"6":{"moveType":"carriage","hideoutId":["66",67]},"8":{"moveType":"alley","hideoutId":51},"9":{"moveType":"walk","hideoutId":66}},"2":{"0":{"hideoutId":149},"1":{"moveType":"carriage","hideoutId":["139",118]},"3":{"moveType":"carriage","hideoutId":["98",62]},"5":{"moveType":"walk","hideoutId":64},"6":{"moveType":"walk","hideoutId":66}},"3":[{"hideoutId":65},{"hideoutId":84},{"moveType":"walk","hideoutId":66}]}, "titleText": "VICTORY", "jackMovementTrackLimit":{"1" : 15, "2" : 15, "3": 15, "4" : 15}}, $.popupManager.alert);

<div class="popup" data-popup="game-end-overview">
    <h1><%=titleText%></h1>
    <h2><%=titleText%></h2>
    <% _.each(jackMovementTrack, function(dayMovementTrack, day){ %>
    <div class="dayOverview" data-day="<%= day %>">
        <div class="dayOverviewLeftRail">
            <div class="dayOverviewTitle"><?=__('Day')?> <%= day %></div>
            <div class="dayOverviewMurderScene"><%=dayMovementTrack[0]%></div>
        </div>

        <div class="dayOverviewRightRail">

            <div class="dayOverviewMovementOrdination">
                <?php for($i=1; $i<=20; $i++): ?>
                    <span class="dayOverviewMovementOrdinal<% if(<?=$i?> > jackMovementTrackLimit[day]){ print(' disabled'); } %>"><?=$i?></span>
                <?php endfor; ?>
            </div>

            <div class="dayOverviewMovementHideoutIDs">
                <% for(i = 1; i <= dayMovementTrack.length; i++){ %>
                <% _.each(dayMovementTrack[i], funtion(hideoutId){ %>
                <span class="dayOverviewMovementHideoutId"><%=hideoutId%></span>
                <% }); %>
                <% } %>
            </div>

            <div class="dayOverviewMovementMethods">
                <% for(i = 1; i <= dayMovementTrack.length; i++){  %>
                <span class="dayOverviewMovementMethod" data-method="<%= method %>"></span>
                <% } %>
            </div>

        </div>
    </div>
    <% }); %>
</div>

