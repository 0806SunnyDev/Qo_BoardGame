<?php
 /**
  *------
  * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
  * Qo implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * qo.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Qo extends Table
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
        return "qo";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
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
        $sql = "SELECT player_id id, player_score score, player_color color, player_stone stone, player_captured captured FROM player ";
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
        $colors = $this->getCollectionFromDb( "SELECT player_id, player_color FROM player", true );
        $playerColor = $colors[$player];
        $oppStoneCountLimit = ($playerColor == "000000") ? 4 : 3;
        $playerStoneCountLimit = ($playerColor == "000000") ? 3 : 4;
        
        $turnedOverDiscs = [];
        $turnedOverDiscs[0] = [];
        $turnedOverDiscs[1] = [];
        $oppTurnedOverDiscs = [];
        $playerTurnedOverDiscs = [];

        if( $board[$x][$y] === NULL ) // If there is already a disc on this place, this can't be a valid move
        {
            $board[$x][$y] = "" . $player;

            $mayBeTurnedHorizonPlayerDisc = [];
            $mayBeTurnedHorizonOppDisc = [];
            $mayBeTurnedVerticalPlayerDisc = [];
            $mayBeTurnedVerticalOppDisc = [];
            $mayBeTurnedDiagonOnePlayerDisc = [];
            $mayBeTurnedDiagonOneOppDisc = [];
            $mayBeTurnedDiagonTwoPlayerDisc = [];
            $mayBeTurnedDiagonTwoOppDisc = [];

            $capPlayerFlagVertical = true;
            $capOppFlagVertical = true;
            $capPlayerFlagHorizontal = true;
            $capOppFlagHorizontal = true;

            for ($i=1; $i<=9; $i++) { 
                for ($j=1; $j<=9; $j++) {
                    // vertical check
                    if ($board[$j][$i]==$player) {
                        if ($j===1) {
                            $mayBeTurnedVerticalPlayerDisc = [];
                            $mayBeTurnedVerticalPlayerDisc[] = ['x'=>$j, 'y'=>$i];
                            $capPlayerFlagVertical=true;
                        } elseif ($board[$j-1][$i]==$player) {
                            $mayBeTurnedVerticalPlayerDisc[] = ['x'=>$j, 'y'=>$i];

                            if (count($mayBeTurnedVerticalPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagVertical)
                            {
                                if ($j>8) $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedVerticalPlayerDisc);
                                elseif (($board[$j+1][$i]!==NULL)&&($board[$j+1][$i]!=$player)) {
                                    $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedVerticalPlayerDisc);
                                };
                            };
                        } elseif (
                            ($board[$j-1][$i]!=$player)
                            && ($board[$j-1][$i]!==NULL)
                        ) {
                            $mayBeTurnedVerticalPlayerDisc = [];
                            $mayBeTurnedVerticalPlayerDisc[] = ['x'=>$j, 'y'=>$i];
                            $capPlayerFlagVertical = true;

                            if (count($mayBeTurnedVerticalOppDisc)===$oppStoneCountLimit && $capOppFlagVertical) {
                                $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedVerticalOppDisc);
                            };

                            $mayBeTurnedVerticalOppDisc = [];
                            $capOppFlagVertical = false;
                        } elseif ($board[$j-1][$i]===NULL) {
                            $capPlayerFlagVertical=false;

                        }
                    };

                    if (($board[$j][$i]!=$player)&&($board[$j][$i]!==NULL)) {
                        if ($j===1) {
                            $mayBeTurnedVerticalOppDisc = [];
                            $mayBeTurnedVerticalOppDisc[] = ['x'=>$j, 'y'=>$i];
                            $capOppFlagVertical=true;
                        } elseif ($board[$j-1][$i]==$player) {
                            $mayBeTurnedVerticalOppDisc = [];
                            $mayBeTurnedVerticalOppDisc[] = ['x'=>$j, 'y'=>$i];
                            $capOppFlagVertical=true;
                            
                            if (count($mayBeTurnedVerticalPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagVertical) {
                                $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedVerticalPlayerDisc);
                            };

                            $mayBeTurnedVerticalPlayerDisc = [];
                            $capPlayerFlagVertical=false;
                        } elseif (
                            ($board[$j-1][$i]!=$player)
                            && ($board[$j-1][$i]!==NULL)
                        ) {
                            $mayBeTurnedVerticalOppDisc[] = ['x'=>$j, 'y'=>$i];

                            if (count($mayBeTurnedVerticalOppDisc)===$oppStoneCountLimit && $capOppFlagVertical)
                            {
                                if ($j>8) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedVerticalOppDisc);
                                elseif ($board[$j+1][$i]==$player) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedVerticalOppDisc);
                            };
                        } elseif ($board[$j-1][$i]===NULL) {
                            $capOppFlagVertical=false;
                        }
                    };

                    if ($board[$j][$i]===NULL) {
                        $mayBeTurnedVerticalOppDisc = [];
                        $mayBeTurnedVerticalPlayerDisc = [];
                        $capPlayerFlagVertical=false;
                        $capOppFlagVertical=false;

                    };

                    // horizontal check
                    if ($board[$i][$j]==$player) {
                        if ($j===1) {
                            $mayBeTurnedHorizonPlayerDisc = [];
                            $mayBeTurnedHorizonPlayerDisc[] = ['x'=>$i, 'y'=>$j];
                            $capPlayerFlagHorizontal = true;
                        } else if ($board[$i][$j-1]==$player) {
                            $mayBeTurnedHorizonPlayerDisc[] = ['x'=>$i, 'y'=>$j];

                            if (count($mayBeTurnedHorizonPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagHorizontal) {
                                if ($j>8) $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedHorizonPlayerDisc);
                                elseif (($board[$i][$j+1] !== NULL)&&($board[$i][$j+1] != $player)) {
                                    $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedHorizonPlayerDisc);
                                };
                            };
                        } elseif (($board[$i][$j-1] != $player)&&($board[$i][$j-1] !== NULL)) {
                            $mayBeTurnedHorizonPlayerDisc = [];
                            $mayBeTurnedHorizonPlayerDisc[] = ['x'=>$i, 'y'=>$j];
                            $capPlayerFlagHorizontal = true;

                            if (count($mayBeTurnedHorizonOppDisc)===$oppStoneCountLimit && $capOppFlagHorizontal) {
                                $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedHorizonOppDisc);
                            };

                            $mayBeTurnedHorizonOppDisc = [];
                            $capOppFlagHorizontal = false;
                        } elseif ($board[$i][$j-1]===NULL) {
                            $capPlayerFlagHorizontal=false;
                        }
                    };

                    if (($board[$i][$j] != $player)&&($board[$i][$j] !== NULL)) {
                        if ($j===1) {
                            $mayBeTurnedHorizonOppDisc = [];
                            $mayBeTurnedHorizonOppDisc[] = ['x'=>$i, 'y'=>$j];
                            $capOppFlagHorizontal = true;
                        } elseif ($board[$i][$j-1]==$player) {
                            $mayBeTurnedHorizonOppDisc = [];
                            $mayBeTurnedHorizonOppDisc[] = ['x'=>$i, 'y'=>$j];
                            $capOppFlagHorizontal = true;

                            if (count($mayBeTurnedHorizonPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagHorizontal) {
                                $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedHorizonPlayerDisc);
                            };

                            $mayBeTurnedHorizonPlayerDisc = [];
                            $capPlayerFlagHorizontal = false;
                        } elseif (($board[$i][$j-1] != $player)&&($board[$i][$j-1] !== NULL)) {
                            $mayBeTurnedHorizonOppDisc[] = ['x'=>$i, 'y'=>$j];

                            if (count($mayBeTurnedHorizonOppDisc)===$oppStoneCountLimit && $capOppFlagHorizontal) {
                                if ($j>8) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedHorizonOppDisc);
                                elseif ($board[$i][$j+1]==$player) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedHorizonOppDisc);
                            };
                        } elseif ($board[$i][$j-1]===NULL) {
                            $capOppFlagHorizontal=false;
                        }
                    };

                    if ($board[$i][$j] ===NULL) {
                        $mayBeTurnedHorizonOppDisc = [];
                        $mayBeTurnedHorizonPlayerDisc = [];
                        $capPlayerFlagHorizontal = false;
                        $capOppFlagHorizontal = false;
                    };

                    // Diagonal check
                    $current_x = 0;
                    $current_y = 0;
                    $check_x = 0;
                    $check_y = 0;
                    $end_x = 0;
                    $end_y = 0;

                    $capPlayerFlagDiagonOne = true;
                    $capOppFlagDiagonOne = true;
                    $capPlayerFlagDiagonTwo = true;
                    $capOppFlagDiagonTwo = true;

                    for ($k=0; $k < 9; $k++) {
                        if ($i === 1) {
                            $current_x = ($i+$k>0 && $i+$k<=9) ? $i+$k : NULL;
                            $current_y = ($j+$k>0 && $j+$k<=9) ? $j+$k : NULL;
                            $check_x = ($current_x-1>0 && $current_x-1<=9) ? $current_x-1 : NULL;
                            $check_y = ($current_y-1>0 && $current_y-1<=9) ? $current_y-1 : NULL;
                            $end_x = ($current_x+1>0 && $current_x+1<=9) ? $current_x+1 : NULL;
                            $end_y = ($current_y+1>0 && $current_y+1<=9) ? $current_y+1 : NULL;
                        }

                        if ($i === 9) {
                            $current_x = ($i-$k>0 && $i-$k<=9) ? $i-$k : NULL;
                            $current_y = ($j-$k>0 && $j-$k<=9) ? $j-$k : NULL;
                            $check_x = (($current_x+1>0) && ($current_x+1<=9)) ? ($current_x+1) : NULL;
                            $check_y = (($current_y+1>0) && ($current_y+1<=9)) ? ($current_y+1) : NULL;
                            $end_x = (($current_x-1>0) && ($current_x-1<=9)) ? ($current_x-1) : NULL;
                            $end_y = (($current_y-1>0) && ($current_y-1<=9)) ? ($current_y-1) : NULL;
                        }

                        
                        if (($i === 1 || $i === 9) && $current_x && $current_y) {
                            if ($board[$current_x][$current_y] == $player) {
                                if ($k === 0) {
                                    $mayBeTurnedDiagonOnePlayerDisc = [];
                                    $mayBeTurnedDiagonOnePlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    $capPlayerFlagDiagonOne = true;
                                } elseif ($board[$check_x][$check_y] == $player) {
                                    $mayBeTurnedDiagonOnePlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    if ((count($mayBeTurnedDiagonOnePlayerDisc)===$playerStoneCountLimit) && $capPlayerFlagDiagonOne) {
                                        if ($end_x===NULL || $end_y===NULL) {
                                            $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonOnePlayerDisc);
                                        }
                                        elseif (($board[$end_x][$end_y] != $player)&&($board[$end_x][$end_y] !== NULL)) {
                                            $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonOnePlayerDisc);
                                        }
                                    };
                                } elseif (($board[$check_x][$check_y] != $player)&&($board[$check_x][$check_y] !== NULL)) {
                                    $mayBeTurnedDiagonOnePlayerDisc = [];
                                    $mayBeTurnedDiagonOnePlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];

                                    if ((count($mayBeTurnedDiagonOneOppDisc)===$oppStoneCountLimit) && $capOppFlagDiagonOne) {
                                        $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonOneOppDisc);
                                    };
                                    $mayBeTurnedDiagonOneOppDisc = [];

                                    $capPlayerFlagDiagonOne = true;
                                    $capOppFlagDiagonOne = false;
                                } elseif ($board[$check_x][$check_y] === NULL) {
                                    $capPlayerFlagDiagonOne = false;
                                }
                            }

                            if ($board[$current_x][$current_y] != $player && $board[$current_x][$current_y] !== NULL) {
                                if ($k === 0) {
                                    $mayBeTurnedDiagonOneOppDisc = [];
                                    $mayBeTurnedDiagonOneOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];

                                    $capOppFlagDiagonOne = true;
                                } elseif ($board[$check_x][$check_y] == $player) {
                                    $mayBeTurnedDiagonOneOppDisc = [];
                                    $mayBeTurnedDiagonOneOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];

                                    if ((count($mayBeTurnedDiagonOnePlayerDisc)===$playerStoneCountLimit) && $capPlayerFlagDiagonOne) {
                                        $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonOnePlayerDisc);
                                    };
        
                                    $mayBeTurnedDiagonOnePlayerDisc = [];
                                    $capOppFlagDiagonOne = true;
                                    $capPlayerFlagDiagonOne = false;
                                } elseif (($board[$check_x][$check_y] != $player)&&($board[$check_x][$check_y] !== NULL)) {
                                    $mayBeTurnedDiagonOneOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    
                                    if ((count($mayBeTurnedDiagonOneOppDisc)===$oppStoneCountLimit) && $capOppFlagDiagonOne) {
                                        if ($end_x===NULL || $end_y===NULL) {
                                            $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonOneOppDisc);
                                        }
                                        elseif ($board[$end_x][$end_y]==$player) {
                                            $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonOneOppDisc);
                                        }
                                    };
                                } elseif ($board[$check_x][$check_y] === NULL) {
                                    $capOppFlagDiagonOne = false;
                                }
                            }

                            if ($board[$current_x][$current_y] === NULL) {
                                $mayBeTurnedDiagonOneOppDisc = [];
                                $mayBeTurnedDiagonOnePlayerDisc = [];
                                $capPlayerFlagDiagonOne = false;
                                $capOppFlagDiagonOne = false;
                            }
                        }
                    };

                    for ($k=0; $k < 9; $k++) {
                        if ($j === 1) {
                            $current_x = ($i-$k>0 && $i-$k<=9) ? $i-$k : NULL;
                            $current_y = ($j+$k>0 && $j+$k<=9) ? $j+$k : NULL;
                            $check_x = ($current_x+1>0 && $current_x+1<=9) ? $current_x+1 : NULL;
                            $check_y = ($current_y-1>0 && $current_y-1<=9) ? $current_y-1 : NULL;
                            $end_x = ($current_x-1>0 && $current_x-1<=9) ? $current_x-1 : NULL;
                            $end_y = ($current_y+1>0 && $current_y+1<=9) ? $current_y+1 : NULL;
                        }

                        if ($j === 9) {
                            $current_x = ($i+$k>0 && $i+$k<=9) ? $i+$k : NULL;
                            $current_y = ($j-$k>0 && $j-$k<=9) ? $j-$k : NULL;
                            $check_x = ($current_x-1>0 && $current_x-1<=9) ? $current_x-1 : NULL;
                            $check_y = ($current_y+1>0 && $current_y+1<=9) ? $current_y+1 : NULL;
                            $end_x = ($current_x+1>0 && $current_x+1<=9) ? $current_x+1 : NULL;
                            $end_y = ($current_y-1>0 && $current_y-1<=9) ? $current_y-1 : NULL;
                        }

                        if (($j === 1 || $j === 9) && $current_x && $current_y) {
                            if ($board[$current_x][$current_y] == $player) {
                                if ($k === 0) {
                                    $mayBeTurnedDiagonTwoPlayerDisc = [];
                                    $mayBeTurnedDiagonTwoPlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    $capPlayerFlagDiagonTwo = true;
                                } elseif ($board[$check_x][$check_y] == $player) {
                                    $mayBeTurnedDiagonTwoPlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    
                                    if (count($mayBeTurnedDiagonTwoPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagDiagonTwo) {
                                        if ($end_x===NULL || $end_y===NULL) $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonTwoPlayerDisc);
                                        elseif (($board[$end_x][$end_y] != $player)&&($board[$end_x][$end_y] !== NULL)) $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonTwoPlayerDisc);
                                    };
                                } elseif (($board[$check_x][$check_y] != $player)&&($board[$check_x][$check_y] !== NULL)) {
                                    $mayBeTurnedDiagonTwoPlayerDisc = [];
                                    $mayBeTurnedDiagonTwoPlayerDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    $capPlayerFlagDiagonTwo = true;
                                    
                                    if (count($mayBeTurnedDiagonTwoOppDisc)===$oppStoneCountLimit && $capOppFlagDiagonTwo) {
                                        $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonTwoOppDisc);
                                    };
                                    
                                    $mayBeTurnedDiagonTwoOppDisc = [];
                                    $capOppFlagDiagonTwo = false;
                                } elseif ($board[$check_x][$check_y] === NULL) {
                                    $capPlayerFlagDiagonTwo = false;
                                }
                            }
                            
                            if ($board[$current_x][$current_y] != $player && $board[$current_x][$current_y] !== NULL) {
                                if ($k === 0) {
                                    $mayBeTurnedDiagonTwoOppDisc = [];
                                    $mayBeTurnedDiagonTwoOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    $capOppFlagDiagonTwo = true;
                                } elseif ($board[$check_x][$check_y] == $player) {
                                    $mayBeTurnedDiagonTwoOppDisc = [];
                                    $mayBeTurnedDiagonTwoOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    
                                    if (count($mayBeTurnedDiagonTwoPlayerDisc)===$playerStoneCountLimit && $capPlayerFlagDiagonTwo) {
                                        $playerTurnedOverDiscs = array_merge($playerTurnedOverDiscs, $mayBeTurnedDiagonTwoPlayerDisc);
                                    };
                                    
                                    $mayBeTurnedDiagonTwoPlayerDisc = [];
                                    $capOppFlagDiagonTwo = true;
                                    $capPlayerFlagDiagonTwo = false;
                                } elseif (($board[$check_x][$check_y] != $player)&&($board[$check_x][$check_y] !== NULL)) {
                                    $mayBeTurnedDiagonTwoOppDisc[] = ['x'=>$current_x, 'y'=>$current_y];
                                    
                                    if (count($mayBeTurnedDiagonTwoOppDisc)===$oppStoneCountLimit && $capOppFlagDiagonTwo) {
                                        if ($end_x===NULL || $end_y===NULL) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonTwoOppDisc);
                                        elseif ($board[$end_x][$end_y]==$player) $oppTurnedOverDiscs = array_merge($oppTurnedOverDiscs, $mayBeTurnedDiagonTwoOppDisc);
                                    };
                                } elseif ($board[$check_x][$check_y] === NULL) {
                                    $capOppFlagDiagonTwo = false;
                                }
                            }
                            
                            if ($board[$current_x][$current_y] === NULL) {
                                $mayBeTurnedDiagonTwoOppDisc = [];
                                $mayBeTurnedDiagonTwoPlayerDisc = [];
                                $capPlayerFlagDiagonTwo = false;
                                $capOppFlagDiagonTwo = false;
                            }
                        }
                    }
                }
            }

        }
        // Convert the 2D array to a 1D array
        $flattenedArray = array_map(function($element) {
            return implode(',', $element);
        }, $oppTurnedOverDiscs);

        // Remove duplicates
        $uniqueArray = array_unique($flattenedArray);

        // Convert the 1D array back to a 2D array
        $turnedOverDiscs[0] = array_map(function($element) {
            return explode(',', $element);
        }, $uniqueArray);

        // Repeat the same for the second sub-array
        $flattenedArray = array_map(function($element) {
            return implode(',', $element);
        }, $playerTurnedOverDiscs);

        $uniqueArray = array_unique($flattenedArray);

        $turnedOverDiscs[1] = array_map(function($element) {
            return explode(',', $element);
        }, $uniqueArray);

        return $turnedOverDiscs;
    }
    
    // Get the complete board with a double associative array
    function getBoard(): array
    {
        return $this->getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board", true );
    }

    // Get the list of possible moves (x => y => true)
    function getEmptyPositions( int $player_id ): array
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

    function playDisc( int $x, int $y, $advanceState = true )
    {
        $moveFlag = false;
        $clickX = $x;
        $clickY = $y;
        $count = 0;
        // Check that this player is active and that this action is possible at this moment
        $this->checkAction( 'playDisc' );  
            
        $player_id = intval($this->getActivePlayerId());
        $stoneOwnerId = $player_id;
        $playerArr = $this->getCollectionFromDb( "SELECT player_id, player_name FROM player", true );
        $positionArr = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I');
        $board = $this->getBoard();

        if (strlen($x) == 2) {
            $moveFlag = true;
            $selectedX = intval(strval($x)[0]);
            $selectedY = intval(strval($x)[1]);
            $clickX = intval(strval($y)[0]);
            $clickY = intval(strval($y)[1]);
            $count = intval(strval($y)[2]);

            $sql = "SELECT board_player FROM board WHERE board_x='$selectedX' AND board_y='$selectedY'";
            $stoneOwner = $this->getObjectFromDB($sql);
            $stoneOwnerId = $stoneOwner["board_player"];

            $sql = "UPDATE board SET board_player=NULL
                    WHERE board_x='$selectedX' AND board_y='$selectedY'";

            $this->DbQuery( $sql );

        }

        // Now, check if this is a possible move
        $turnedOverDiscs = $this->getTurnedOverDiscs( $clickX, $clickY, $player_id, $board );
        
        // Let's place a disc at x,y and return all "$returned" discs to the active player
        if($stoneOwnerId == $player_id && count($turnedOverDiscs[1])===0){
            $sql = "UPDATE board SET board_player='$player_id'
                        WHERE board_x='$clickX' AND board_y='$clickY'";
            $this->DbQuery( $sql );

            $sql = "INSERT INTO `record`(`player`, `position`) VALUES ('" . $player_id . "', '" . $positionArr[$clickY] . $clickX . "')";
            $this->DbQuery( $sql );
        } elseif ($stoneOwnerId != $player_id && count($turnedOverDiscs[0])===0) {
            $sql = "UPDATE board SET board_player='$stoneOwnerId'
                        WHERE board_x='$clickX' AND board_y='$clickY'";
            $this->DbQuery( $sql );

            $sql = "INSERT INTO `record`(`player`, `position`) VALUES ('" . $stoneOwnerId . "', '" . $positionArr[$clickY] . $clickX . "')";
            $this->DbQuery( $sql );
        }

        $capturedNum = 0;
        $playerCapturedNum = 0;
        $lodestone = $count*2;

        if (strlen($x) == 1) $sql = "UPDATE player SET player_stone = player_stone - 1 WHERE player_id='$player_id'";
        else {
            $sql = "UPDATE player SET player_stone = player_stone - '$lodestone' WHERE player_id='$player_id'";
            
            $playerCapturedNum = $count * 2;
        }
        $this->DbQuery( $sql );

        if( count( $turnedOverDiscs[0] ) > 0 || count( $turnedOverDiscs[1] ) > 0 )
        {
            for ($i=0; $i < count( $turnedOverDiscs ); $i++) { 
                if (count($turnedOverDiscs[$i]) > 0) {
                    $sql = "UPDATE board SET board_player=NULL WHERE ( board_x, board_y) IN ( ";
            
                    foreach( $turnedOverDiscs[$i] as $turnedOver )
                    {
                        $sql .= "('" . $turnedOver[0] . "', '" . $turnedOver[1] . "'),";
                    }
                    $sql = substr( $sql, 0, -1) . " )";
                            
                    $this->DbQuery( $sql );
                }
            }

            $capturedNum = count($turnedOverDiscs[0]);
            $playerCapturedNum = $playerCapturedNum + count($turnedOverDiscs[1]);

            foreach ($playerArr as $id => $name) {
                if ($id != $player_id) {
                    $sql = "UPDATE player
                            SET player_captured = player_captured + " . $playerCapturedNum;
                    $sql .= " WHERE player_id='$id'";
                    $this->DbQuery( $sql );
                } else {
                    $sql = "UPDATE player
                            SET player_captured = player_captured + " . $capturedNum;
                    $sql .= " WHERE player_id='$id'";
                    $this->DbQuery( $sql );
                }
            }
        } else if ($playerCapturedNum > 0) {
            foreach ($playerArr as $id => $name) {
                if ($id != $player_id) {
                    $sql = "UPDATE player
                            SET player_captured = player_captured + " . $playerCapturedNum;
                    $sql .= " WHERE player_id='$id'";
                    $this->DbQuery( $sql );
                }
            }
        }

        // Statistics
        $this->incStat( count( $turnedOverDiscs[0] ), "turnedOver", $player_id );
        if( ($clickX==1 && $clickY==1) || ($clickX==9 && $clickY==1) || ($clickX==1 && $clickY==9) || ($clickX==9 && $clickY==9) )
            $this->incStat( 1, 'discPlayedOnCorner', $player_id );
        else if( $clickX==1 || $clickX==9 || $clickY==1 || $clickY==9 )
            $this->incStat( 1, 'discPlayedOnBorder', $player_id );
        else if( $clickX>=3 && $clickX<=7 && $clickY>=3 && $clickY<=7 )
            $this->incStat( 1, 'discPlayedOnCenter', $player_id );
        
        // Notify
        $newScores = $this->getCollectionFromDb( "SELECT player_id, player_captured FROM player", true );
        $newStones = $this->getCollectionFromDb( "SELECT player_id, player_stone FROM player", true );
        $newColors = $this->getCollectionFromDb( "SELECT player_id, player_color FROM player", true );
        $v_posArr = ["A","B","C","D","E","F","G","H","I"];
    
        if ($moveFlag) {
            $firstPos = $v_posArr[intval(strval($x)[1])-1] . intval(strval($x)[0]);
            $secondPos = "" . $v_posArr[intval($clickY)-1] . $clickX;

            $msg = '${player_name} moved ' . $firstPos . ' to ' . $secondPos;
            if (count( $turnedOverDiscs[0] )>0) $msg .= ' and captured ${returned_nbr} lodestone(s)';
            
            $this->notifyAllPlayers( "moveDisc", clienttranslate( $msg ), array(
                'player_id' => $player_id,
                'stone_owner_id' => $stoneOwnerId,
                'player_name' => $this->getActivePlayerName(),
                'returned_nbr' => $capturedNum,
                'colors' => $newColors,
                'beforeX' => intval(strval($x)[0]),
                'beforeY' => intval(strval($x)[1]),
                'x' => $clickX,
                'y' => $clickY
            ) );
        } else {
            $pos = "" . $v_posArr[intval($clickY)-1] . $clickX;

            $msg = '${player_name} placed a lodestone at ' . $pos;
            if (count( $turnedOverDiscs[0] )>0) $msg .= ' and captured ${returned_nbr} lodestone(s)';

            $this->notifyAllPlayers( "playDisc", clienttranslate( $msg ), array(
                'player_id' => $player_id,
                'player_name' => $this->getActivePlayerName(),
                'returned_nbr' => $capturedNum,
                'colors' => $newColors,
                'x' => $clickX,
                'y' => $clickY
            ) );
        };

        $this->notifyAllPlayers( "turnOverDiscs", '', array(
            'player_id' => $player_id,
            'turnedOver' => $turnedOverDiscs
        ) );
        
        $this->notifyAllPlayers( "newScores", "", array(
            "scores" => $newScores,
            "stones" => $newStones,
            "colors" => $newColors,
        ) );

        // Advance game state if specified
        if ($advanceState) {
            $this->gamestate->nextState( 'playDisc' );
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn(): array
    {
        return [
            'emptyPositions' => $this->getEmptyPositions( intval($this->getActivePlayerId()) )
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPlayer(): void
    {
        // Check if the active player is a zombie
        $active_player_id = intval($this->getActivePlayerId());
        if ($this->isZombie($active_player_id)) {
            // Only call zombieTurn in compatible game states
            $state = $this->gamestate->state();
            if ($state['type'] === "activeplayer") {
                $this->zombieTurn($state, $active_player_id);
                return;
            }
        }
        
        // Active next player
        $player_id = intval($this->activeNextPlayer());

        $board = $this->getBoard();

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = $this->getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                        FROM board
                                                        GROUP BY board_player", true );
        $player_remain_stones = $this->getCollectionFromDb( "SELECT player_id, player_stone
                                                        FROM player", true);
        $player_captured_stones = $this->getCollectionFromDb( "SELECT player_id, player_captured
                                                        FROM player", true );

        // Check it the active player has made the perfect horizontal or vertical stone line
        $player_v_flag = 1;
        $player_h_flag = 1;
        $opp_v_flag = 1;
        $opp_h_flag = 1;

        $winner_sql = "UPDATE player SET player_score = player_captured + 100 WHERE player_id='";
        $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id='";

        for ($i=1; $i <= 9; $i++) { 
            for ($j=1; $j <= 9; $j++) { 
                if($board[$i][$j] != $active_player_id) {
                    $player_v_flag = 2;
                }
                if($board[$i][$j] != $player_id) {
                    $opp_v_flag = 2;
                }
                if($board[$j][$i] != $active_player_id) {
                    $player_h_flag = 2;
                }
                if($board[$j][$i] != $player_id) {
                    $opp_h_flag = 2;
                }
            }
            if ($player_v_flag == 1 || $player_h_flag == 1) {
                $winner_sql .= $player_id . "'";
                $loser_sql .= $active_player_id . "'";
                $this->DbQuery($winner_sql);
                $this->DbQuery($loser_sql);
                $this->gamestate->nextState( 'endGame' );
                return ;
            } elseif ($opp_v_flag == 1 || $opp_h_flag == 1) {
                $winner_sql .= $active_player_id . "'";
                $loser_sql .= $player_id . "'";
                $this->DbQuery($winner_sql);
                $this->DbQuery($loser_sql);
                $this->gamestate->nextState( 'endGame' );
                return ;
            }
            else {
                $player_v_flag = 1;
                $player_h_flag = 1;
                $opp_v_flag = 1;
                $opp_h_flag = 1;
            }
        }

        if((!isset( $player_to_discs[ null ] )) || (intval($player_remain_stones[$active_player_id]) === 0))
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            $this->checkEndGame();
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
    
    function checkEndGame()
    {
        // Calculate final scores
        $finalScores = [];
        $remainStoneOnBoard = [];
        $playerArr = [];
        $players = $this->loadPlayersBasicInfos();
        
        $captured = $this->getCollectionFromDb( "SELECT player_id, player_captured FROM player", true );

        // Initialize scores array
        foreach ($players as $player_id => $player) {
            array_push($playerArr, $player_id);
            $finalScores[$player_id] = 0;
            $remainStoneOnBoard[$player_id] = 0;
        }

        // Calculate scores based on the final board state
        $board = $this->getBoard();
        for ($x = 1; $x <= 9; $x++) {
            for ($y = 1; $y <= 9; $y++) {
                if ($board[$x][$y] !== null) {
                    $remainStoneOnBoard[$board[$x][$y]]++;
                }
            }
        }

        $remainStones = $this->getCollectionFromDb( "SELECT player_id, player_stone FROM player", true );

        foreach ($players as $player_id => $player) {
            $finalScores[$player_id] = $remainStoneOnBoard[$player_id] + $remainStones[$player_id];
        }

        if (intval($captured[$playerArr[0]]) != intval($captured[$playerArr[1]])) {
            if (intval($captured[$playerArr[0]]) > intval($captured[$playerArr[1]])) {
                if (abs($finalScores[$playerArr[0]]-$finalScores[$playerArr[1]])<8) {
                    $winner = $playerArr[0];
                    $loser = $playerArr[1];
                    $winner_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $winner";
                    $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $loser";
                    $this->DbQuery($winner_sql);
                    $this->DbQuery($loser_sql);
                } elseif (abs($finalScores[$playerArr[0]]-$finalScores[$playerArr[1]])>=8) {
                    $winner = $playerArr[1];
                    $loser = $playerArr[0];
                    $winner_sql = "UPDATE player SET player_score = player_captured + 100 WHERE player_id = $winner";
                    $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $loser";
                    $this->DbQuery($winner_sql);
                    $this->DbQuery($loser_sql);
                }
            } elseif (intval($captured[$playerArr[0]]) < intval($captured[$playerArr[1]])) {
                if (abs($finalScores[$playerArr[0]]-$finalScores[$playerArr[1]])<8) {
                    $winner = $playerArr[1];
                    $loser = $playerArr[0];
                    $winner_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $winner";
                    $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $loser";
                    $this->DbQuery($winner_sql);
                    $this->DbQuery($loser_sql);
                } elseif (abs($finalScores[$playerArr[0]]-$finalScores[$playerArr[1]])>=8) {
                    $winner = $playerArr[0];
                    $loser = $playerArr[1];
                    $winner_sql = "UPDATE player SET player_score = player_captured + 100 WHERE player_id = $winner";
                    $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $loser";
                    $this->DbQuery($winner_sql);
                    $this->DbQuery($loser_sql);
                }
            }
            $winner_sql = "UPDATE player SET player_score = player_captured + 100 WHERE player_id = $winner";
            $loser_sql = "UPDATE player SET player_score = player_captured WHERE player_id = $loser";
            // Update the scores in the database
            $this->DbQuery($winner_sql);
            $this->DbQuery($loser_sql);
        } else {
            $sql = "UPDATE player SET player_score = player_captured WHERE player_id = $playerArr[0]";
            $this->DbQuery($sql);
            $sql = "UPDATE player SET player_score = player_captured WHERE player_id = $playerArr[1]";
            $this->DbQuery($sql);
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

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];
        
        if ($state['type'] === "activeplayer") {
            // Handle zombie turn for active player state
            // Place a lodestone in any empty place on the board
            $this->placeRandomLodestone($active_player);
            $this->gamestate->nextState("zombiePass");
            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non-blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    function isZombie($player_id)
    {
        // Check if the player is in a zombie state
        $sql = "SELECT player_zombie FROM player WHERE player_id = $player_id";
        $is_zombie = $this->getUniqueValueFromDB($sql);
        
        // Return true if the player is a zombie, otherwise return false
        return ($is_zombie == 1);
    }

    function placeRandomLodestone($player)
    {
        // Get all possible empty places on the board
        $board = $this->getBoard();
        $empty_places = $this->getEmptyPlaces();
        $placed_places = $this->getPlacedPlaces($player);
        $possiblity = true;
        $x = 0;
        $y = 0;

        $rand_flag = rand(0, 1);

        if ($rand_flag == 0) {
            $random_index = array_rand($empty_places);
            $random_place = $empty_places[$random_index];

            $x = $random_place['x'];
            $y = $random_place['y'];

            $possiblity = true;
        } else {
            $random_index = array_rand($placed_places);
            $random_place = $placed_places[$random_index];

            $random_target_index = array_rand($empty_places);
            $random_target_place = $empty_places[$random_target_index];

            $selectedX = $random_place['x'];
            $selectedY = $random_place['y'];
            $targetX = $random_target_place['x'];
            $targetY = $random_target_place['y'];

            $x = "" . $selectedX . $selectedY;
            $y = "" . $targetX . $targetY;

            if ($selectedX == $targetX) {
                $i=($selectedY>$targetY)?$targetY:$selectedY;
                $j=($selectedY<$targetY)?$targetY:$selectedY;

                for ($k=$i+1; $k < $j; $k++) { 
                    if ($board[$selectedX][$k] !== null) $possiblity = false;
                }
            } else if ($selectedY == $targetY) {
                $i=($selectedX>$targetX)?$targetX:$selectedX;
                $j=($selectedX<$targetX)?$targetX:$selectedX;

                for ($k=$i+1; $k < $j; $k++) { 
                    if ($board[$k][$selectedY] !== null) $possiblity = false;
                }
            } else {
                $possiblity = false;
            };
            
        };

        // // Place a lodestone in the random empty place
        
        if ($possiblity) $this->playDisc($x, $y, false); // Call playDisc() without advancing game state
        else $this->placeRandomLodestone($player);
    }

    function getEmptyPlaces()
    {
        $empty_places = [];
        $board = $this->getBoard();
        for ($x = 1; $x <= 9; $x++) {
            for ($y = 1; $y <= 9; $y++) {
                if ($board[$x][$y] === null) {
                    $empty_places[] = array('x' => $x, 'y' => $y);
                }
            }
        }
        return $empty_places;
    }

    function getPlacedPlaces($player)
    {
        $placed_places = [];
        $board = $this->getBoard();
        for ($x = 1; $x <= 9; $x++) {
            for ($y = 1; $y <= 9; $y++) {
                if ($board[$x][$y] == $player) {
                    $placed_places[] = array('x' => $x, 'y' => $y);
                }
            }
        }
        return $placed_places;
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
