<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GameQo implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * gameqo.action.php
 *
 * GameQo main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/gameqo/gameqo/myAction.html", ...)
 *
 */
  
  
  class action_gameqo extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( $this->isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = $this->getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "gameqo_gameqo";
            $this->trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there


    /*
    
    Example:
  	
    public function myAction()
    {
        $this->setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = $this->getArg( "myArgument1", AT_posint, true );
        $arg2 = $this->getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        $this->ajaxResponse( );
    }
    
    */

  }
  

