<?php
/**
 * TODO precedence code instead of number only
 */
// Lexer
define("SYMB_NOT",          1/*'¬'*/ );
define("SYMB_HTML_NOT",     "&#172;"  );
define("SYMB_AND",          2/*'∧'*/ );
define("SYMB_HTML_AND",     "&#8743;"  );
define("SYMB_OR",           3/*'∨'*/ );
define("SYMB_HTML_OR",      "&#8744;"   );
define("SYMB_IMPLY",        4/*'⇒'*/ );
define("SYMB_HTML_IMPLY",   "&#8658;");
define("SYMB_TAUTO",        5/*'⊧'*/ );
define("SYMB_HTML_TAUTO",   "&#8871;");
define("SYMB_BICOND",       6/*'⇔'*/ );
define("SYMB_HTML_BICOND",  "&#8660;");
define("OPEN_PARENTHESIS",  '('      );
define("CLOSE_PARENTHESIS", ')'      );
define("SEMICOLON",         ';'      );
define("WHITESPACE",        ' '      );

// Alphabet
define("ALPHABET_PATTERN", '/[a-zA-Z]/');

?>