<?php
require_once('constants.inc.php');
require_once('Stack.class.php');

/***
 * USED GRAMMAR:
 * exp1 -> exp1 |=  exp2 | exp7 | value | ;
 * exp2 -> exp2 <=> exp3 | exp7 | value | ;
 * exp3 -> exp3  => exp4 | exp7 | value | ;
 * exp4 -> exp4  v  exp5 | exp7 | value | ;
 * exp5 -> exp5  ^  exp6 | exp7 | value | ;
 * exp6 -> exp6  /  exp7 | exp7 | value | ;
 * exp7 -> ( exp1 ) | ( exp2 ) | ( exp3 )
 * 			| ( exp4 ) | ( exp5 ) | ( exp6 ) | ( exp7 ) | ;
 */

/**
 * The Lexer class
 * @author Chegham wassim
 * @access public
 * @copyright GPL
 */
class Lexer
{
  /**
   * This variable contains the current read character,
   * The current character is set by read_char().
   * @access Protected
   * @var String
   */
  protected $current_char;

  /**
   * This variable contains the index of the current read character.
   * @var Integer
   */
  private $current_index;

  /**
   * The infix forumula that needs to be processed.
   * @access Public
   * @var String
   */
  private $expr;

  /**
   * The postfix formula.
   * @var Array
   * @access Private
   */
  private $postfix;

  /**
   * The stack of characters.
   * @var Array
   * @access Private
   */
  private $S;

  /**
   * The debugging flag.
   * @var Integer
   * @access Private
   */
  private $debug_enable = 1;

  /**
   * The precedence of the used operators.
   * @var Array
   * @access Protected
   * @see http://en.wikipedia.org/wiki/Logical_connective#Order_of_precedence
   */
  protected $precedence = array(
    SEMICOLON         => -1,
    OPEN_PARENTHESIS  => 0,
    CLOSE_PARENTHESIS => 0,
  
    SYMB_TAUTO        => 1,
    SYMB_BICOND       => 2,
    SYMB_IMPLY        => 3,
    SYMB_OR           => 4,
    SYMB_AND          => 5,
    SYMB_NOT          => 6
  );

  /**
   * The Lexer constructer
   * @param String $expr
   * @access Public
   * @return void
   */
  public function __construct( $expr )
  {
    $this->current_char = "";
    $this->current_index = -1;
    $this->expr = $this->clean_expr($expr);
    $this->postfix = array();
    $this->expr_len = strlen($this->expr);
    $this->S = new Stack();
    $this->fix_eoe();
  }


  /**
   * Read the next character, this methode sets the $current_char's value
   * and increments the $current_index's value.
   * @access Public
   * @return void
   */
  public function read_next()
  {
    $len = $this->expr_len;

    if ( $len-1 > 0 && $this->current_index <= $len-2)
    {
      $this->current_char = $this->expr[ ++$this->current_index ];
      while ( $this->current_char == WHITESPACE )
      {
        $this->current_char = $this->expr[ ++$this->current_index ];
      }

    }
    else { $this->current_char = null; }

  }


  /**
   * Gets the next character, this methode DOES NOT set the $current_char's value,
   * and DOES NOT increment the $current_index's value.
   * @access Public
   * @return The next available character.
   * @return NULL if no more characters left.
   */
  public function get_next()
  {

    $len = $this->expr_len;

    if ( $len-1 > 0 && $this->current_index <= $len-2)
    {
      $i=1;
      $char = $this->expr[ $this->current_index+$i ];
      while ( $char == WHITESPACE )
      {
        $char = $this->expr[ $this->current_index+$i ];
        $i++;
      }

      return $char;

    }
    else { return null; }
  }

  
  /**
   * Gets the current stack content.
   * @access Public
   * @return String, the stack content.
   */
  public function get_stack()
  {
    return $this->S->to_string();
  }

  /**
   * Gets the postfix expression
   * @access Public
   * @return String, The postfix expression
   */
  public function get_postfix()
  {
    $arr = array(
        SYMB_NOT     => SYMB_HTML_NOT,
        SYMB_AND     => SYMB_HTML_AND,
        SYMB_OR      => SYMB_HTML_OR,
        SYMB_IMPLY   => SYMB_HTML_IMPLY,
        SYMB_TAUTO   => SYMB_HTML_TAUTO,
        SYMB_BICOND  => SYMB_HTML_BICOND    
    );
    return strtr(implode('', $this->postfix), $arr);
  }

