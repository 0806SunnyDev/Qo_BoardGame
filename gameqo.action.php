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
  	
  	public function playDisc()
{
    $this->setAjaxMode();
    $x = (int)$this->getArg( "x", AT_posint, true );
    $y = (int)$this->getArg( "y", AT_posint, true );
    $result = $this->game->playDisc( $x, $y );
    $this->ajaxResponse( );
}

  }
  

