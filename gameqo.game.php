<?php
 /**
  *------
  * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
  * GameQo implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * gameqo.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class GameQo extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        $this->initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "gameqo";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = [] )
    {    
        // Create players
        $default_color = array( "000000", "ffffff" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            
            if( $color == '000000' )
                $blackplayer_id = $player_id;
            else
                $whiteplayer_id = $player_id;
        }
        $sql .= implode( ',', $values );
        $this->DbQuery( $sql );
        $this->reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = [];
        list( $blackplayer_id, $whiteplayer_id ) = array_keys( $players );
        for( $x=1; $x<=9; $x++ )
        {
            for( $y=1; $y<=9; $y++ )
            {
                $token_value = "NULL";
                if( ($x==4 && $y==5) || ($x==5 && $y==4) )  // Initial positions of white player
                    $token_value = "'$whiteplayer_id'";
                else if( ($x==5 && $y==6) || ($x==6 && $y==5) )  // Initial positions of black player
                    $token_value = "'$blackplayer_id'";
                    
                $sql_values[] = "('$x','$y',$token_value)";
            }
        }
        $sql .= implode( ',', $sql_values );
        $this->DbQuery( $sql );
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = $this->getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_color color FROM player ";
        $result['players'] = $this->getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Get reversi board token
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    // Get the list of possible moves (x => y => true)
    function getPossibleMoves( int $player_id ): array
    {
        $result = [];
        
        $board = $this->getBoard();
        
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
                $returned = $this->getTurnedOverDiscs( $x, $y, $player_id, $board );
                if( count( $returned ) == 0 )
                {
                    // No discs returned => not a possible move
                }
                else
                {
                    // Okay => set this coordinate to "true"
                    if( ! isset( $result[$x] ) )
                        $result[$x] = [];
                        
                    $result[$x][$y] = true;
                }
            }
        }
                
        return $result;
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in gameqo.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        $this->checkAction( 'playCard' ); 
        
        $player_id = $this->getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        $this->notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => $this->getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn(): array
    {
        return [
            'possibleMoves' => $this->getPossibleMoves( intval($this->getActivePlayerId()) )
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPlayer(): void
    {
        // Active next player
        $player_id = intval($this->activeNextPlayer());

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = $this->getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                    FROM board
                                                    GROUP BY board_player", true );

        if( ! isset( $player_to_discs[ null ] ) )
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        else if( ! isset( $player_to_discs[ $player_id ] ) )
        {
            // Active player has no more disc on the board => he looses immediately
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        
        // Can this player play?

        $possibleMoves = $this->getPossibleMoves( $player_id );
        if( count( $possibleMoves ) == 0 )
        {

            // This player can't play
            // Can his opponent play ?
            $opponent_id = $this->getUniqueValueFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' " );
            if( count( $this->getPossibleMoves( $opponent_id ) ) == 0 )
            {
                // Nobody can move => end of the game
                $this->gamestate->nextState( 'endGame' );
            }
            else
            {            
                // => pass his turn
                $this->gamestate->nextState( 'cantPlay' );
            }
        }
        else
        {
            // This player can play. Give him some extra time
            $this->giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
