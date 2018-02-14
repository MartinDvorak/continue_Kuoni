<?php  

echo("Hell!\n");

//####################################################
//				ZPRACOVANI PARAMETRU 
$longopts  = array(
    "help",     // help
    "stats:",    // potrebuje soubor vystupni
    "comments",        // bez hodnoty
    "loc",           // bez hodnoty
);
$index_comm = -1; // -1 neni zadan
$index_loc = -1; // -1 neni zadan

$args = getopt("", $longopts);

if(count($args) != 0)
{
if (count($args)+1 != count($argv))
	{
		exit(10);
	}	
else if (count($args) == 1)
	{
	if(array_key_exists("help", $args))
		{
		echo("TODO napsat --help\n");
		}
	else if(array_key_exists("stats", $args))
		{
		echo("TODO napsat --stats=file\n");
		}	
	else{
		exit(10);
	}
	}	
else if(array_key_exists("help", $args))
	{
		exit(10);
	}	
else{
	if(array_key_exists("stats", $args))
	{
		echo("TODO napsat --stats=file\n");	

		if(array_key_exists("loc", $args))
		{
			$index_loc = array_search("loc", array_keys($args));
		}
		if(array_key_exists("comments", $args))
		{
			$index_comm = array_search("comments", array_keys($args));
		}
	}
	else{
		exit(10);
	}	
	}
}
	
var_dump($args);
echo($index_comm);
echo($index_loc);
echo("\n");

//##########################################################
//			REGEX PART
//	https://regex101.com/
//	\x40 - @
//	\x5F - _
//	\x2D - -
//	\x23 - #
//	\x24 - $
//	\x25 - %
//	\x26 - &
//	\x2A - *
//  \x5C - \

$regex_var = '/^(GF|LF|TF)\x40([[:alpha:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)([[:alnum:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$/';
$regex_label = '/^([[:alpha:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)([[:alnum:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$/';
$regex_int_lit = '/^int\x40\x2D?\d+$/';
$regex_bool_lit = '/^bool\x40(true|false)$/';
$regex_string_lit = '/^string\x40((\x5C\d{3})|[^\x23\s\x5C])*$/';
$regex_type = '/^(int|bool|string)$/';
$regex_comments = '/\x23.*$/';
$regex_remove_blank = '/\s*$/';
//##########################################################


$count_comments = 0;
$count_instruction = 0;

// matching the first row of txt. must by .IPPCode18 => case incasitive
$line = trim(fgets(STDIN));
// remove end white character
$line = preg_replace($regex_remove_blank, "", $line);
$line = strtoupper($line);
if(strcmp($line, ".IPPCODE18") != 0)
{
	exit(21);
}

//##########################################################
//		dictionary with all instruction
$instr_set = array("MOVE","CREATEFRAME","PUSHFRAME","POPFRAME","DEFVAR","CALL",
	"RETURN","PUSHS","POPS","ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","NOT",
	"INT2CHAR","STRI2INT","READ","WRITE","CONCAT","STRLEN","GETCHAR","SETCHAR","TYPE",
	"LABEL","JUMP","JUMPIFEQ","JUMPIFNEQ","DPRINT","BREAK"
	);

