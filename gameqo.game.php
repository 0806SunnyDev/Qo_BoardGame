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
        
        $this->initGameStateLabels( [] );        
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
                // if( ($x==4 && $y==5) || ($x==6 && $y==5) )  // Initial positions of white player
                //     $token_value = "'$whiteplayer_id'";
                // else if( ($x==5 && $y==4) || ($x==5 && $y==6) )  // Initial positions of black player
                //     $token_value = "'$blackplayer_id'";
                    
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
        $sql = "SELECT player_id id, player_score score, player_color color, player_stone stone FROM player ";
        $result['players'] = $this->getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Get reversi board token
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );

        $result['record'] = self::getObjectListFromDB( "SELECT player, position FROM record " );
  
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

    // Get the list of returned disc when "player" we play at this place ("x", "y"),
    //  or a void array if no disc is returned (invalid move)
    function getTurnedOverDiscs( int $x, int $y, int $player, array $board ): array
    {
        $turnedOverDiscs = [];
        
        if( $board[ $x ][ $y ] === null ) // If there is already a disc on this place, this can't be a valid move
        {
            // For each directions...
            $directions = array( array( -1,0 ), array( 0, -1), array( 0,1 ), array( 1,0 ) );
            $mayBeTurnedOver = [];
            
            foreach( $directions as $direction )
            {
                // Starting from the square we want to place a disc...
                $current_x = $x;
                $current_y = $y;
                $mayBeTurnedOverForOneStone = [];
                $bContinue = true;
                $flag = false;

                while( $bContinue )
                {
                    // Go to the next square in this direction
                    $current_x += $direction[0];
                    $current_y += $direction[1];
                    
                    if( $current_x<1 || $current_x>9 || $current_y<1 || $current_y>9 )
                        $bContinue = false; // Out of the board => stop here for this direction
                    else if( $board[ $current_x ][ $current_y ] === null )
                    {
                        if ( $flag ) $mayBeTurnedOver = [];
                        $bContinue = false; // An empty square => stop here for this direction
                    }
                    else if( $board[ $current_x ][ $current_y ] == $player )
                    {
                        $bContinue = false;
                    }
                    else if( $board[ $current_x ][ $current_y ] != $player )
                    {
                        $flag = true;
                        $mayBeTurnedOverForOneStone = array( 'x' => $current_x, 'y' => $current_y );
                        $mayBeTurnedOver[] = $mayBeTurnedOverForOneStone;
                    }
                }

                if (count($mayBeTurnedOver) > 0) {
                    $turnedOverDiscs = $mayBeTurnedOver;
                }
            } 
        }
        return $turnedOverDiscs;
    }
    
    // Get the complete board with a double associative array
    function getBoard(): array
    {
        return $this->getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board", true );
    }

    // Get the list of possible moves (x => y => true)
    function getPossibleMoves( int $player_id ): array
    {
        $result = [];
        
        $board = $this->getBoard();
        
        for( $x=1; $x<=9; $x++ )
        {
            for( $y=1; $y<=9; $y++ )
            {
                if( $board[ $x ][ $y ] === null ) $result[$x][$y] = true;
            }
        }
                
        return $result;
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function playDisc( int $x, int $y )
    {
        // Check that this player is active and that this action is possible at this moment
        $this->checkAction( 'playDisc' );  
        
        $player_id = intval($this->getActivePlayerId()); 

        $positionArr = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I');
        // Now, check if this is a possible move
        $board = $this->getBoard();
        $turnedOverDiscs = $this->getTurnedOverDiscs( $x, $y, $player_id, $board );

        // Let's place a disc at x,y and return all "$returned" discs to the active player
        $sql = "UPDATE board SET board_player='$player_id'
                WHERE board_x='$x' AND board_y='$y'";

        $this->DbQuery( $sql );

        $sql = "UPDATE player SET player_stone = player_stone - 1 WHERE player_id='$player_id'";
        $this->DbQuery( $sql );

        $sql = "INSERT INTO `record`(`player`, `position`) VALUES ('" . $player_id . "', '" . $positionArr[$y] . $x . "')";
        $this->DbQuery( $sql );
        
        if( count( $turnedOverDiscs ) > 0 )
        {
            if (count($turnedOverDiscs) === 1) {
                $sql = "UPDATE board SET board_player=NULL
                        WHERE board_x=" . $turnedOverDiscs[0]['x'] . " AND board_y=" . $turnedOverDiscs[0]['y'];
            } else {
                $sql = "UPDATE board SET board_player=NULL WHERE ( board_x, board_y) IN ( ";
                
                foreach( $turnedOverDiscs as $turnedOver )
                {
                    $sql .= "('" . $turnedOver['x'] . "', '" . $turnedOver['y'] . "'),";
                }
                $sql = substr( $sql, 0, -1) . " )";
            }
                    
            $this->DbQuery( $sql );
            
            // Update scores according to the number of disc on board
            $sql = "UPDATE player
                    SET player_score = player_score + " . count( $turnedOverDiscs );
            $sql .= " WHERE player_id='$player_id'";
            $this->DbQuery( $sql );
        }
            
        // Statistics
        $this->incStat( count( $turnedOverDiscs ), "turnedOver", $player_id );
        if( ($x==1 && $y==1) || ($x==9 && $y==1) || ($x==1 && $y==9) || ($x==9 && $y==9) )
            $this->incStat( 1, 'discPlayedOnCorner', $player_id );
        else if( $x==1 || $x==9 || $y==1 || $y==9 )
            $this->incStat( 1, 'discPlayedOnBorder', $player_id );
        else if( $x>=3 && $x<=7 && $y>=3 && $y<=7 )
            $this->incStat( 1, 'discPlayedOnCenter', $player_id );
        
        // Notify
        $newScores = $this->getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
        $newStones = $this->getCollectionFromDb( "SELECT player_id, player_stone FROM player", true );
        $newColors = $this->getCollectionFromDb( "SELECT player_id, player_color FROM player", true );
        
        $this->notifyAllPlayers( "playDisc", clienttranslate( '${player_name} plays a lodestone and captured ${returned_nbr} lodestone(s)' ), array(
            'player_id' => $player_id,
            'player_name' => $this->getActivePlayerName(),
            'returned_nbr' => count( $turnedOverDiscs ),
            'colors' => $newColors,
            'x' => $x,
            'y' => $y
        ) );

        $this->notifyAllPlayers( "turnOverDiscs", '', array(
            'player_id' => $player_id,
            'turnedOver' => $turnedOverDiscs
        ) );
        
        $this->notifyAllPlayers( "newScores", "", array(
            "scores" => $newScores,
            "stones" => $newStones,
            "colors" => $newColors,
        ) );
        
        // Then, go to the next state
        $this->gamestate->nextState( 'playDisc' );
    }

    
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

        $board = $this->getBoard();

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = $this->getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                    FROM board
                                                    GROUP BY board_player", true );
        $player_remain_stones = $this->getCollectionFromDb( "SELECT player_id, player_stone
                                                        FROM player", true);

        $i = 1;
        $j = 1;
        $h_flag = true;
        $v_flag = true;

        // while ($i <= 9 && ( $h_flag || $v_flag )) {
        //     while ($j <= 9 && ( $h_flag || $v_flag )) {
        //         if ($board[$i][$j] != $player_id) $h_flag = false;
        //         if ($board[$j][$i] != $player_id) $v_flag = false;
        //         $j++;
        //     }
            
        //     if ($h_flag || $v_flag) {
        //         echo("##### => end of game at this moment");
        //         $this->gamestate->nextState( 'endGame' );
        //         return ;
        //     } else {
        //         $h_flag = true;
        //         $v_flag = true;
        //         $i++;
        //     }
        // }

        if( ! isset( $player_to_discs[ null ] ) )
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            echo("##### => there's no more free place on the board");
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        else if( $player_remain_stones[$player_id] == "0" )
        {
            // Active player has no more lodestones to play on the board
            echo("##### => Active player has no more lodestones to play on the board");
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        else
        {
            // This player can play. Give him some extra time
            $this->giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }
    }

    function stGameEnd()
    {
        // Calculate final scores, if necessary
        // $finalScores = $this->calculateFinalScores();

        // Notify all players about the end of the game
        $this->notifyAllPlayers("endGame", clienttranslate("The game has ended."), array(
            // Add any relevant data here
        ));

        // Go to the end of game state
        $this->gamestate->nextState("gameEnd");
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
