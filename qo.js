define([
    "dojo","dojo/_base/declare", "dojo/dom", "dojo/html",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, dom, html) {
    return declare("bgagame.qo", ebg.core.gamegui, {
        constructor: function(){
            console.log('qo constructor');

            this.oppPlayer = "";
            this.player = "";
            this.oppColor = "";
            this.boardData = [];
            this.emptyPositions = [];
            this.playEvtFlag = 1;
            this.beforeClickPos = 0;
            this.stepNum = 0;
            this.firstRender = true;
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting creating player boards => ", gamedatas );

            this.boardData = gamedatas.board;

            this.isSpectator = this.player_id === null || !(this.player_id in gamedatas.players);

            const playerOneStone = document.getElementById('lodestone-1');
            const playerOneScore = document.getElementById('score-1');
            const playerTwoStone = document.getElementById('lodestone-2');
            const playerTwoScore = document.getElementById('score-2');
            
            var activePlayer = gamedatas.gamestate.active_player;

            for (const player in gamedatas.players) {
                if (player != activePlayer) {
                    this.oppPlayer = player
                }
            }

            var thisPlayer = gamedatas.playerorder[0];
            this.player = thisPlayer;

            var activePlayerId = (gamedatas.players[activePlayer]['color'] === '000000') ? 'active-black' : 'active-white' ;

            var playerStones = [];
            var playerColors = [];

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];

                if (player['color'] !== '000000') {
                    playerOneStone.insertAdjacentText('afterbegin', player['stone']);
                    playerTwoScore.insertAdjacentText('afterbegin', player['captured']);
                    this.oppColor = "ffffff";
                } else {
                    playerTwoStone.insertAdjacentText('afterbegin', player['stone']);
                    playerOneScore.insertAdjacentText('afterbegin', player['captured']);
                    this.oppColor = "000000";
                }

                playerStones[player_id] = gamedatas.players[player_id].stone;
                playerColors[player_id] = gamedatas.players[player_id].color;
                // TODO: Setting up players boards if needed
            }

            // TODO: Set up your game interface here, according to "gamedatas"
            const board = document.getElementById('board');
            
            const hor_scale = 62.4;
            const ver_scale = 62.4;
            for (let x=1; x<=9; x++) {
                for (let y=1; y<=9; y++) {
                    const left = Math.round((x - 1) * hor_scale);
                    const top = Math.round((y - 1) * ver_scale);
                    // we use afterbegin to make sure quares are placed before discs
                    board.insertAdjacentHTML(`afterbegin`, `<div id="square_${x}_${y}" class="square" style="left: ${left}px; top: ${top}px;"></div>`);
                }
            }

            for( var i in gamedatas.board )
            {
                var square = gamedatas.board[i];
                

                if( square.player !== null )
                {
                    this.addDiscOnBoard( square.x, square.y, square.player );
                }
            }

            if (gamedatas.record.length !== 0) {
                for (let i = 0; i < gamedatas.record.length; i++) {
                    const player_id = gamedatas.record[i]['player']
                    var player_color = gamedatas.players[player_id]['color'];
                    
                    var className = "last-move-tile-black";
                    var idName = "move-record-black";
                    var idActiveName = "active-black";

                    if (player_color === "ffffff") {
                        className = "last-move-tile-white";
                        idName = "move-record-white";
                        idActiveName = "active-white";
                    }
        
                    document.getElementById(idName).insertAdjacentHTML(
                        `afterbegin`,
                        `<div class="${className}">${gamedatas.record[i]['position']}</div>`
                    );
                }
            }

            if (document.getElementById("active-player")) document.getElementById("active-player").remove();
            document.getElementById(activePlayerId).insertAdjacentHTML(
                `beforeend`,
                `<div id="active-player"></div>`
            );

            this.checkBalance(playerStones, playerColors);

            if (!this.isSpectator) {
                document.querySelectorAll('.square').forEach(square => square.addEventListener('click', e => this.onPlayDisc(e)));
                document.querySelectorAll('.pay-btn').forEach(btn => btn.addEventListener('click', e => this.onClickPayBtn(e)));
            }

            this.addTooltipToClass( 'pay-btn', '', _('Click to move your lodestone') );
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
        

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: ', stateName );
            
            switch( stateName )
            {
                case 'playerTurn':
                    if (!this.isSpectator) {
                        this.updateEmptyPositions( args.args.emptyPositions );
                        this.emptyPositions = args.args.emptyPositions;
                    };
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: ', stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() && !this.isSpectator )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        addDiscOnBoard: function( x, y, player )
        {
            var color = this.gamedatas.players[ player ].color;
            
            document.getElementById('discs').insertAdjacentHTML('beforeend', `<div class="disc" data-color="${color}" id="disc_${x}${y}"></div>`);
            
            this.placeOnObject( `disc_${x}${y}`, 'overall_player_board_'+player );
            this.slideToObject( `disc_${x}${y}`, 'square_'+x+'_'+y ).play();

            if (!this.isSpectator) {
                document.querySelectorAll('.disc').forEach(square => square.addEventListener('click', e => this.onPlayDisc(e)));
            }
        },

        moveDiscOnBoard: function(beforeX, beforeY, x, y, player_id) {
            var disc = document.getElementById(`disc_${beforeX}${beforeY}`);
            var target = document.getElementById('discs');
            var targetSquare = document.getElementById(`square_${x}_${y}`);
            var targetX = parseInt(targetSquare.style.left);
            var targetY = parseInt(targetSquare.style.top);
        
            // Animate the movement of the disc
            dojo.animateProperty({
                node: disc,
                duration: 500, // Animation duration in milliseconds
                properties: {
                    left: { start: parseInt(disc.style.left), end: targetX },
                    top: { start: parseInt(disc.style.top), end: targetY }
                },
                onEnd: function() {
                    // After animation, move the disc to the new position
                    dojo.style(disc, "left", targetX + "px");
                    dojo.style(disc, "top", targetY + "px");
        
                    // Ensure the disc is positioned correctly in the DOM hierarchy
                    dojo.place(disc, target, "last");
        
                    // Update the ID of the disc to reflect its new position
                    disc.id = `disc_${x}${y}`;
                }
            }).play();

            this.stepNum = 0;
        },

        updateEmptyPositions: function( emptyPositions )
        {
            // Remove current possible moves
            document.querySelectorAll('.emptyPositions').forEach(div => div.classList.remove('emptyPositions'));

            for( var x in emptyPositions )
            {
                for( var y in emptyPositions[ x ] )
                {
                    // x,y is a possible move
                    document.getElementById(`square_${x}_${y}`).classList.add('emptyPositions');
                }            
            }
                        
            this.addTooltipToClass( 'emptyPositions', '', _('Place a lodestone here') );
            this.addTooltipToClass( 'disc', '', _('Click to move this lodestone') );
        },

        getPossibleMoves: function( selectedPosition )
        {
            console.log("get possible moves")
            var possibleMoves = [];
            var emptyPositions = this.emptyPositions;

            console.log("#### emptyPositions => ", emptyPositions)

            var x = parseInt(selectedPosition[0]);
            var y = parseInt(selectedPosition[1]);
            var checkPositions = [[x, y]];
            var step = this.stepNum;
            var loop = 1;

            var directions = [
                [-1,-1], // top-left
                [0, -1],  // top
                [1, -1],  // top-right
                [-1, 0],  // left
                [1, 0],   // right
                [-1, 1],  // bottom-left
                [0, 1],   // bottom
                [1, 1]    // bottom-right
            ];

            while (loop <= step) {
                for (const position of checkPositions) {
                    for (const direction of directions) {
                        var check_x = (position[0] + direction[0] > 9) ? 9 : (position[0] + direction[0] < 1) ? 1 : position[0] + direction[0] ;
                        var check_y = (position[1] + direction[1] > 9) ? 9 : (position[1] + direction[1] < 1) ? 1 : position[1] + direction[1] ;

                        var checked = possibleMoves.find(item => item[0] == check_x && item[1] == check_y);

                        if (emptyPositions[check_x][check_y] && checked === undefined) {
                            possibleMoves.push([check_x, check_y]);
                        }
                    }
                }

                checkPositions = [];

                for (const move of possibleMoves) {
                    checkPositions.push(move);
                }

                loop++;
            }

            document.querySelectorAll('.possibleMoves').forEach(div => div.classList.remove('possibleMoves'));
            for (const possibleMove of possibleMoves) {
                document.getElementById(`square_${possibleMove[0]}_${possibleMove[1]}`).classList.add('possibleMoves');
            }



        },

        onPlayDisc: function( evt )
        {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // Get the cliqued square x and y
            // Note: square id format is "square_X_Y"
            var coords = evt.target.id.split('_');
            var x,y;
            var afterPos;
            
            if (coords[0] === "square" && this.playEvtFlag === 1) {
                x = coords[1];
                y = coords[2];

                if(!document.getElementById(`square_${x}_${y}`).classList.contains('emptyPositions')) {
                    // This is not a possible move => the click does nothing
                    return ;
                }
                console.log("first")

                if( this.checkAction( 'playDisc' ) )    // Check that this action is possible at this moment
                {            
                    this.ajaxcall( "/qo/qo/playDisc.html", {
                        x:x,
                        y:y,
                    }, this, function( result ) {} );
                }
            } else if (coords[0] !== "square" && this.stepNum !== 0) {
                console.log("click")
                var playerId = this.gamedatas.playerorder[0];
                var playerColor = this.gamedatas.players[ playerId ].color;
                var stoneColor = evt.currentTarget.getAttribute('data-color');

                if (playerColor == stoneColor) {
                    this.beforeClickPos = coords[1];
                    this.playEvtFlag = 2;

                    // Get all elements with the class name 'disc'
                    var elements = document.getElementsByClassName('disc');

                    // Loop through each element and set the opacity to 1
                    for (let i = 0; i < elements.length; i++) {
                        elements[i].style.opacity = 1;
                    }

                    document.getElementById(`disc_${this.beforeClickPos}`).style.opacity = 0.7;

                    this.getPossibleMoves(this.beforeClickPos);
                } 
            } else if (coords[0] === "square" && this.stepNum !== 0) {
                afterPos = "" + coords[1] + coords[2] + this.stepNum;
                var beforePos = this.beforeClickPos;

                if(!document.getElementById(`square_${coords[1]}_${coords[2]}`).classList.contains('possibleMoves')) {
                    // This is not a possible move => the click does nothing
                    return ;
                }

                if( this.checkAction( 'playDisc' ))    // Check that this action is possible at this moment
                {            
                    this.ajaxcall( "/qo/qo/playDisc.html", {
                            x:beforePos,
                            y:afterPos,
                    }, this, function( result ) { console.log("result => ", result)} );
                } else {
                    console.log("Impossible Move")
                    this.stepNum = 0;
                }

                var elements = document.getElementsByClassName('disc');

                // Loop through each element and set the opacity to 1
                for (let i = 0; i < elements.length; i++) {
                    elements[i].style.opacity = 1;
                }

                this.playEvtFlag = 1;
            }

            
        },

        onClickPayBtn: function ( evt ) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            var btnName = evt.target.id.split('-');
            var btnColor = (btnName[2]==="black") ? "000000" : "ffffff";
            var oppPlayer = this.oppPlayer;
            var thisPlayer = this.player;

            console.log("#### active player => ", oppPlayer);
            console.log("#### this player => ", thisPlayer)
            console.log("#### btn color => ", btnColor)
            console.log("#### opp color => ", this.oppColor)

            if ((btnColor == this.oppColor) && (oppPlayer != thisPlayer)) {
                var msgContainer = document.getElementById("dark-shroud");
                var msgTitle = document.getElementById("win-lose-draw");
                var msgBox = document.getElementById("pay-message");
                msgContainer.style.visibility = "visible";
                msgContainer.style.opacity = 1;
                msgTitle.style.opacity = 1;
                msgBox.style.opacity = 1;

                msgTitle.insertAdjacentHTML('afterbegin', 'Want to move your lodestone?<br>You must pay double numbers of lodestone.');

                for (let i = 1; i <= 3; i++) {
                    const btnElement = `<button class="msg-button" id="msg-btn-${i}">Move ${i} - ${i*2} lodestones</button>`;
                    msgBox.insertAdjacentHTML('beforeend', btnElement);
                }

                msgBox.insertAdjacentHTML('beforeend', `<button class="msg-cancel-button" id="cancel-btn">Cancel</button>`);

                document.querySelectorAll('.msg-button').forEach(btn => btn.addEventListener('click', e => this.onSetMoveNum(e)));
                document.querySelectorAll('.msg-cancel-button').forEach(btn => btn.addEventListener('click', e => this.onCancelMove(e)));
            }
        },

        onSetMoveNum: function ( evt ) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            var idNum = evt.target.id.split('-');
            this.stepNum = parseInt(idNum[2]);

            var msgContainer = dojo.byId("dark-shroud");
            var msgTitle = dojo.byId("win-lose-draw");
            var msgBox = dojo.byId("pay-message");

            dojo.style(msgContainer, {
                visibility: "hidden",
                opacity: 0
            });

            dojo.style(msgTitle, {
                opacity: 0
            });

            dojo.style(msgBox, {
                opacity: 0
            });

            dojo.empty(msgTitle);
            dojo.empty(msgBox);
        },

        onCancelMove: function ( evt ) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            var msgContainer = dojo.byId("dark-shroud");
            var msgTitle = dojo.byId("win-lose-draw");
            var msgBox = dojo.byId("pay-message");

            dojo.style(msgContainer, {
                visibility: "hidden",
                opacity: 0
            });

            dojo.style(msgTitle, {
                opacity: 0
            });

            dojo.style(msgBox, {
                opacity: 0
            });

            dojo.empty(msgTitle);
            dojo.empty(msgBox);

            this.stepNum = 0;
        },
        
        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        checkBalance: function ( stones, colors ) {
            var board = this.boardData;
            var remainStoneOnBoard = [];
            var finalScore = [];

            for (const position of board) {
                if (!remainStoneOnBoard[position.player]) remainStoneOnBoard[position.player] = 1;
                else remainStoneOnBoard[position.player]++;
            }

            for (const player in stones) {
                if (remainStoneOnBoard[player]) finalScore.push([player, remainStoneOnBoard[player] + parseInt(stones[player])]);
                else finalScore.push([player, parseInt(stones[player])]);
            }

            var difference = Math.abs(finalScore[0][1] - finalScore[1][1]);

            if (this.firstRender) {
                document.getElementById("balance-slider").insertAdjacentHTML('afterbegin', '<div id="slider"></div>');
                document.getElementById('slider').style.top = "110px";
                document.getElementById('slider').style.left = "15px";
                this.firstRender = false;
            }

            if (difference >= 2) {
                var stepCount = (difference > 8) ? 4 : parseInt(difference / 2);
                var distance = 0;

                if (difference !== 0) {
                    if (finalScore[0][1] > finalScore[1][1]) {
                        distance = (colors[finalScore[0][0]]==="000000") ? stepCount*35 : stepCount*35*(-1);
                    } else {
                        distance = (colors[finalScore[1][0]]==="ffffff") ? stepCount*35 : stepCount*35;
                    }
                }

                var slider = document.getElementById('slider');
                var startPoint = parseInt(slider.style.top);
                var targetPoint = 110 + distance;
    
                // Animate the movement of the disc
                dojo.animateProperty({
                    node: slider,
                    duration: 500, // Animation duration in milliseconds
                    properties: {
                        left: { start: 15, end: 15 },
                        top: { start: startPoint, end: targetPoint }
                    },
                    onEnd: function() {
                        // After animation, move the disc to the new position
                        dojo.style(slider, "top", targetPoint + "px");
                    }
                }).play();
            }
           
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/qo/qo/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your qo.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            const notifs = [
                ['moveDisc', 500],
                ['playDisc', 500],
                ['turnOverDiscs', 1500],
                ['newScores', 500],
            ];
    
            notifs.forEach((notif) => {
                dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
                this.notifqueue.setSynchronous(notif[0], notif[1]);
            });
        },
        
        notif_playDisc: function( notif )
        {
            console.log('notif => ', notif)
            // Remove current possible moves (makes the board more clear)
            document.querySelectorAll('.emptyPositions').forEach(div => div.classList.remove('emptyPositions'));
        
            this.addDiscOnBoard( notif.args.x, notif.args.y, notif.args.player_id );
            
            this.boardData = this.boardData.concat({ x: `${notif.args.x}`, y: `${notif.args.y}`, player: `${notif.args.player_id}` });

            var color = notif.args.colors[ notif.args.player_id ];
            this.oppColor = (color == "000000") ? "ffffff" : "000000";
            this.oppPlayer = notif.args.player_id;

            var position_y_arr = ["A", "B", "C", "D", "E", "F", "G", "H", "I"]

            var move = position_y_arr[notif.args.y - 1] + notif.args.x;
            var className = "last-move-tile-black";
            var idName = "move-record-black";
            var idActiveName = "active-white";
            if (color === "ffffff") {
                className = "last-move-tile-white";
                idName = "move-record-white";
                idActiveName = "active-black";
            };

            document.getElementById(idName).insertAdjacentHTML(
                `afterbegin`,
                `<div class="${className}">${move}</div>`
            );

            if(document.getElementById("active-player")) document.getElementById("active-player").remove();

            document.getElementById(idActiveName).insertAdjacentHTML(
                `beforeend`,
                `<div id="active-player"></div>`
            );
            
            document.querySelectorAll('.possibleMoves').forEach(div => div.classList.remove('possibleMoves'));
        },

        notif_moveDisc: function( notif )
        {
            // Remove current possible moves (makes the board more clear)
            document.querySelectorAll('.emptyPositions').forEach(div => div.classList.remove('emptyPositions'));

            this.moveDiscOnBoard( notif.args.beforeX, notif.args.beforeY, notif.args.x, notif.args.y, notif.args.player_id );
            
            this.boardData = this.boardData.filter( item => item.x != notif.args.beforeX && item.y != notif.args.beforeY );
            this.boardData = this.boardData.concat({ x: `${notif.args.x}`, y: `${notif.args.y}`, player: `${notif.args.player_id}` });

            var color = notif.args.colors[ notif.args.player_id ];
            this.oppColor = (color == "000000") ? "ffffff" : "000000";
            this.oppPlayer = notif.args.player_id;

            var position_y_arr = ["A", "B", "C", "D", "E", "F", "G", "H", "I"]

            var move = position_y_arr[notif.args.y - 1] + notif.args.x;
            var className = "last-move-tile-black";
            var idName = "move-record-black";
            var idActiveName = "active-white";
            if (color === "ffffff") {
                className = "last-move-tile-white";
                idName = "move-record-white";
                idActiveName = "active-black";
            };

            document.getElementById(idName).insertAdjacentHTML(
                `afterbegin`,
                `<div class="${className}">${move}</div>`
            );

            document.querySelector('#active-player').remove();

            document.getElementById(idActiveName).insertAdjacentHTML(
                `beforeend`,
                `<div id="active-player"></div>`
            );

            document.querySelectorAll('.possibleMoves').forEach(div => div.classList.remove('possibleMoves'));
        },

        notif_turnOverDiscs: function(notif) {

            // Make these discs blink and then remove them
            var turnedOverDiscs = notif.args.turnedOver;
            for (let j = 0; j < turnedOverDiscs.length; j++) {
                for (var i in turnedOverDiscs[j]) {
                    var disc = turnedOverDiscs[j][i];
                    
                    this.boardData = this.boardData.filter( item => item.x != disc.x && item.y != disc.y );
    
                    // Make the disc blink once and then remove it
                    var anim = dojo.fx.chain([
                        dojo.fadeOut({ node: 'disc_' + disc.x + '' + disc.y }),
                        dojo.fadeIn({ node: 'disc_' + disc.x + '' + disc.y }),
                        dojo.fadeOut({ 
                            node: 'disc_' + disc.x + '' + disc.y,
                            onEnd: function(node){
                                dojo.destroy(node); // Remove the DOM element after animation
                            }
                        })
                    ]); // end of dojo.fx.chain
            
                    // ... and launch the animation
                    anim.play();
                }
            }
        },

        notif_newScores: function( notif )
        {
            for( var player_id in notif.args.scores )
            {
                var color = notif.args.colors[ player_id ];
                var newScore = notif.args.scores[ player_id ];
                var newStone = notif.args.stones[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );

                var idNumber = "1";
                
                if (color === "ffffff") idNumber = "2";
                
                var scoreId = "score-" + idNumber;
                var stoneId = "lodestone-" + idNumber;

                var scoreElement = dom.byId(scoreId);
                var stoneElement = dom.byId(stoneId);
                html.set(scoreElement, newScore);
                html.set(stoneElement, newStone);
            }

            this.checkBalance(notif.args.stones, notif.args.colors);
        },
        
   });             
});
