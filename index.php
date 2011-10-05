<?php
error_reporting(E_ALL);
require_once('constants.inc.php');
require_once('Lexer.class.php');
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>

<body>
<script>

function write_op(el)
{
	var myField = document.getElementById('input');
	var myValue = el.value;
	
	//IE support
	if (document.selection) 
	{
    	myField.focus();
    	sel = document.selection.createRange();
    	sel.text = myValue;
	}

	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') 
	{
    	var startPos = myField.selectionStart;
    	var endPos = myField.selectionEnd;
    	myField.value = myField.value.substring(0, startPos)
    	+ myValue
    	+ myField.value.substring(endPos, myField.value.length);
	} 
	else 
	{
		myField.value += myValue;
	}
	myField.focus();
}
</script>
<form style="width: 100%" action="" method="post"><label for="formule"
	style="float: left; position: relative;">Formula</label> <input
	id="input"
	style="padding: 2px; color: #80bde9; font-size: 30px; font-weight: bold; width: 100%; border: 1px solid #aaa;"
	name="formule" type="text"
	value="<?php  echo ( isset($_POST['formule']) )?$_POST['formule']:'' ?>" />
  
  <input type="button" id="symb_and" value="<?php echo SYMB_HTML_NOT; ?>" onclick="write_op(this)" /> 
  <input type="button" id="symb_or" value="<?php echo SYMB_HTML_OR; ?>" onclick="write_op(this)" /> 
  <input type="button" id="symb_not" value="<?php echo SYMB_HTML_AND; ?>" onclick="write_op(this)" /> 
  <input type="button" id="symb_imply" value="<?php echo SYMB_HTML_IMPLY; ?>" onclick="write_op(this)" />
  <input type="button" id="symb_bicond" value="<?php echo SYMB_HTML_BICOND; ?>" onclick="write_op(this)" />
  <input type="button" id="symb_tauto"	value="<?php echo SYMB_HTML_TAUTO; ?>" onclick="write_op(this)" />

</form>
<hr />
<pre>
<?php
/*
 for($i=100; $i<=9000; $i++)
 {
 echo '<b>&#'.$i.";</b>&nbsp;<b>".$i."</b>\n";

 }
 */
?>

<?php

extract($_POST);

if ( isset($formule) && !empty($formule) )
{
  $APP = new Lexer($formule);
  $APP->check();
  echo "<span style='font-size:30px;color:green;font-weight:bold;'>Infix formula: ".$APP->get_postfix()."</span>\n";
}
?>
</pre>


</body>
</html>
