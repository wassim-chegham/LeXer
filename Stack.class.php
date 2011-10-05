<?php

class stack
{
 private $stack;
 private $si;

 public function __construct()
 {
  $this->stack = array();
  $this->si = -1;
 }

 public function push($a)
 {
  $this->stack[ $this->si++ ] = $a;
 }

 // pop an item from the stack
 public function pop() {
  if( $this->si != -1 )
  {
   $elt = $this->stack[$this->si-1];
   unset($this->stack[$this->si]);
   $this->si--;
   return $elt;
  }
  else {
   return null;
  }
 }

 public function reset() {

  for($i=0; $i<=$this->si; $i++)
  $this->stack[$i] = null;

  $this->si = -1;
 }

 public function is_empty()
 {
  return $this->si <= -1;
 }

 public function get_top()
 {
  return $this->stack[ $this->si-1 ];
 }

 public function to_string()
 {
  $s = $this->stack;
  return '['.implode(" ", $s)."]";
 }

}
?>