  /**
   * Checks weither the expression is correct or not.
   * @return void
   */
  public function check()
  {
     
    $nb_par = 0;
    $this->read_next();
    $current_char = $this->current_char;

    if ( $current_char != SYMB_NOT && ( $this->is_operator( $current_char ) || $current_char == CLOSE_PARENTHESIS) )
    {
      $this->error(__LINE__." : expressions must not begin with an operator! Found '".$this->get_html_symb($current_char)."'");
    }

    $this->debug(__LINE__." : push '".SEMICOLON."'");
    $this->S->push(SEMICOLON);
     
    while( ! $this->is_eoe() )
    {

      switch ( $this->current_char )
      {

        case WHITESPACE:
          // skip whitespaces
          break;
           
          // (
        case OPEN_PARENTHESIS:

          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS || $char == SYMB_NOT )
          {
            $nb_par++;

            $this->debug(__LINE__." : open parenthesis : code ".$nb_par." ");

            // EXECUTE CODE HERE
            $this->to_postfix( OPEN_PARENTHESIS );
            $this->read_next();
             
          }
          else {
            $this->error(__LINE__." : an expression or operand is expected after '('. Found '".$this->get_html_symb($this->current_char)."'");
          }
          break;

          // )
        case CLOSE_PARENTHESIS:

          $this->debug(__LINE__." : close parenthesis : code ".$nb_par." ");

          $char = $this->get_next();
          if ( $char != null && !$this->is_operator( $char ) && $char != CLOSE_PARENTHESIS )
          {
            $this->error(__LINE__." : an operator or ')' is expected after ')'. Found '".$this->get_html_symb($this->current_char)."'");
          }
          else if ( $char != SEMICOLON )
          {
            $this->error(__LINE__." : an end of expression is expected. Found '".$this->get_html_symb($this->current_char)."'");            
          }
          else {
             
            if ( $nb_par <= 0 )
            {
              $this->error(__LINE__." : Please check your parenthasus : code ".$nb_par." ");
            }
             
            else
            {
              $nb_par--;
              // EXECUTE CODE HERE
              $this->to_postfix( CLOSE_PARENTHESIS );
              $this->read_next();
            }
          }
          break;

          // NOT
        case SYMB_NOT:
          
          $this->debug(SYMB_NOT);
          
          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS )
          {
            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_NOT );
            $this->read_next();

          }
          else
          {
            $this->error(__LINE__." : an expression or operand is expected. Found '".$this->get_html_symb($char)."'");
          }

          break;

