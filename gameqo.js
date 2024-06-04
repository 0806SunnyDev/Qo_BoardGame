/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GameQo implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameqo.js
 *
 * GameQo user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare", "dojo/dom", "dojo/html",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, dom, html) {
    return declare("bgagame.gameqo", ebg.core.gamegui, {
        constructor: function(){
            console.log('gameqo constructor');
              
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
            console.log( "Starting creating player boards" );
            console.log(gamedatas)

            const playerNameOne = document.getElementById('player-name-1');
            const playerNameTwo = document.getElementById('player-name-2');
            const playerOneStone = document.getElementById('lodestone-1');
            const playerOneScore = document.getElementById('score-1');
            const playerTwoStone = document.getElementById('lodestone-2');
            const playerTwoScore = document.getElementById('score-2');
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];

                // console.log('player => ', player['id']);

                if (player['color'] === '000000') {
                    playerNameOne.insertAdjacentText('afterbegin', player['name'])
                    playerOneStone.insertAdjacentText('afterbegin', player['stone']);
                    playerOneScore.insertAdjacentText('afterbegin', player['score']);
                } else {
                    playerNameTwo.insertAdjacentText('afterbegin', player['name'])
                    playerTwoStone.insertAdjacentText('afterbegin', player['stone']);
                    playerTwoScore.insertAdjacentText('afterbegin', player['score']);
                }
                            
                // TODO: Setting up players boards if needed
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"
            const board = document.getElementById('board');
            
            const hor_scale = 78;
            const ver_scale = 78;
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
                const moveRecord = document.getElementById('move-record');

                for (let i = 0; i < gamedatas.record.length; i++) {
                    const player_id = gamedatas.record[i]['player']
                    var player_color = gamedatas.players[player_id]['color'];
                    
                    let className = "last-move-tile-white";

                    if (player_color === "000000") {
                        className = "last-move-tile-black";
                    }

                    moveRecord.insertAdjacentHTML(
                        `afterbegin`, 
                        `<div class="last-move-slot"><div class="${className}"></div><div class="last-move-number">${gamedatas.record[i]['position']}</div></div>`
                    );
                    
                }
            }

            // this.addDiscOnBoard( 2, 3, this.player_id );

            document.querySelectorAll('.square').forEach(square => square.addEventListener('click', e => this.onPlayDisc(e)));
            
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
           console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'playerTurn':
                this.updatePossibleMoves( args.args.possibleMoves );
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
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
                      
            if( this.isCurrentPlayerActive() )
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
        },

        updatePossibleMoves: function( possibleMoves )
        {
            // Remove current possible moves
            document.querySelectorAll('.possibleMove').forEach(div => div.classList.remove('possibleMove'));

            for( var x in possibleMoves )
            {
                for( var y in possibleMoves[ x ] )
                {
                    // x,y is a possible move
                    document.getElementById(`square_${x}_${y}`).classList.add('possibleMove');
                }            
            }
                        
            this.addTooltipToClass( 'possibleMove', '', _('Place a lodestone here') );
        },

        onPlayDisc: function( evt )
        {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // Get the cliqued square x and y
            // Note: square id format is "square_X_Y"
            var coords = evt.currentTarget.id.split('_');
            var x = coords[1];
            var y = coords[2];

            if(!document.getElementById(`square_${x}_${y}`).classList.contains('possibleMove')) {
                // This is not a possible move => the click does nothing
                return ;
            }
            
            if( this.checkAction( 'playDisc' ) )    // Check that this action is possible at this moment
            {            
                this.ajaxcall( "/gameqo/gameqo/playDisc.html", {
                    x:x,
                    y:y
                }, this, function( result ) {} );
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


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

            this.ajaxcall( "/gameqo/gameqo/myAction.html", { 
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
                  your gameqo.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            const notifs = [
                ['playDisc', 500],
                ['turnOverDiscs', 1500],
                ['newScores', 1],
            ];
    
            notifs.forEach((notif) => {
                dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
                this.notifqueue.setSynchronous(notif[0], notif[1]);
            });
        },
        
        notif_playDisc: function( notif )
        {
            // Remove current possible moves (makes the board more clear)
            document.querySelectorAll('.possibleMove').forEach(div => div.classList.remove('possibleMove'));
        
            this.addDiscOnBoard( notif.args.x, notif.args.y, notif.args.player_id );

            var color = notif.args.colors[ notif.args.player_id ];

            var position_y_arr = ["A", "B", "C", "D", "E", "F", "G", "H", "I"]

            var move = position_y_arr[notif.args.y - 1] + notif.args.x;
            var className = "last-move-tile-black";
            if (color === "ffffff") className = "last-move-tile-white"

            document.getElementById("move-record").insertAdjacentHTML(
                `afterbegin`,
                `<div class="last-move-slot"><div class="${className}"></div><div class="last-move-number">${move}</div></div>`
            )

        },

        notif_turnOverDiscs: function(notif) {
            // Make these discs blink and then hide them
            for (var i in notif.args.turnedOver) {
                var disc = notif.args.turnedOver[i];
                
                // Make the disc blink once and then hide it
                var anim = dojo.fx.chain([
                    dojo.fadeOut({ node: 'disc_' + disc.x + '' + disc.y }),
                    dojo.fadeIn({ node: 'disc_' + disc.x + '' + disc.y }),
                    dojo.fadeOut({ 
                        node: 'disc_' + disc.x + '' + disc.y
                    })
                ]); // end of dojo.fx.chain
        
                // ... and launch the animation
                anim.play();
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


        }
   });             
});
