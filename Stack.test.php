<?php

require_once('Stack.class.php');

$s = new Stack();
?>
<?php
echo "<pre>";

print_r($s);

$s->push('a');
print_r($s);

$s->push('b');
print_r($s);

$s->push('c');
print_r($s);

$s->pop();
print_r($s);


echo "</pre>";
?>