          // IMPLY
        case SYMB_IMPLY:
          
          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == SYMB_NOT || $char == OPEN_PARENTHESIS )
          {

            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_IMPLY );
            $this->read_next();

          }
          else
          {
            $this->error(__LINE__." : an expression or operand is expected after '".SYMB_HTML_IMPLY."'. Found '".$this->get_html_symb($char)."'");
          }
          break;

          // TAUTO
        case SYMB_TAUTO:
            
          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS )
          {

            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_TAUTO );
            $this->read_next();

          }
          else {
            $this->error(__LINE__." : an expression or operand is expected after '".SYMB_HTML_TAUTO."'. Found '".$this->get_html_symb($char)."'");
          }
          break;

          // BICOND
        case SYMB_BICOND:
          
          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS )
          {

            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_BICOND );
            $this->read_next();

          }
          else
          {
            $this->error(__LINE__." : an expression or operand is expected after '".SYMB_BINCOND."'. Found '".$this->get_html_symb($char)."'");
          }
          break;

          // AND
        case SYMB_AND:

          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS || $char == SYMB_NOT)
          {
             
            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_AND );
            $this->read_next();
             
          }
          else
          {
            $this->error(__LINE__." : an expression or operand is expected after '".SYMB_AND."'. Found '".$this->get_html_symb($char)."'");
          }
          break;

          // OR
        case SYMB_OR:

          $char = $this->get_next();
          if ( $this->is_value( $char ) || $char == OPEN_PARENTHESIS )
          {
             
            // EXECUTE CODE HERE
            $this->to_postfix( SYMB_OR );
            $this->read_next();

          }
          else
          {
            $this->error(__LINE__." : an expression or operand is expected after '".SYMB_OR."'. Found '".$this->get_html_symb($char)."'");
          }
          break;

        case SEMICOLON:
          $this->to_postfix( SEMICOLON );
          $this->read_next();

          break;

          // values
        default:

          $current_char = $this->current_char;
          if ( $this->is_value( $current_char ) )
          {

            $next_char = $this->get_next();

            if ( $this->is_operator( $next_char ) || $next_char == CLOSE_PARENTHESIS )
            {

              // EXECUTE CODE HERE
              $this->to_postfix( $current_char );
              $this->read_next();

            }
            else if ( $next_char == null)
            {
              $this->error(__LINE__." : '".SEMICOLON."' is expected at the end of expression");
            }
            else
            {
              $this->error(__LINE__." : an operator is missing between '".$current_char."' and '".$next_char."' in expression '".$current_char.$next_char."'");
            }
          }
          else
          {
            $this->error(__LINE__." : '".SEMICOLON."' is expected at the end of expression");
          }
      }

    }
     
    if ( $nb_par != 0 )
    {
      $txt = (abs($nb_par)>1)?"are":"is";

      if ( $nb_par > 0 ) $this->error(__LINE__." : ".$nb_par." ')' ".$txt." missing ");
      else if ( $nb_par < 0 ) $this->error(__LINE__." : ".abs($nb_par)." '(' ".$txt." missing ");

    }
     
  }


  
  
  /**
   * Split the expression in order to remove the unused characters.
   * @access Private
   * @return void
   */
  private function fix_eoe()
  {
    if ( !preg_match('/\;$/', $this->expr) )
    {
      $this->expr .= SEMICOLON;
    }
      
    $tmp = explode(SEMICOLON, $this->expr);  
    $this->expr = $tmp[0].SEMICOLON;
  }

  /**
   * Prefix to Postfix conversion
   * @param String $exp the current read character from the infix expression.
   * @access Private
   * @return void
   */
  private function to_postfix( $exp )
  {
    /* The conversion process follows this algorithm:
     * 1/ Variables (in this case letters) are copied to the output
     * 2/ Left parentheses are always pushed onto the stack
     * 3/ When a right parenthesis is encountered, the symbol at the top of
     *    the stack is popped off the stack and copied to the output.
     *    Repeat until the symbol at the top of the stack is a left parenthesis.
     *    When that occurs, both parentheses are discarded.
     * 4/ Otherwise, if the symbol being scanned has a higher precedence than
     *    the symbol at the top of the stack, the symbol being scanned is pushed
     *    onto the stack and the scan pointer is advanced.
     * 5/ If the precedence of the symbol being scanned is lower than or equal
     *    to the precedence of the symbol at the top of the stack, one element
     *    of the stack is popped to the output; the scan pointer is not advanced.
     *    Instead, the symbol being scanned will be compared with
     *    the new top element on the stack.
     * 6/ When the terminating symbol is reached on the input scan,
     *    the stack is popped to the output until the terminating symbol is also
     *    reached on the stack. Then the algorithm terminates.
     */

    switch( $exp )
    {
      // left parenthesis
      case OPEN_PARENTHESIS:
        $this->S->push(OPEN_PARENTHESIS);
        $this->debug(__LINE__." : push '".$exp."'");
        break;

        // right parenthesis
      case  CLOSE_PARENTHESIS:

        while
        ( ($top_stack = $this->S->pop()) != OPEN_PARENTHESIS )
        {
          if ($top_stack == SEMICOLON) break;

          $this->postfix[] = $top_stack;
          $this->debug(__LINE__." : pop '".$top_stack."'");
        }

        break;

        // ;
      case SEMICOLON:

        while
        ( ($top_stack = $this->S->pop()) != SEMICOLON
        && $top_stack != null
        )
        {
          $this->postfix[] = $top_stack;
          $this->debug(__LINE__." : pop '".$top_stack."'");
        }

        break;

        // values and operators
      default:

        if ( $this->is_value($exp) )
        {
          $this->postfix[] = $exp;
          $this->debug(__LINE__." : print '".$exp."'");
        }
        else if ( $this->is_operator($exp) )
        {
          $top_stack = $this->S->get_top();

          if ( $this->check_precedence( $exp, $top_stack ) == 1 )
          {
            $this->debug(__LINE__." : push '".$exp."'");
            $this->S->push($exp);
          }
          else {
            $top_stack = $this->S->pop();
            $this->postfix[] = $top_stack;
            $this->debug(__LINE__." : pop '".$top_stack."'");
            $this->current_index--; // we dont read the next one !!
          }
        }
    }

  }
  
  
  /*****************************************************************************/
  /************************** Privates Methodes ********************************/
  /*****************************************************************************/
  
  private function clean_expr($expr)
  {
    //print($expr);
    $arr = array(
        SYMB_HTML_NOT     => SYMB_NOT,
        SYMB_HTML_AND     => SYMB_AND,
        SYMB_HTML_OR      => SYMB_OR,
        SYMB_HTML_IMPLY   => SYMB_IMPLY,
        SYMB_HTML_TAUTO   => SYMB_TAUTO,
        SYMB_HTML_BICOND  => SYMB_BICOND    
    );
    
//    print(strtr($expr, $arr));
//    exit();
//    
    return strtr($expr, $arr);
  }
  
  /**
   * Tests if the end of expression is reached.
   * @return Boolean, True if the end of expression is reached.
   * @access Private
   */
  private function is_eoe()
  {
    return $this->current_char == null || $this->current_index > $this->expr_len;
  }
  
  /**
   * TODO associate each symbole with its precedence code, instead of number:1, 2, 3 ... only!!
   * @param unknown_type $code
   * @return unknown_type
   */
  private function get_html_symb($code)
  {
    switch($code)
    {
      case 1:
          return SYMB_HTML_NOT;
        break;
      case 2:
          return SYMB_HTML_AND;
        break;      
      case 3:
          return SYMB_HTML_OR;
        break;      
      case 4:
          return SYMB_HTML_IMPLY;
        break;      
      case 5:
          return SYMB_HTML_TAUTO;
        break;
      case 6:
          return SYMB_HTML_BICOND;
        break;
      default:
         return $code;    
    }
  }
  
  /**
   * Checks if the given character is a parenthesis
   * @param String $txt, the current read character
   * @access Private
   * @return Boolean, True if $txt is a parenthesis
   */
  private function is_parenthasus( $txt )
  {
    return OPEN_PARENTHESIS  == $txt || CLOSE_PARENTHESIS  == $txt;
  }


  /**
   * Checks if the given character is a value (a variable)
   * @param String $txt, the current read character
   * @access Private
   * @return Boolean, True if $txt is a value
   */
  private function is_value( $txt )
  {
    return preg_match(ALPHABET_PATTERN, $txt);
  }


  /**
   * Checks if the given character is an operator.
   * @param String $txt, the current read character.
   * @access Private
   * @return Boolean, True if $txt is an operator
   */
  private function is_operator( $txt )
  {
    return SYMB_NOT == $txt
    || SYMB_AND == $txt
    || SYMB_OR == $txt
    || SYMB_BICOND == $txt
    || SYMB_TAUTO == $txt
    || SYMB_IMPLY == $txt
    || SEMICOLON == $txt;
  }


  /**
   * Check the precedence between operations
   * @param String $op, the current read character
   * @param String $stack_op, the top stack character
   * @access Private
   * @return 1 if $op has a higher precedence than $stack_op,
   * @return 2 if $stack_op has a higher precedence than $op,
   * @return 0 if $op and $stack_op have the same precedence
   */
  private function check_precedence($op, $stack_op)
  {
    if( $this->debug_enable == 1 ) print("<b>Precedence of '".$op."' and '".$stack_op."'</b>\n");

    if ( !isset($this->precedence[ $op ]) ) return 1;
    else if ( !isset($this->precedence[ $stack_op ]) ) return 2;
    else if ( isset($this->precedence[ $op ]) && isset($this->precedence[ $stack_op ]) )
    {

      if ( $this->precedence[ $op ] > $this->precedence[ $stack_op ] )
      return 1;
      else if ( $this->precedence[ $op ] < $this->precedence[ $stack_op ] )
      return 2;
      else
      return 0;

    }

  }


  /**
   * Prints out some usefull infos.
   * @param String $txt A message to be printed.
   * @access Private
   * @return void
   */
  private function debug($txt)
  {
    if ( $this->debug_enable == 1 )
    {
      
      echo "<span style='color:blue;font-weight:bold;'>Lex : line ".$txt.".</span>\n";
      echo "* index = ".$this->current_index."\n";
      echo "* char  = ".$this->current_char."\n";
      echo "* stack = ".$this->S->to_string()."\n";
      echo "* infix = ".$this->get_postfix()."\n";
    }
  }


  /**
   * Prints out an error and exit the program.
   * @access Private
   * @param String $error The text error
   * @return void
   */
  private function error($error)
  {
    $txt = "<span style='color:red;font-weight:bold;'>";
    $txt .= "Lex : line ".$error.".\n";
    $txt .= "* index = ".$this->current_index."\n";
    $txt .= "* char  = ".$this->current_char."\n";
    $txt .= "* stack = ".$this->S->to_string()."\n";
    $txt .= "* infix = ".$this->get_postfix()."</span>\n";
    exit( $txt );
  }


}

// End of Lexer.class.php

?>