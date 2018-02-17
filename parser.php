<?php  

//####################################################
//			 	PARAMS 
$longopts  = array(
    "help",     // help
    "stats:",    // must have one parameter
    "comments",        // without parameter
    "loc",           // without parameter
);
$index_comm = -1; // -1 not set
$index_loc = -1; // -1 not set

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
		echo("--help\n");
		echo("Skript typu filtr (parse.php v jazyce PHP 5.6) načte ze standardního vstupu zdrojový kód v IPPcode18, zkontroluje lexikální a syntaktickou správnost kódu a vypíše na standardní
výstup XML reprezentaci programu dle specifikace\n");
		echo("parametry : --help  => vypíše tuto nápovědu\n");
		echo("            --stats=\"file\" => pozaduje vstupni soubor, do kterého se budou zapisovat sledované paramtery.\n");
		echo("            --comments => Zapne parametr počet komentarů. Nutné s --stats=\"file\"\n");
		echo("            --loc => Zapne parametr počet instrukcí. Nutné s --stats=\"file\"\n");
		exit(0);
		}
	else if(array_key_exists("stats", $args))
		{
			if(($out = fopen($args["stats"], "w")) == false)
			{exit(10);}
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
		if(($out = fopen($args["stats"], "w")) == false)
		{	
			exit(10);
		}
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
//  \x2B - +
//  \x5C - \

$regex_var = '/^(GF|LF|TF)\x40([[:alpha:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)([[:alnum:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$/';
$regex_label = '/^([[:alpha:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)([[:alnum:]]|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$/';
$regex_int_lit = '/^int\x40(\x2D|\x2B)?\d+$/';
$regex_bool_lit = '/^bool\x40(true|false)$/';
$regex_string_lit = '/^string\x40((\x5C\d{3})|[^\x23\s\x5C])*$/';
$regex_type = '/^(int|bool|string)$/';
$regex_comments = '/\x23.*$/';
$regex_remove_blank = '/\s*$/';
$regex_remove_first_blank = '/^\s*/';
//##########################################################


$count_comments = 0;
$count_instruction = 0;

$type = array("var","int","bool","string");

// matching the first row of txt. must by .IPPCode18 => case incasitive
$line = trim(fgets(STDIN));
// remove end white character
$line = preg_replace($regex_remove_blank, "", $line);
$line = strtoupper($line);
if(strcmp($line, ".IPPCODE18") != 0)
{
	exit(21);
}
echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
echo("<program language=\"IPPcode18\">\n");

//##########################################################
//		dictionary with all instruction
$instr_set = array("MOVE","CREATEFRAME","PUSHFRAME","POPFRAME","DEFVAR","CALL",
	"RETURN","PUSHS","POPS","ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","NOT",
	"INT2CHAR","STRI2INT","READ","WRITE","CONCAT","STRLEN","GETCHAR","SETCHAR","TYPE",
	"LABEL","JUMP","JUMPIFEQ","JUMPIFNEQ","DPRINT","BREAK"
	);

//##########################################################
//				FUNCTION 

function xml_special_key($string)
{
	$string = preg_replace('/&/', "&amp;", $string);
	$string = preg_replace('/</', "&lt;", $string);
	$string = preg_replace('/>/', "&gt;", $string);
	$string = preg_replace('/\'/', "&apos;", $string);
	$string = preg_replace('/"/', "&quot;", $string);

	return $string;
}

function get_symb_check($value)
{
	global $regex_var, $regex_string_lit, $regex_int_lit, $regex_bool_lit;

	if(preg_match($regex_var, $value))
		{
			return array("var" => $value);
		}
	else if(preg_match($regex_string_lit, $value))
		{
			return array("string" => substr(xml_special_key($value),7));
		}	
	else if(preg_match($regex_int_lit, $value))
		{
			return array("int" => substr($value,4));
		}	
	else if(preg_match($regex_bool_lit, $value))
		{
			return array("bool" => substr($value,5));
		}	
	else{	
		exit(21);
	}	
 
}

//##########################################################

do{
$line = fgets(STDIN);
// remove coments
$line_v = preg_replace($regex_comments, "", $line,-1,$count);
$count_comments += $count;

// remove end white character
$line_v = preg_replace($regex_remove_blank, "", $line_v);
$line_v = preg_replace($regex_remove_first_blank, "", $line_v);

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
	//var_dump($line);
	switch($instr)
	{
		case 0: // MOVE <VAR> <SYMB>
		case 19: // INT2CHAR <VAR> <SYMB>
		case 24: // STRLEN <VAR> <SYMB>
		case 27: // TYPE <VAR> <SYMB>
			if(count($words) != 3) // right num of args
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} 
			else{ 
				$sym1 = get_symb_check($words[2]); //match <SYMB>
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"var\">$words[1]</arg1>\n");
				echo("  <arg2 type=\"".array_search(reset($sym1), $sym1)."\">".reset($sym1)."</arg2>\n");
				echo(" </instruction>\n");
			}
			break;
		case 1: // CREATEFRAME
		case 2:	// PUSHFRAME
		case 3: // POPFRAME
		case 6: // RETURN
		case 33: // BREAK
			if(count($words) != 1)
				{exit(21);}
			else{
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\"/>\n");
			}
			break;
		case 4: // DEFVAR <VAR>
		case 8: // POPS <VAR
			if(count($words) != 2)
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} 
			else{
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"var\">$words[1]</arg1>\n");
				echo(" </instruction>\n");
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
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"label\">$words[1]</arg1>\n");
				echo(" </instruction>\n");
			}			
			break;
		case 7: // PUSHS <SYMB>
		case 22: // WRITE <SYMB>
		case 32: // DPRINT <SYMB>
			if(count($words) != 2)
				{exit(21);}
			else{
				$sym1 = get_symb_check($words[1]); //match <SYMB>
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg2 type=\"".array_search(reset($sym1), $sym1)."\">".reset($sym1)."</arg2>\n");
				echo(" </instruction>\n");
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
			else{ 
				$sym1 = get_symb_check($words[2]); //match <SYMB>
				$sym2 = get_symb_check($words[3]); //match <SYMB>
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"var\">$words[1]</arg1>\n");
				echo("  <arg2 type=\"".array_search(reset($sym1), $sym1)."\">".reset($sym1)."</arg2>\n");
				echo("  <arg3 type=\"".array_search(reset($sym2), $sym2)."\">".reset($sym2)."</arg3>\n");
				echo(" </instruction>\n");
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
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"var\">$words[1]</arg1>\n");
				echo("  <arg2 type=\"type\">$words[2]</arg2>\n");
				echo(" </instruction>\n");
			}			
			break;
		case 30: // JUMPIFEQ <LABEL> <SYMB> <SYMB>
		case 31: // JUMPIFNEQ <LABEL> <SYMB> <SYMB>
			if(count($words) != 4) // right num of args
				{exit(21);}
			else if(!preg_match($regex_label, $words[1])) // match <VAR>
				{exit(21);} //match <SYMB>		
			else{ 
				$sym1 = get_symb_check($words[2]); //match <SYMB>
				$sym2 = get_symb_check($words[3]); //match <SYMB>
				echo(" <instruction order=\"$count_instruction\" opcode=\"$words[0]\">\n");
				echo("  <arg1 type=\"label\">$words[1]</arg1>\n");
				echo("  <arg2 type=\"".array_search(reset($sym1), $sym1)."\">".reset($sym1)."</arg2>\n");
				echo("  <arg3 type=\"".array_search(reset($sym2), $sym2)."\">".reset($sym2)."</arg3>\n");
				echo(" </instruction>\n");
			}					
			break;
															
		default:
		exit(21);
	}
}

}while($line);

//var_dump($count_comments);
//var_dump($count_instruction);
if(array_key_exists("stats", $args))
{
	if(($count_comments != -1)&&($count_instruction != -1))
	{
		if($count_instruction > $count_comments)
		{
			fwrite($out, "$count_instruction\n$count_comments\n");
		}
		else{
			fwrite($out, "$count_comments\n$count_instruction\n");
		}
	}
	else if($count_comments != -1)
	{
		fwrite($out, "$count_comments\n");
	}
	else if($count_instruction != -1)
	{
		fwrite($out, "$count_instruction\n");	
	}

	fclose($out);
}
echo("</program>\n");
exit(0);
?>
