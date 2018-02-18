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
$parse_file = "./parser.php";
$inter_file = "./interpret.py";
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
function recursive_dir($regex,$dir,$path)
{
	global $all_tests;
	$local_dir = scandir($dir);

	foreach($local_dir as $file)
	{
		if(is_dir($file)&&($file != '..' ) && ($file != '.'))
		{
			recursive_dir($regex,$file,$path."/".$file);
		}
		else if(preg_match($regex, $file))
		{	
			array_push($all_tests, $path."/".$file);
		}			
	}

}

//####################################################
// 			REGEX

$regex_src = '/.+\.src$/';
$all_tests = array();

if($recursive)
{
	recursive_dir($regex_src,$test_dir,".");
}
else{
	$local_dir = scandir($test_dir);
	foreach($local_dir as $file)
	{
		if(preg_match($regex_src, $file))
		{	
			array_push($all_tests, $file);
		}			
	}
}

foreach ($all_tests as $test) {
	$out = shell_exec("php5.6 $parse_file < $test");
	var_dump($out);
}
var_dump($all_tests)

//http://php.net/manual/en/function.exec.php
//####################################################

?>
