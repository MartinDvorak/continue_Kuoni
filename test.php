<?php  

//####################################################
//			 	ARGS 
$longopts  = array(
    "help",     		// help
    "directory:",   	// must have one parameter
    "recursive",        // without parameter
    "parse-script:",	// must have one parameter
    "int-script:",		// must have one parameter
    "testlist:",		// must have one parameter
    "match:",			// must have one parameter
);

$test_dir= "./";
$parse_file = "./parse.php";
$inter_file = "./interpret.py";
$recursive = false;
$testlist = false;
$regex_file = '/.*/';

$args = getopt("", $longopts);

if(count($argv) != 1)
{
if (count($args)+1 != count($argv))
	{
		exit(10);
	}	
if (array_key_exists("help", $args))
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
if((array_key_exists("directory", $args))&&(!array_key_exists("testlist", $args)))	
	{
		$test_dir = $args["directory"];
	}
else if((!array_key_exists("directory", $args))&&(array_key_exists("testlist", $args)))	
	{
		$testlist = true;
		if(($list = fopen($args["testlist"],"r"))==false)
		{exit (11);}	
	}
else if(((array_key_exists("directory", $args))&&(array_key_exists("testlist", $args))))
	{
		exit(10);
	}	

if(array_key_exists("recursive", $args))	
	{
		$recursive = true;
	}
if(array_key_exists("parse-script", $args))	
	{
		$parse_file = $args["parse-script"];
	}
if(array_key_exists("int-script", $args))	
	{
		$inter_file = $args["int-script"];
	}			
if(array_key_exists("match", $args))
	{
		$regex_file =  $args["match"];
	}
}
//####################################################
// 			GET FILES
// 		\x2F	/

$regex_src = '/.+\.src$/';
$regex_remove_path = '/^.*\x2F/';
$all_tests = array(); 


// PARARAM --testlist=file
if($testlist)
{
	$input = fread($list, filesize($args["testlist"]));
	$items = preg_split("/\n/",$input);
	$test_dir = $items;
	//var_dump($items);
}
else{
	$test_dir = str_split($test_dir, strlen($test_dir));
}

// because of --testlist=file
foreach ($test_dir as $dir) 
{
	// $dir is file
	if(is_file($dir))
	{
		if(preg_match($regex_src, $dir))
		{	
			$no_path = preg_replace($regex_remove_path, "", $dir);
			if(preg_match($regex_file, substr($no_path, 0, -4)))
				$all_tests[] = $dir; 
		}
		continue;
	}
	// empty line
	if(trim($dir) == "")
	{continue;}

	if(is_dir($dir))
	{
	// becouse of --recursive
	if($recursive)
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

		foreach ($iterator as $file) 
		{
			if ($file->isDir())
				{ continue;}
			if(preg_match($regex_src, $file))
			{	
				$without_path = preg_replace('/^.*\x2F/', "", $file);		
				if(preg_match($regex_file, substr($without_path, 0, -4)))
					$all_tests[] = $file->getPathname(); 
			}	
		}
	}
	// none recursive param
	else{
		$local_dir = scandir($dir);
		foreach ($local_dir as $file) 
		{		
			if(preg_match($regex_src, $file))
			{	
				if(preg_match($regex_file, substr($file, 0, -4)))
					$all_tests[] = $dir."/".$file; 
			}
		}

	}
	}
}

//var_dump($all_tests);
//####################################################
// 				Result HTML v.5

