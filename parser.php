<?php  

//####################################################
//			 	ARGS
$longopts  = array(
    "help",     // help
    "stats:",   // must have one parameter
    "comments",	// without parameter
    "loc",     	// without parameter
);
$index_comm = -1; // -1 not set
$index_loc = -1; // -1 not set

$args = getopt("", $longopts);

if(count($argv) != 1)
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
			{exit(12);}
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
			exit(12);
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
$count_inst = 0;

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

$XML = new DomDocument("1.0","UTF-8");
$prog = $XML->createElement('program');
$prog->setAttribute('language','IPPcode18');

//##########################################################
//		dictionary with all instruction
$instr_set = array("MOVE","CREATEFRAME","PUSHFRAME","POPFRAME","DEFVAR","CALL",
	"exit","PUSHS","POPS","ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","NOT",
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
	$count_inst += 1;
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
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','var');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});

				${"arg2".$count_inst} = $XML->createElement("arg2",reset($sym1));
				${"arg2".$count_inst}->setAttribute('type',array_search(reset($sym1), $sym1));	
				${"instr".$count_inst}->appendChild(${"arg2".$count_inst});
			}
			break;
		case 1: // CREATEFRAME
		case 2:	// PUSHFRAME
		case 3: // POPFRAME
		case 6: // exit
		case 33: // BREAK
			if(count($words) != 1)
				{exit(21);}
			else{
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});
			}
			break;
		case 4: // DEFVAR <VAR>
		case 8: // POPS <VAR
			if(count($words) != 2)
				{exit(21);}
			else if(!preg_match($regex_var, $words[1])) // match <VAR>
				{exit(21);} 
			else{
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','var');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});
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
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','label');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});
			}			
			break;
		case 7: // PUSHS <SYMB>
		case 22: // WRITE <SYMB>
		case 32: // DPRINT <SYMB>
			if(count($words) != 2)
				{exit(21);}
			else{
				$sym1 = get_symb_check($words[1]); //match <SYMB>
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",reset($sym1));
				${"arg1".$count_inst}->setAttribute('type',array_search(reset($sym1), $sym1));	
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});
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
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','var');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});

				${"arg2".$count_inst} = $XML->createElement("arg2",reset($sym1));
				${"arg2".$count_inst}->setAttribute('type',array_search(reset($sym1), $sym1));	
				${"instr".$count_inst}->appendChild(${"arg2".$count_inst});

				${"arg3".$count_inst} = $XML->createElement("arg3",reset($sym2));
				${"arg3".$count_inst}->setAttribute('type',array_search(reset($sym2), $sym2));	
				${"instr".$count_inst}->appendChild(${"arg3".$count_inst});
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
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','var');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});

				${"arg2".$count_inst} = $XML->createElement("arg2",$words[2]);
				${"arg2".$count_inst}->setAttribute('type','type');
				${"instr".$count_inst}->appendChild(${"arg2".$count_inst});
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
		
				${"instr".$count_inst} = $XML->createElement('instruction');
				${"instr".$count_inst}->setAttribute('order',$count_inst);
				${"instr".$count_inst}->setAttribute('opcode',$words[0]);
				$prog->appendChild(${"instr".$count_inst});

				${"arg1".$count_inst} = $XML->createElement("arg1",$words[1]);
				${"arg1".$count_inst}->setAttribute('type','label');
				${"instr".$count_inst}->appendChild(${"arg1".$count_inst});

				${"arg2".$count_inst} = $XML->createElement("arg2",reset($sym1));
				${"arg2".$count_inst}->setAttribute('type',array_search(reset($sym1), $sym1));	
				${"instr".$count_inst}->appendChild(${"arg2".$count_inst});

				${"arg3".$count_inst} = $XML->createElement("arg3",reset($sym2));
				${"arg3".$count_inst}->setAttribute('type',array_search(reset($sym2), $sym2));	
				${"instr".$count_inst}->appendChild(${"arg3".$count_inst});		
			}					
			break;
															
		default:
		exit(21);
	}
}

}while($line);


$XML->appendChild($prog);
$XML->formatOutput = true;

echo($XML->saveXML());
//var_dump($count_comments);
//var_dump($count_inst);
if(array_key_exists("stats", $args))
{
	if(($index_comm != -1)&&($index_loc != -1))
	{
		if($index_loc > $index_comm)
		{
			fwrite($out, "$count_inst\n$count_comments\n");
		}
		else{
			fwrite($out, "$count_comments\n$count_inst\n");
		}
	}
	else if($index_comm != -1)
	{
		fwrite($out, "$count_comments\n");
	}
	else if($index_loc != -1)
	{
		fwrite($out, "$count_inst\n");	
	}

	fclose($out);
}

exit(0);
?>