//##########################################################
do{
$line = fgets(STDIN);
// remove coments
$line_v = preg_replace($regex_comments, "", $line,-1,$count);
$count_comments += $count;

// remove end white character
$line_v = preg_replace($regex_remove_blank, "", $line_v);

if(trim($line_v) != "")
{
	// seperating into words
	$words = preg_split("/[\s]+/", $line_v); 
	$count_instruction += 1;
	//var_dump($words);

	$words[0] = strtoupper($words[0]);
	$instr = -1;
	$instr = array_search($words[0], $instr_set);
	;

	//var_dump($words);
	switch($instr)
	{
		case 0: // MOVE <VAR> <SYMB>
		case 19: // INT2CHAR <VAR> <SYMB>
		case 24: // STRLEN <VAR> <SYMB>
		case 27: // TYPE <VAR> <SYMB>
			if(count($words) != 3) // right num of args
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} //match <SYMB>
			else if(!(preg_match($regex_var, $words[2])||preg_match($regex_string_lit, $words[2])||preg_match($regex_int_lit, $words[2])||preg_match($regex_bool_lit, $words[2])))
				{exit(21);}
			else{ 
				echo("TODO XML\n");
			}
			break;
		case 1: //CREATEFRAME
		case 2:	// PUSHFRAME
		case 3: // POPFRAME
		case 6: // RETURN
		case 33: // BREAK
			if(count($words) != 1)
				{exit(21);}
			else{
				echo("TODO XML\n");
			}
			break;
		case 4: // DEFVAR <VAR>
		case 8: // POPS <VAR
			if(count($words) != 2)
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} 
			else{
				echo("TODO XML\n");
			}				
			break;
		case 5: // CALL <LABEL>
		case 28: // LABEL <LABEL>
		case 29: // JUMP <LABEL>
			if(count($words) != 2)
				{exit(21);}
			else if(!preg_match($regex_label, $words[1])) // match <VAR>
				{exit(21);} 
			else{
				echo("TODO XML\n");
			}			
			break;
		case 7: // PUSHS <SYMB>
		case 22: // WRITE <SYMB>
		case 32: // DPRINT <SYMB>
			if(count($words) != 2)
				{exit(21);}
			else if(!(preg_match($regex_var, $words[1])||preg_match($regex_string_lit, $words[1])||preg_match($regex_int_lit, $words[1])||preg_match($regex_bool_lit, $words[1])))
				{exit(21);} // match <SYMB>
			else{
				echo("TODO XML\n");
			}		
			break;
		case 9: // ADD <VAR> <SYMB> <SYMB> 
		case 10: // SUB <VAR> <SYMB> <SYMB>
		case 11: // MUL <VAR> <SYMB> <SYMB>
		case 12: // IDIV <VAR> <SYMB> <SYMB> 
		case 13: // LT <VAR> <SYMB> <SYMB>
		case 14: // GT <VAR> <SYMB> <SYMB>
		case 15: // EQ <VAR> <SYMB> <SYMB> 
		case 16: // AND <VAR> <SYMB> <SYMB>
		case 17: // OR <VAR> <SYMB> <SYMB>
		case 18: // NOT <VAR> <SYMB> <SYMB> 
		case 20: // STRI2INT <VAR> <SYMB> <SYMB>
		case 23: // CONCAT <VAR> <SYMB> <SYMB>
		case 25: // GETCHAR <VAR> <SYMB> <SYMB>
		case 26: // SETCHAR <VAR> <SYMB> <SYMB>
			if(count($words) != 4) // right num of args
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} //match <SYMB>
			else if(!(preg_match($regex_var, $words[2])||preg_match($regex_string_lit, $words[2])||preg_match($regex_int_lit, $words[2])||preg_match($regex_bool_lit, $words[2])))
				{exit(21);}
			else if(!(preg_match($regex_var, $words[3])||preg_match($regex_string_lit, $words[3])||preg_match($regex_int_lit, $words[3])||preg_match($regex_bool_lit, $words[3])))
				{exit(21);}			
			else{ 
				echo("TODO XML\n");
			}			
			break;
		case 21: //READ <VAR> <TYPE>
			if(count($words) != 3) // right num of args
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} //match <TYPE>
			else if(!preg_match($regex_type, $words[2]))
				{exit(21);}
			else{ 
				echo("TODO XML\n");
			}			
			break;
		case 30: // JUMPIFEQ <LABEL> <SYMB> <SYMB>
		case 31: // JUMPIFNEQ <LABEL> <SYMB> <SYMB>
			if(count($words) != 4) // right num of args
				{exit(21);}
			else if(!preg_match($regex_label, $words[1])) // match <VAR>
				{exit(21);} //match <SYMB>
			else if(!(preg_match($regex_var, $words[2])||preg_match($regex_string_lit, $words[2])||preg_match($regex_int_lit, $words[2])||preg_match($regex_bool_lit, $words[2])))
				{exit(21);}
			else if(!(preg_match($regex_var, $words[3])||preg_match($regex_string_lit, $words[3])||preg_match($regex_int_lit, $words[3])||preg_match($regex_bool_lit, $words[3])))
				{exit(21);}			
			else{ 
				echo("TODO XML\n");
			}					
			break;
															
		default:
		exit(21);
	}
}


//var_dump($line);
}while($line);

//var_dump($count_comments);
//var_dump($count_instruction);
//var_dump($line);
//var_dump($instr_set);
echo("YEAH!\n");

?>