function html_head($count,$success,$fail)
{
	if($count == 0)
		$percent = 0;
	else
		$percent = round(($success / $count)*100);

echo("<!DOCTYPE html>
<html>
<head>

<meta charset=\"UTF-8\">
<title>IPP proj1 testovani</title>

<style>

body{
	background-color: BurlyWood;
}

div.page{
	margin: auto;
	width: 1024px;
	position: relative;
	padding: 10px;
	background-color: linen;
	border-radius: 20px;
	font-size: 18px;
	color: Chocolate ;
	text-align: center;
}

div.text{
	margin: auto;
	width: 400px;
	position: relative;
	font-size: 18px;
	color: Chocolate ;
	text-align: left;
}

h1{
	color: maroon;
	margin: 40px;
	text-align: center;
	font-size: 35px;
}

h3{
	color: maroon;
	font-size: 25px;
	text-align: center;
}

div.tail{
	font-size: 20px;
	color: maroon;
	text-align: right;
	margin-right: 20px;

}

small.tick{
	color: green;
	font-size: 25px;
}

small.cross{
	color: red;
	font-size: 25px;
}
small.result{
	color: black;
	font-size: 18px;
}
</style>
</head>

<body>
<div class=\"page\">	
<h1>Souhrn a hodnoceni testu k projektu do predmetu IPP 2018</h1>

<div class=\"text\">


<h3>
Souhrn vsech testu
</h3>

<p>
	Celkovy pocet testu: <small class=\"result\"> $count</small><br>
	Pocet uspesnych testu: <small class=\"result\"> $success</small><small class=\"tick\">&#x2714</small><br>
	Pocet neuspesnych testu: <small class=\"result\"> $fail</small><small class=\"cross\">&#x2717</small><br>
	Procentualni uspesnost: <small class=\"result\"> $percent%</small><br>
</p>

<h3>
Jednotlive testy	
</h3>");
} 


function html_res_test($count,$name ,$expecter,$returned,$info,$result)
{
	$name = substr($name, 0,-4);
	global $html_tmp;
	fwrite($html_tmp, "<p>
	Test <small class=\"result\"> $count</small> $result <br>
	Jmeno a cesta: <small class=\"result\"> $name</small><br>
	Ocekavana navratova hodnota: <small class=\"result\">$expecter</small><br>
	Ziskana navratova hodnota:<small class=\"result\">$returned</small> <br>
	Dalsi informace: <small class=\"result\">$info</small><br>
</p>");

}

//####################################################
// 				Testing Files
$cross = 0;
$tick = 0;
$count = 0;
$succes_test = '<small class="tick">&#x2714</small>';
$fail_test = '<small class="cross">&#x2717</small>';
$html_tmp = tmpfile();


foreach ($all_tests as $test) {
	// file inst exists
	
	//if(!file_exists($test))
	//{
	//	continue;
	//}
	// check all files *.in *.out *.rc and set *.rc file.
	if(!($in = fopen(substr($test, 0,-3)."in", "c+")))
	{
		exit(11);
	}
	if(!($out = fopen(substr($test, 0,-3)."out", "c+")))
	{
		exit(11);
	}
	if(!($rc = fopen(substr($test, 0,-3)."rc", "c+")))
	{
		exit(11);
	}
	// get return code from *.rc or generate it
	if(filesize(substr($test, 0,-3)."rc") == 0)
	{
		fwrite($rc, "0\n");
		$return_code = 0;
	}
	else{
		$return_code = intval(fread($rc,filesize(substr($test, 0,-3)."rc")));
	}
	fclose($in);
	fclose($out);
	fclose($rc);

	$count += 1;
	//var_dump($test);
	// TESTING parse.php by default or --parse-script=file
	if(is_file($parse_file))
	{

		exec("php5.6 $parse_file < $test",$out_parse,$ret_parse);
		//exit code from parse.php
		if($ret_parse != 0)
		{
			if($ret_parse == $return_code)
			{
				// USPECH
				html_res_test($count,$test,$return_code,$ret_parse,"Test pro kontrolu $parse_file",$succes_test);
				$tick += 1;
			}
			else{
				// NEUSPECH TESTU
				html_res_test($count,$test,$return_code,$ret_parse,"Test pro kontrolu $parse_file",$fail_test);
				$cross += 1;
			}
		}
		else{
		// tmp file becouse interpret.py must have input from --source=file
		if(!($tmp_file = fopen(substr($test, 0,-3)."tmp", "w")))
		{
			exit(99);
		}

		fwrite($tmp_file, implode("\n",$out_parse));
		$source_name = substr($test, 0,-3)."tmp";
		$input_name = substr($test, 0,-3)."in";
		$out_name = substr($test, 0,-3)."out";
		$out_py = substr($test, 0,-3)."inter";
		
		fclose($tmp_file);

		// TESTING interpret.py by default or --int-script=file
		if(is_file($inter_file))
		{
			exec("python3.6 $inter_file --source=$source_name < $input_name > $out_py",$out_inter,$ret_inter); 
			//exit code from interpret.py
			if($ret_inter == $return_code)
			{
				exec("diff $out_name $out_py",$out_diff,$ret_diff);
				if($out_diff = "")
				{
					// test cely OK
					html_res_test($count,$test,$return_code,$ret_inter,"Test probehl v poradku",$succes_test);
					$tick += 1;
				}
				else{
					// NESPECH DIFF
					html_res_test($count,$test,$return_code,$ret_inter,"Neshoda vystupu $inter_file a referencniho *.out souboru",$fail_test);
					$cross += 1;
				}
			}
			else{
				// NEUSPECH TESTU
				html_res_test($count,$test,$return_code,$ret_inter,"Neschoda navratovych kodu",$fail_test);
				$cross += 1;
			}
			//var_dump($out_inter);
			unlink($out_py);	
		}
		else{
			html_res_test($count,$test,$return_code,'<small class="cross">x</small>',"Nenalezen soubor pro interpretaci",$fail_test);
			$cross += 1;

		}
		// remove tmp file
		unlink($source_name);
		}
	}
	else{
		html_res_test($count,$test,$return_code,'<small class="cross">x</small>',"Nenalezen soubor pro parsovani vstupu",$fail_test);
		$cross += 1;
	}
}

fseek($html_tmp, 0);
//	var_dump(fstat($html_tmp)["size"]);
//	var_dump(fread($html_tmp,1024));

html_head($count,$tick,$cross);

if(fstat($html_tmp)["size"] != 0)
	echo(fread($html_tmp,fstat($html_tmp)["size"]));
	
	// TAIL
echo('
	</div>
		<div class="tail">
		<b>Dvorak Martin, xdvora2l 2018</b> 
	</div>
	</div>
	</body>
	
	</html>');

//####################################################

?>
