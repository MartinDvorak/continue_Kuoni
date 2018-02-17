<?php  

//####################################################
//			 	ARGS 
$longopts  = array(
    "help",     		// help
    "directory:",   	// must have one parameter
    "recursive",        // without parameter
    "parse-script:",	// must have one parameter
    "int-script:",		// must have one parameter
);


$test_dir= "./";
$parse_file = "./";
$inter_file = "./";
$recursive = false;

$args = getopt("", $longopts);

if(count($argv) != 1)
{
if (count($args)+1 != count($argv))
	{
		exit(10);
	}	
else if (array_key_exists("help", $args))
	{	
		if(count($args) != 1)
		{
			exit(10);
		}
		else{
		echo("Skript kontroluje činnost skriptu parse.php (php5.6) nebo skriptu inerpret.py (python3.6).\n");
		echo("parametry :\n      --help  => vypíše tuto nápovědu\n");
		echo("      --directory=path testy bude hledat v zadaném adresáři (chybí-li tento parametr, tak skript
prochází aktuální adresář)\n");
		echo("      --recursive testy bude hledat nejen v zadaném adresáři, ale i rekurzivně ve všech jeho
podadresářích\n");
		echo("      --parse-script=file soubor se skriptem v PHP 5.6 pro analýzu zdrojového kódu v IPPcode18
(chybí-li tento parametr, tak implicitní hodnotou je parse.php uložený v aktuálním
adresáři)\n");
		echo("      --int-script=file soubor se skriptem v Python 3.6 pro interpret XML reprezentace kódu
v IPPcode18 (chybí-li tento parametr, tak implicitní hodnotou je interpret.py uložený v aktuálním
adresáři)\n");
		exit(0);
		}
	}
else if(array_key_exists("directory", $args))	
	{
		$test_dir = $args["directory"];
	}
else if(array_key_exists("recursive", $args))	
	{
		$recursive = true;
	}
else if(array_key_exists("parse-script", $args))	
	{
		$parse_file = $args["parse-script"];
	}
else if(array_key_exists("int-script", $args))	
	{
		$inter_file = $args["int-script"];
	}		
else{
	exit(10);
	}	
}

echo("HELL\n");

//####################################################

?>
