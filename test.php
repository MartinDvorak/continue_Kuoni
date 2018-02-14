<?php  

echo("Hell!\n");

$regex_string_lit = '/^string\x40((\x5C\d{3})|[^\x23\s\x5C])*$/';

$word = 'string@_#m'; 
if(!preg_match($regex_string_lit, $word)) // match <VAR>
	{
		echo("DONT MATCH\n");
	}
else{
	echo("YEAH!\n");
	}
?>
