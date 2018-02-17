<?php  

$longopts  = array(
    "help",     // help
    "stats:",    // must have one parameter
    "comments",        // without parameter
    "loc",           // without parameter
);
$index_comm = -1; // -1 not set
$index_loc = -1; // -1 not set

$args = getopt("", $longopts);

$out = fopen($args["stats"], "w");

fwrite($out, "HELL\n");


fclose($out);

?>
